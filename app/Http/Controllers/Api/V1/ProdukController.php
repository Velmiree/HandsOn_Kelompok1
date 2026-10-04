<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LayananKatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller ramping: menerima masukan, memanggil SATU layanan,
 * lalu membentuk response. Tidak ada aturan bisnis.
 */
final class ProdukController extends Controller
{
    public function __construct(
        private readonly LayananKatalog $katalog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        // Query string dinormalkan: string kosong dianggap "tidak ada filter".
        $kategori = $request->string('kategori')->trim()->toString();
        $cari = $request->string('cari')->trim()->toString();

        $produk = $this->katalog->daftar(
            kategori: $kategori !== '' ? $kategori : null,
            cari: $cari !== '' ? $cari : null,
        );

        return response()->json([
            'data' => $produk,
            'meta' => [
                'jumlah' => count($produk),
                'kategori' => $kategori !== '' ? $kategori : 'semua',
            ],
        ]);
    }

    public function show(string $sku): JsonResponse
    {
        // Bila SKU tidak ada, LayananKatalog melempar ProdukTidakDitemukan
        // dan handler global mengubahnya menjadi response 404.
        return response()->json([
            'data' => $this->katalog->ambil($sku),
        ]);
    }

    public function pemasok(string $sku): JsonResponse
    {
        return response()->json([
            'data' => $this->katalog->pemasok($sku),
        ]);
    }
}
