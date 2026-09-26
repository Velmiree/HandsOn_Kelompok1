<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\LaporanController;
use App\Http\Controllers\Api\V1\ProdukController;
use App\Http\Controllers\Api\V1\TransaksiController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn() => response()->json([
    'status' => 'ok',
    'toko' => config('pos.nama_toko'),
    'waktu' => now()->toIso8601String(),
]))->name('api.ping');

Route::prefix('v1/pos')
    ->name('api.v1.pos.')
    ->middleware('kasir')
    ->group(function () {

        Route::get('/produk', [ProdukController::class, 'index'])
            ->name('produk.index');

        Route::get('/pelanggan', function () {
            return response()->json([
                'message' => 'Data pelanggan Noven',
            ]);
        })->name('pelanggan.index');
        
        Route::get('/member', function () {
            return response()->json([
                'message' => 'Data member Hanif',
            ]);
        })->name('member.index');

        Route::get('/produk/{sku}', [ProdukController::class, 'show'])
            ->where('sku', 'SKU-[0-9]{3}')
            ->name('produk.show');

        Route::get('/transaksi', [TransaksiController::class, 'index'])
            ->name('transaksi.index');

        Route::post('/transaksi', [TransaksiController::class, 'store'])
            ->middleware('jam.buka')
            ->name('transaksi.store');

        Route::post('/pratinjau', [TransaksiController::class, 'pratinjau'])
            ->middleware('ua.required')
            ->name('transaksi.pratinjau');

        Route::get('/transaksi/{nomor}', [TransaksiController::class, 'show'])
            ->where('nomor', 'POS-[0-9]{8}-[0-9]{4}')
            ->name('transaksi.show');

        Route::post('/transaksi/{nomor}/batal', [TransaksiController::class, 'batal'])
            ->middleware('peran:supervisor')
            ->where('nomor', 'POS-[0-9]{8}-[0-9]{4}')
            ->name('transaksi.batal');

        Route::prefix('laporan')
            ->name('laporan.')
            ->group(function () {

                Route::get('/harian', [LaporanController::class, 'harian'])
                    ->name('harian');

                Route::get('/terlaris', [LaporanController::class, 'terlaris'])
                    ->name('terlaris');
            });
    });
