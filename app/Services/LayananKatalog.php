<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\RepositoriProduk;
use App\Domain\Kategori;
use App\Domain\Uang;
use App\Exceptions\ProdukTidakDitemukan;

/**
 * Layanan katalog produk.
 * Tidak mengenal Request, response(), maupun status HTTP.
 */
final class LayananKatalog
{
    // Laravel menyuntikkan implementasi RepositoriProduk secara otomatis
    // berdasarkan binding di AppServiceProvider.
    public function __construct(
        private readonly RepositoriProduk $repositori,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function daftar(?string $kategori = null, ?string $cari = null): array
    {
        $produk = $this->repositori->semua();

        if ($kategori !== null) {
            $produk = array_filter(
                $produk,
                static fn (array $p): bool => $p['kategori'] === $kategori,
            );
        }

        if ($cari !== null) {
            $kunci = mb_strtolower($cari);

            $produk = array_filter(
                $produk,
                static fn (array $p): bool => str_contains(
                    mb_strtolower($p['nama']),
                    $kunci
                ),
            );
        }

        return array_values(array_map($this->format(...), $produk));
    }

    /** @return array<string, mixed> */
    public function ambil(string $sku): array
    {
        $produk = $this->repositori->cariSku($sku);

        if ($produk === null) {
            throw new ProdukTidakDitemukan($sku);
        }

        return $this->format($produk);
    }

    /**
     * Pemasok sebuah produk beserta marginnya (AB-13).
     *
     * @return array<string, mixed>
     */
    public function pemasok(string $sku): array
    {
        $produk = $this->repositori->cariPemasok($sku);

        if ($produk === null) {
            throw new ProdukTidakDitemukan($sku);
        }

        $produk['pemasok'] = array_map(
            static function (array $p) use ($produk): array {
                $margin = $produk['harga'] - $p['harga_beli'];

                return $p + [
                    'margin' => $margin,
                    'margin_persen' => round(
                        $margin * 100 / $produk['harga'],
                        1
                    ),
                ];
            },
            $produk['pemasok'],
        );

        return $produk;
    }

    /**
     * @param  array<string, mixed>  $produk
     * @return array<string, mixed>
     */
    private function format(array $produk): array
    {
        $kategori = Kategori::from($produk['kategori']);
        $harga = new Uang($produk['harga']);

        return [
            'sku' => $produk['sku'],
            'nama' => $produk['nama'],
            'kategori' => $kategori->value,
            'kategori_label' => $kategori->label(),
            'harga' => $harga->rupiah,
            'harga_format' => $harga->format(),
            'stok' => $produk['stok'],
            'tersedia' => $produk['stok'] > 0,
        ];
    }
}
