<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FakturController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Modul M1 - Penjualan dan Faktur
Route::prefix('v1')->group(function () {
    Route::post('/faktur', [FakturController::class, 'store']);
});
