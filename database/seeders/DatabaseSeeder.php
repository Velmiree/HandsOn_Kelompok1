<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Urutan penting: produk dibuat sebelum pemasok dan transaksi contoh.
        $this->call([
            KategoriProdukSeeder::class,
            PemasokSeeder::class,
            TransaksiContohSeeder::class,
        ]);
    }
}
