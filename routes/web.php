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
