<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\RepositoriProduk;
use App\Contracts\RepositoriTransaksi;
use App\Domain\MetodeBayar;
use App\Domain\StatusTransaksi;
use App\Domain\Uang;
use App\Exceptions\PembayaranKurang;
use App\Exceptions\ProdukTidakDitemukan;
use App\Exceptions\StokTidakCukup;
use App\Exceptions\TransaksiSudahDibatalkan;
use App\Exceptions\TransaksiTidakDitemukan;
use Illuminate\Support\Facades\DB;

/**
 * Inti aturan bisnis kasir (AB-1 s.d. AB-10).
 *
 * Seluruh angka uang dihitung ulang di sini dari data mentah
 * (SKU dan kuantitas). Nilai total yang dikirim klien tidak
 * pernah dipercaya.
 */
final class LayananKasir
{
    public function __construct(
        private readonly RepositoriProduk $produk,
        private readonly RepositoriTransaksi $transaksi,
    ) {}

    /**
     * Menghitung rincian struk tanpa menyimpannya.
     * Dipakai ulang oleh endpoint pratinjau maupun oleh proses().
     *
     * @param  array<int, array{sku: string, kuantitas: int}>  $item
     * @return array<string, mixed>
     */
    public function hitung(array $item, bool $member = false): array
    {
        $minimalGrosir = (int) config('pos.grosir.minimal_kuantitas');
        $persenGrosir = (float) config('pos.grosir.persen');

        $baris = [];
        $subtotal = Uang::nol();
        $diskonItem = Uang::nol();

        foreach ($item as $masukan) {
            $produk = $this->produk->cariSku($masukan['sku']);

            if ($produk === null) {
                throw new ProdukTidakDitemukan($masukan['sku']); // -> 404
            }

            $kuantitas = (int) $masukan['kuantitas'];

            if ($kuantitas > $produk['stok']) { // AB-8
                throw new StokTidakCukup(
                    $produk['sku'],
                    $kuantitas,
                    $produk['stok']
                );
            }

            $hargaSatuan = new Uang($produk['harga']);
            $totalBaris = $hargaSatuan->kali($kuantitas); // AB-1

            $diskonBaris = $kuantitas >= $minimalGrosir // AB-2
                ? $totalBaris->persen($persenGrosir)
                : Uang::nol();

            $subtotal = $subtotal->tambah($totalBaris);
            $diskonItem = $diskonItem->tambah($diskonBaris);

            $baris[] = [
                'sku' => $produk['sku'],
                'nama' => $produk['nama'],
                'harga_satuan' => $hargaSatuan->rupiah,
                'kuantitas' => $kuantitas,
                'diskon' => $diskonBaris->rupiah,
                'total' => $totalBaris->kurang($diskonBaris)->rupiah,
                'total_format' => $totalBaris->kurang($diskonBaris)->format(),
            ];
        }

        $diskonMember = $member // AB-3
            ? $subtotal->kurang($diskonItem)->persen(
                (float) config('pos.member.persen')
            )
            : Uang::nol();

        $diskonHappyHour = Uang::nol();

        $jamSekarang = now()->format('H:i');

        if ($jamSekarang >= '16:00' && $jamSekarang < '18:00') {
            $diskonHappyHour = $subtotal->persen(10);
        }

        $totalDiskon = $diskonItem
            ->tambah($diskonMember)
            ->tambah($diskonHappyHour);

        $dpp = $subtotal->kurang($totalDiskon);

        $ppn = $dpp->persen(
            (float) config('pos.ppn_persen')
        ); // AB-5

        $total = $dpp->tambah($ppn);

        $totalBayar = $total->bulatkanKeAtas(
            (int) config('pos.pembulatan')
        ); // AB-6

        return [
            'item' => $baris,
            'subtotal' => $subtotal->rupiah,
            'diskon_grosir' => $diskonItem->rupiah,
            'diskon_member' => $diskonMember->rupiah,
            'diskon_happy_hour' => $diskonHappyHour->rupiah,
            'total_diskon' => $totalDiskon->rupiah,
            'dpp' => $dpp->rupiah,
            'ppn' => $ppn->rupiah,
            'total' => $total->rupiah,
            'pembulatan' => $totalBayar->kurang($total)->rupiah,
            'total_bayar' => $totalBayar->rupiah,
            'total_bayar_format' => $totalBayar->format(),
        ];
    }

