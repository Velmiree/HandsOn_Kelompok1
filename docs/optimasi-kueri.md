# Laporan Optimasi Kueri - Modul 5

Volume data saat pengukuran:
- Transaksi: 283
- Item transaksi: 567
- Produk: 30
- Pemasok: 2

Alat ukur:
- Header `X-Jumlah-Kueri` (Middleware `HitungKueri`)
- Laravel Tinker (`EXPLAIN QUERY PLAN`)

## 1. Jumlah kueri sebelum dan sesudah

| Endpoint | Sebelum | Sesudah | Perbaikan |
|---|---:|---:|---|
| GET /api/v1/pos/transaksi | 284 | 2 | with('item') |
| GET /api/v1/pos/transaksi?tanggal=2026-10-06 | 284 | 2 | with('item') |
| GET /api/v1/pos/transaksi/{nomor} | 2 | 1 | with('item') |

## 2. Indeks

| Tabel | Indeks | Status | Tindakan |
|---|---|---|---|
| transaksi | (status, created_at) | Ada | Dioptimasi dengan rentang waktu SARGable |
| transaksi | (created_at) | Baru | Ditambahkan via migration `add_created_at_index_to_transaksi_table` |

## 3. Bukti EXPLAIN

| Kueri | Rencana Eksekusi Sebelum | Rencana Eksekusi Sesudah |
|---|---|---|
| Laporan harian (`WHERE status = 'selesai' AND ...`) | `SEARCH USING INDEX (status=?)` | `SEARCH USING INDEX (status=? AND created_at>? AND created_at<?)` |
| Daftar struk (`WHERE created_at ... ORDER BY nomor`) | `SCAN transaksi` (Full scan) | `SEARCH USING INDEX transaksi_created_at_index` |

## 4. Temuan yang diperbaiki

1. **Masalah N+1 Kueri pada Item Transaksi:**
   - *Penyebab:* Pengambilan item dilakukan terpisah per baris transaksi di dalam perulangan/pemetaan.
   - *Solusi:* Menggunakan eager loading `with('item')` pada Eloquent query di `RepositoriTransaksiEloquent`.
   - *Dampak:* Kueri terpangkas drastis dari 284 menjadi 2 kueri.

2. **Kueri Non-SARGable pada Filter Tanggal:**
   - *Penyebab:* Kolom `created_at` dibungkus fungsi `DATE(created_at) = ?`, yang mencegah indeks komposit memfilter kolom tanggal.
   - *Solusi:* Mengubah `scopeTanggal()` menggunakan perbandingan rentang waktu setengah terbuka `[>= startOfDay, < startOfDay + 1]`.
   - *Dampak:* Indeks komposit `(status, created_at)` kini memfilter status dan tanggal secara bersamaan.

3. **Ketiadaan Indeks Mandiri untuk Kolom `created_at`:**
   - *Penyebab:* Kueri daftar struk umum tidak menyaring kolom `status`, sehingga indeks komposit tidak bisa dimanfaatkan secara optimal.
   - *Solusi:* Membuat migration baru untuk menambahkan indeks `transaksi_created_at_index`.
   - *Dampak:* Kueri daftar struk langsung menggunakan indeks `created_at` tanpa harus melakukan full table scan.

## 5. Kepatuhan Kontrak
Seluruh antarmuka `RepositoriTransaksi` tetap terpenuhi tanpa merusak controller maupun lapisan service yang bergantung padanya.
