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
        // Guard clause dari main: jika bukan local environment, langsung lewat
        if (! app()->isLocal()) {
            return $next($request);
        }

        $jumlahKueri = 0;

        // Hitung setiap kueri basis data yang dieksekusi selama request berlangsung
        DB::listen(function () use (&$jumlahKueri): void {
            $jumlahKueri++;
        });

        $response = $next($request);

        $response->headers->set('X-Jumlah-Kueri', (string) $jumlahKueri);

        return $response;
    }
}