    /**
     * Memproses satu penjualan dan menyimpan struknya.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function proses(array $data, string $kasir): array
    {
        return DB::transaction(function () use ($data, $kasir): array {
        $metode = MetodeBayar::from($data['metode_bayar']);
        $member = (bool) ($data['member'] ?? false);

        $rincian = $this->hitung($data['item'], $member);

        foreach ($data['item'] as $baris) {
            $tersedia = $this->produk->kunciStok($baris['sku']);

            if ($baris['kuantitas'] > $tersedia) {
                throw new StokTidakCukup(
                    $baris['sku'],
                    (int) $baris['kuantitas'],
                    $tersedia
                );
            }
        }

            $totalBayar = new Uang($rincian['total_bayar']);

            $dibayar = $metode->butuhKembalian()
                ? new Uang((int) ($data['dibayar'] ?? 0))
                : $totalBayar;

            if ($dibayar->kurangDari($totalBayar)) {
                throw new PembayaranKurang(
                    $totalBayar->kurang($dibayar)
                );
            }

            $transaksi = array_merge([
                'nomor' => $this->nomorBaru(),
                'kasir' => $kasir,
                'member' => $member,
                'metode_bayar' => $metode->value,
                'status' => StatusTransaksi::Selesai->value,
            ], $rincian, [
                'dibayar' => $dibayar->rupiah,
                'kembalian' => $dibayar->kurang($totalBayar)->rupiah,
            ]);

            $this->transaksi->simpan($transaksi);

            foreach ($data['item'] as $baris) {
                $this->produk->ubahStok(
                    $baris['sku'],
                    -1 * (int) $baris['kuantitas']
                );
            }

            return $this->transaksi->cariNomor($transaksi['nomor']);
        });
    }

    /** @return array<string, mixed> */
    public function batalkan(string $nomor, string $alasan, string $olehKasir): array
    {
        return DB::transaction(function () use ($nomor, $alasan, $olehKasir): array {
            $transaksi = $this->transaksi->cariNomor($nomor);

            if ($transaksi === null) {
                throw new TransaksiTidakDitemukan($nomor);
            }

            if ($transaksi['status'] === StatusTransaksi::Batal->value) {
                throw new TransaksiSudahDibatalkan($nomor);
            }

            $this->transaksi->perbarui($nomor, [
                'status' => StatusTransaksi::Batal->value,
                'alasan_batal' => $alasan,
                'dibatalkan_oleh' => $olehKasir,
                'dibatalkan_pada' => now(),
            ]);

            foreach ($transaksi['item'] as $baris) {
                $this->produk->ubahStok(
                    $baris['sku'],
                    (int) $baris['kuantitas']
                );
            }

            return $this->transaksi->cariNomor($nomor);
        });
    }

    /** @return array<int, array<string, mixed>> */
    public function transaksiTanggal(string $tanggal): array
    {
        return array_values(
            array_filter(
                $this->transaksi->semua(),
                static fn (array $t): bool => str_starts_with($t['waktu'], $tanggal)
            )
        );
    }

    /** @return array<string, mixed> */
    public function cari(string $nomor): array
    {
        $transaksi = $this->transaksi->cariNomor($nomor);

        if ($transaksi === null) {
            throw new TransaksiTidakDitemukan($nomor);
        }

        return $transaksi;
    }

    /** Nomor struk berformat POS-YYYYMMDD-0001, berulang tiap hari. */
    private function nomorBaru(): string
    {
        $tanggal = now()->toDateString();

        return sprintf(
            'POS-%s-%04d',
            now()->format('Ymd'),
            $this->transaksi->urutanBerikutnya($tanggal),
        );
    }
}
