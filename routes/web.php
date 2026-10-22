<?php

use App\Http\Controllers\AppManifestController;
use Illuminate\Support\Facades\Route;

Route::get('/app.webmanifest', [AppManifestController::class, 'manifest']);
Route::get('/app-icon/{size}.png', [AppManifestController::class, 'icon'])->where('size', '180|192|512');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/{path}', function () {
    return view('welcome');
})->where('path', '(?!api(?:/|$)|build(?:/|$)|storage(?:/|$)|assets(?:/|$))(?!.*\.[^/]+$).*');
