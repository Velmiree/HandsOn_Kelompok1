<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class TolakUserAgentKosong
{
    /**
     * Menolak request yang tidak menyertakan header User-Agent.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('User-Agent') === null) {
            return response()->json([
                'kesalahan' => 'user_agent_kosong',
                'pesan' => 'Header User-Agent wajib disertakan.',
            ], 400);
        }

        return $next($request);
    }
}