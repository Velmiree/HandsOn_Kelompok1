<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Pemasok;
use App\Models\Produk;
use Illuminate\Database\Seeder;

class PemasokSeeder extends Seeder
{
    public function run(): void
    {
        $pemasok1 = Pemasok::create([
            'nama' => 'PT Sumber Pangan',
            'kota' => 'Surakarta',
        ]);

        $pemasok2 = Pemasok::create([
            'nama' => 'CV Mitra Niaga',
            'kota' => 'Semarang',
        ]);

        $produk = Produk::query()->orderBy('id')->get();

        foreach ($produk as $index => $item) {
            $pemasokUtama = $index % 2 === 0 ? $pemasok1 : $pemasok2;

            $pemasokUtama->produk()->attach($item->id, [
                'harga_beli' => (int) ($item->harga * 0.8),
                'utama' => true,
            ]);

            $pemasokAlternatif = $pemasokUtama->is($pemasok1)
                ? $pemasok2
                : $pemasok1;

            $pemasokAlternatif->produk()->attach($item->id, [
                'harga_beli' => (int) ($item->harga * 0.85),
                'utama' => false,
            ]);
        }
    }
}