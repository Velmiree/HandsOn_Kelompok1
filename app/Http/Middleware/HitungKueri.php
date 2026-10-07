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
        if (! app()->isLocal()) {
            return $next($request);
        }

        $jumlah = 0;

        DB::listen(function () use (&$jumlah): void {
            $jumlah++;
        });

        $response = $next($request);

        $response->headers->set('X-Jumlah-Kueri', (string) $jumlah);

        return $response;
    }
}