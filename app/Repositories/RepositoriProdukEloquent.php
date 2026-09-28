<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\RepositoriProduk;
use App\Models\Produk;
use Illuminate\Database\Eloquent\Builder;

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

    public function kunciStok(string $sku): int
    {
        return (int) Produk::query()
            ->where('sku', strtoupper(trim($sku)))
            ->lockForUpdate()
            ->value('stok');
    }

    public function ubahStok(
        string $sku,
        int $selisih
    ): void {
        Produk::query()
            ->where('sku', strtoupper(trim($sku)))
            ->increment('stok', $selisih);
    }

    private function dasar(
        bool $aktifSaja = true
    ): Builder {
        return Produk::query()
            ->when(
                $aktifSaja,
                fn (Builder $q) => $q->aktif()
            )
            ->join(
                'kategori',
                'kategori.id',
                '=',
                'produk.kategori_id'
            )
            ->select([
                'produk.*',
                'kategori.kode as kategori_kode',
            ]);
    }

    /** @return array<string, mixed> */
    private function keArray(
        Produk $produk
    ): array {
        return [
            'sku'      => $produk->sku,
            'nama'     => $produk->nama,
            'kategori' => $produk->kategori_kode,
            'harga'    => $produk->harga,
            'stok'     => $produk->stok,
        ];
    }
}