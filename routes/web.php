<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/
Route::get('/uploads/{path}', function ($path) {
    $rutaFisica = base_path('../uploads/' . $path);

    if (!File::exists($rutaFisica)) {
        abort(404);
    }

    return response()->file($rutaFisica);
})->where('path', '.*');

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
