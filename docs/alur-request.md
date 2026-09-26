# Alur Request API POS

## Endpoint yang Digunakan

Endpoint tulis yang digunakan untuk menggambarkan alur request adalah:

```text
POST /api/v1/pos/transaksi
```

# Diagram Alur Request
┌──────────────────────────────┐
│           Client             │
│        Postman / API         │
└──────────────┬───────────────┘
               │
               │ HTTP POST
               ▼
┌──────────────────────────────┐
│       public/index.php       │
│       Entry Point Laravel    │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│      Laravel Application     │
│          Bootstrap           │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│       API Middleware         │
│        CatatRequest          │
│                              │
│ - membuat ID request         │
│ - mulai menghitung durasi    │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│       Route Middleware       │
│            kasir             │
│       KunciApiKasir          │
│                              │
│ - memeriksa X-API-Key        │
│ - menyimpan identitas kasir  │
│   ke request attributes      │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│       Route Middleware       │
│          jam.buka            │
│       JamOperasional         │
│                              │
│ - memeriksa jam operasional  │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│     TransaksiController      │
│            store()           │
│                              │
│ - validasi request           │
│ - mengambil identitas kasir  │
└──────────────┬───────────────┘
               │
               │ proses()
               ▼
┌──────────────────────────────┐
│        LayananKasir          │
│           proses()           │
│                              │
│ - menentukan metode bayar    │
│ - memanggil hitung()         │
│ - memeriksa pembayaran       │
│ - membuat data transaksi     │
└──────────────┬───────────────┘
               │
               │ hitung()
               ▼
┌──────────────────────────────┐
│        LayananKasir          │
│           hitung()           │
│                              │
│ - menghitung subtotal        │
│ - diskon grosir              │
│ - diskon member              │
│ - diskon happy hour          │
│ - menghitung DPP             │
│ - menghitung PPN             │
│ - pembulatan                 │
└──────────────┬───────────────┘
               │
               │ cariSku()
               ▼
┌──────────────────────────────┐
│      RepositoriProduk        │
│          Interface           │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│     RepositoriProdukArray    │
│        Implementasi          │
│                              │
│ - menyediakan data produk    │
│   dari katalog array         │
└──────────────┬───────────────┘
               │
               │ data produk
               ▼
┌──────────────────────────────┐
│        LayananKasir          │
│           proses()           │
│                              │
│ - membentuk data transaksi   │
│ - menghitung pembayaran      │
└──────────────┬───────────────┘
               │
               │ simpan()
               ▼
┌──────────────────────────────┐
│     RepositoriTransaksi      │
│          Interface           │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│ RepositoriTransaksiBerkas    │
│        Implementasi          │
│                              │
│ - menyimpan transaksi ke     │
│   berkas JSON                │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│         Berkas JSON          │
│      Penyimpanan transaksi   │
└──────────────┬───────────────┘
               │
               │ hasil penyimpanan
               ▼
┌──────────────────────────────┐
│        LayananKasir          │
│    mengembalikan transaksi   │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│     TransaksiController      │
│                              │
│ return response()->json()    │
│          status 201           │
└──────────────┬───────────────┘
               │
               │ arah balik
               ▼
┌──────────────────────────────┐
│          jam.buka            │
│    Middleware Return         │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│            kasir             │
│    Middleware Return         │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│        CatatRequest          │
│                              │
│ - menghitung durasi akhir    │
│ - mencatat jika:             │
│   > 100 ms atau 4xx/5xx      │
│ - menambahkan X-Request-Id   │
│ - menambahkan X-Response-    │
│   Time                       │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│           Client             │
│      menerima response       │
└──────────────────────────────┘

# Urutan Middleware
## Arah Masuk
Urutan request pada arah masuk adalah:

Client
↓
public/index.php
↓
Laravel Application
↓
CatatRequest
↓
KunciApiKasir (alias: kasir)
↓
JamOperasional (alias: jam.buka)
↓
TransaksiController@store
↓
LayananKasir
↓
RepositoriProduk
↓
RepositoriProdukArray
↓
LayananKasir
↓
RepositoriTransaksi
↓
RepositoriTransaksiBerkas
↓
Berkas JSON

## Arah Balik
Setelah controller menghasilkan response, response berjalan kembali melalui middleware dengan urutan terbalik:

TransaksiController@store
↓
JamOperasional
↓
KunciApiKasir
↓
CatatRequest
↓
Client

# Ringkasan Alur
Secara sederhana, alur request dapat diringkas menjadi:

Client
→ public/index.php
→ Middleware
→ Controller
→ Service
→ Repository Produk
→ Service
→ Repository Transaksi
→ Berkas JSON
→ Service
→ Controller
→ Middleware
→ Client