<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Kategori as EnumKategori;
use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Database\Seeder;

/**
 * Data acuan katalog Barokah Mart.
 *
 * Sepuluh produk pertama SENGAJA ditulis tetap, bukan diacak, agar
 * seluruh angka pada bagian verifikasi dokumen ini dapat direproduksi
 * di mesin siapa pun. Data acak ditambahkan setelahnya lewat factory.
 */
final class KategoriProdukSeeder extends Seeder
{
    private const PRODUK = [
        ['SKU-001', 'Beras Pandan Wangi 5 kg', 'kebutuhan_rumah', 72000, 40],
        ['SKU-002', 'Minyak Goreng 1 L', 'kebutuhan_rumah', 18500, 120],
        ['SKU-003', 'Gula Pasir 1 kg', 'kebutuhan_rumah', 15500, 85],
        ['SKU-004', 'Kopi Bubuk 200 g', 'minuman', 24000, 60],
        ['SKU-005', 'Teh Celup 25 sachet', 'minuman', 9500, 0],
        ['SKU-006', 'Mie Instan Goreng', 'makanan', 3400, 480],
        ['SKU-007', 'Susu UHT 1 L', 'minuman', 19000, 36],
        ['SKU-008', 'Sabun Mandi Batang', 'kebutuhan_rumah', 5200, 150],
        ['SKU-009', 'Buku Tulis 38 lembar', 'alat_tulis', 4800, 200],
        ['SKU-010', 'Pena Gel Hitam', 'alat_tulis', 3900, 240],
    ];

    public function run(): void
    {
        // 1. Kategori diambil dari enum domain, sehingga tabel dan enum
        // tidak mungkin berbeda isi.
        $kategori = [];

        foreach (EnumKategori::cases() as $kasus) {
            // updateOrCreate membuat seeder ini AMAN dijalankan berulang kali:
            // dijalankan dua kali tidak menghasilkan kategori ganda.
            $kategori[$kasus->value] = Kategori::updateOrCreate(
                ['kode' => $kasus->value],
                ['nama' => $kasus->label(), 'aktif' => true],
            );
        }

        // 2. Sepuluh produk acuan.
        foreach (self::PRODUK as [$sku, $nama, $kode, $harga, $stok]) {
            Produk::updateOrCreate(
                ['sku' => $sku],
                [
                    'kategori_id' => $kategori[$kode]->id,
                    'nama' => $nama,
                    'harga' => $harga,
                    'aktif' => true,
                ],
            )->forceFill(['stok' => $stok])->save(); // stok di luar $fillable
        }

        // 3. Dua puluh produk acak sebagai isian katalog.
        Produk::factory()->count(14)->create([
            'kategori_id' => $kategori['makanan']->id,
        ]);

        Produk::factory()->grosir()->count(4)->create([
            'kategori_id' => $kategori['minuman']->id,
        ]);

        Produk::factory()->habis()->count(2)->create([
            'kategori_id' => $kategori['alat_tulis']->id,
        ]);
    }
}
