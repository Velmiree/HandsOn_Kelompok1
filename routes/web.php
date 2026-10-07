<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/openapi.yaml', function () {
    return response(
        file_get_contents(base_path('docs/openapi.yaml'))
    )->header('Content-Type', 'text/plain; charset=UTF-8');
});

if (! app()->isProduction()) {
    Route::prefix('docs')->name('docs.')->group(function () {
        Route::get('/openapi.yaml', function () {
            return response()->file(
                base_path('docs/openapi.yaml'),
                [
                    'Content-Type' => 'application/yaml; charset=UTF-8',
                    'Cache-Control' => 'no-store',
                ]
            );
        })->name('spesifikasi');

        Route::view(
            '/swagger',
            'dokumentasi.swagger'
        )->name('swagger');
    });
}