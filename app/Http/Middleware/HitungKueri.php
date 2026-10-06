<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class HitungKueri
{
    public function handle(Request $request, Closure $next): Response
    {
        $jumlahKueri = 0;

        // Hitung setiap kueri basis data yang dieksekusi selama request berlangsung
        DB::listen(function () use (&$jumlahKueri): void {
            $jumlahKueri++;
        });

        $response = $next($request);

        // Hanya tampilkan header di environment local untuk keperluan observasi/debug
        if (app()->isLocal()) {
            $response->headers->set('X-Jumlah-Kueri', (string) $jumlahKueri);
        }

        return $response;
    }
}
