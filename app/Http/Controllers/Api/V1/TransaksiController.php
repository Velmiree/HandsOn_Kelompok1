<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LayananKasir;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TransaksiController extends Controller
{
    public function __construct(
        private readonly LayananKasir $kasir,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tanggal = $request->string('tanggal')->trim()->toString();

        $tanggal = $tanggal !== ''
            ? $tanggal
            : now()->toDateString();

        return response()->json([
            'data' => $this->kasir->transaksiTanggal($tanggal),
            'meta' => [
                'tanggal' => $tanggal,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item' => ['required', 'array', 'min:1'],
            'item.*.sku' => ['required', 'string'],
            'item.*.kuantitas' => ['required', 'integer', 'min:1'],
            'metode_bayar' => ['required', 'string'],
            'member' => ['sometimes', 'boolean'],
            'dibayar' => ['sometimes', 'integer', 'min:0'],
        ]);

        $kasir = $request->attributes->get('kasir');

        $transaksi = $this->kasir->proses(
            data: $data,
            kasir: $kasir['nama'],
        );

        return response()->json([
            'data' => $transaksi,
        ], 201);
    }

    public function show(string $nomor): JsonResponse
    {
        return response()->json([
            'data' => $this->kasir->cari($nomor),
        ]);
    }

    public function batal(
        Request $request,
        string $nomor
    ): JsonResponse {
        $data = $request->validate([
            'alasan' => ['required', 'string', 'min:3'],
        ]);

        $kasir = $request->attributes->get('kasir');

        return response()->json([
            'data' => $this->kasir->batalkan(
                nomor: $nomor,
                alasan: $data['alasan'],
                olehKasir: $kasir['nama'],
            ),
        ]);
    }

    public function pratinjau(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item' => ['required', 'array', 'min:1'],
            'item.*.sku' => ['required', 'string'],
            'item.*.kuantitas' => ['required', 'integer', 'min:1'],
            'member' => ['sometimes', 'boolean'],
        ]);

        // Method hitung() berada di service sehingga dapat digunakan ulang
        // oleh endpoint pratinjau tanpa menyalin logika perhitungan ke controller.
        // Jika logika hitung ditulis langsung di controller, logika tersebut
        // perlu dipindahkan ke service agar dapat digunakan kembali oleh
        // endpoint lain.
        $hasil = $this->kasir->hitung(
            item: $data['item'],
            member: (bool) ($data['member'] ?? false),
        );

        return response()->json([
            'message' => 'Pratinjau berhasil',
            'data' => $hasil,
        ]);
    }
}