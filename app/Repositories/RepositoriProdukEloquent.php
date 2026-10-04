<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\RepositoriProduk;
use App\Models\Produk;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pengganti RepositoriProdukArray dari Modul 3.
 *
 * Bentuk nilai kembaliannya sengaja dijaga persis sama: array asosiatif
 * berisi sku, nama, kategori, harga, dan stok. Karena itulah LayananKatalog,
 * LayananKasir, dan seluruh controller tidak berubah satu baris pun.
 */
final class RepositoriProdukEloquent implements RepositoriProduk
{
    public function semua(): array
    {
        return $this->dasar()
            ->orderBy('produk.nama')
            ->get()
            ->map($this->keArray(...))
            ->all();
    }

    public function cariSku(string $sku): ?array
    {
        $produk = $this->dasar(aktifSaja: false)
            ->where('produk.sku', strtoupper(trim($sku)))
            ->first();

        return $produk === null ? null : $this->keArray($produk);
    }

    public function cariPemasok(string $sku): ?array
    {
        $produk = Produk::query()
            ->with([
                'kategori',
                'pemasok' => fn ($query) => $query
                    ->orderByPivot('utama', 'desc')
                    ->orderBy('pemasok.nama'),
            ])
            ->where('sku', strtoupper(trim($sku)))
            ->first();

        if ($produk === null) {
            return null;
        }

        return [
            ...$this->keArray($produk),
            'pemasok' => $produk->pemasok
                ->map(fn ($pemasok): array => [
                    'nama' => $pemasok->nama,
                    'kota' => $pemasok->kota,
                    'harga_beli' => $pemasok->pasokan->harga_beli,
                    'utama' => $pemasok->pasokan->utama,
                ])
                ->all(),
        ];
    }

    public function kurangiStok(string $sku, int $kuantitas): void
    {
        Produk::query()
            ->where('sku', strtoupper(trim($sku)))
            ->decrement('stok', $kuantitas);
    }

    public function kunciStok(string $sku): int
    {
        return (int) Produk::query()
            ->where('sku', strtoupper(trim($sku)))
            ->lockForUpdate()
            ->value('stok');
    }

    public function ubahStok(string $sku, int $selisih): void
    {
        Produk::query()
            ->where('sku', strtoupper(trim($sku)))
            ->increment('stok', $selisih);
    }

    /**
     * Kueri dasar: produk digabung dengan kategori lewat join eksplisit.
     * Relasi Eloquent (belongsTo/hasMany) baru diperkenalkan pada Modul 5.
     */
    private function dasar(bool $aktifSaja = true): Builder
    {
        return Produk::query()
            ->when($aktifSaja, fn (Builder $q) => $q->aktif())
            ->with('kategori');
    }

    /** @return array<string, mixed> */
    /** @return array<string, mixed> */
    private function keArray(Produk $produk): array
    {
        return [
            'sku' => $produk->sku,
            'nama' => $produk->nama,
            'kategori' => $produk->kategori?->kode,
            'harga' => $produk->harga,
            'stok' => $produk->stok,
        ];
    }
}
