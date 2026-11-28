<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FakturController;
use App\Http\Controllers\Api\PembayaranController;
use App\Http\Controllers\Api\PenerimaanBarangController;
use App\Http\Controllers\Api\MutasiStokController;
use App\Http\Controllers\Api\CabangController;
use App\Http\Controllers\Api\PelangganController;
use App\Http\Controllers\Api\SinkronisasiMasterDataController;
use App\Http\Controllers\Api\KasController;
use App\Http\Controllers\Api\LaporanHarianController;

// Semua endpoint v1 wajib melewati autentikasi Keycloak.
Route::prefix('v1')->middleware('keycloak')->group(function () {

    // M1 - Penjualan dan Faktur
    Route::get('/faktur/{id}', [FakturController::class, 'show'])
        ->middleware('role:sales_cabang,admin_cabang,manajer_cabang,admin_pusat');

    Route::post('/faktur', [FakturController::class, 'store'])
        ->middleware('role:sales_cabang');

    // M-03 - Pembayaran
    Route::post('/faktur/{id}/pembayaran', [PembayaranController::class, 'store'])
        ->middleware('role:sales_cabang');

    Route::patch('/pembayaran/{id}/verifikasi-sales', [PembayaranController::class, 'verifySales'])
        ->middleware('role:sales_cabang');

    Route::patch('/pembayaran/{id}/cek-pusat', [PembayaranController::class, 'cekPusat'])
        ->middleware('role:admin_pusat');

    // M2 - Stok dan Gudang
    Route::post('/penerimaan-barang', [PenerimaanBarangController::class, 'store'])
        ->middleware('role:gudang_cabang');

    Route::post('/mutasi-stok', [MutasiStokController::class, 'store'])
        ->middleware('role:gudang_cabang');

    // M6 - Master Data Cabang
    Route::get('/cabang', [CabangController::class, 'index'])
        ->middleware('role:sales_cabang,admin_cabang,gudang_cabang,manajer_cabang,admin_pusat');

    Route::get('/cabang/{id}', [CabangController::class, 'show'])
        ->middleware('role:sales_cabang,admin_cabang,gudang_cabang,manajer_cabang,admin_pusat');

    Route::post('/cabang', [CabangController::class, 'store'])
        ->middleware('role:admin_pusat');

    Route::put('/cabang/{id}', [CabangController::class, 'update'])
        ->middleware('role:admin_pusat');

    Route::delete('/cabang/{id}', [CabangController::class, 'destroy'])
        ->middleware('role:admin_pusat');

    // M6 - Data Pelanggan
    Route::get('/pelanggan', [PelangganController::class, 'index'])
        ->middleware('role:sales_cabang,admin_cabang,gudang_cabang,manajer_cabang,admin_pusat');

    Route::get('/pelanggan/{id}', [PelangganController::class, 'show'])
        ->middleware('role:sales_cabang,admin_cabang,gudang_cabang,manajer_cabang,admin_pusat');

    Route::post('/pelanggan', [PelangganController::class, 'store'])
        ->middleware('role:admin_cabang');

    Route::put('/pelanggan/{id}', [PelangganController::class, 'update'])
        ->middleware('role:admin_cabang');

    Route::delete('/pelanggan/{id}', [PelangganController::class, 'destroy'])
        ->middleware('role:admin_cabang');

    // M7 - Sinkronisasi Master Data
    Route::post('/sinkronisasi/produk', [SinkronisasiMasterDataController::class, 'syncProduk'])
        ->middleware('role:admin_pusat');

    Route::post('/sinkronisasi/promo', [SinkronisasiMasterDataController::class, 'syncPromo'])
        ->middleware('role:admin_pusat');

    // M-04 - Transaksi Kas
    Route::get('/kas', [KasController::class, 'index'])
        ->middleware('role:admin_cabang,manajer_cabang,admin_pusat');

    Route::get('/kas/{id}', [KasController::class, 'show'])
        ->middleware('role:admin_cabang,manajer_cabang,admin_pusat');

    Route::post('/kas', [KasController::class, 'store'])
        ->middleware('role:admin_cabang');

    // M-04 - Laporan Harian
    Route::get('/laporan-harian', [LaporanHarianController::class, 'index'])
        ->middleware('role:admin_cabang,manajer_cabang,admin_pusat');

    Route::get('/laporan-harian/{id}', [LaporanHarianController::class, 'show'])
        ->middleware('role:admin_cabang,manajer_cabang,admin_pusat');

    Route::post('/laporan-harian', [LaporanHarianController::class, 'store'])
        ->middleware('role:admin_cabang');

    Route::patch('/laporan-harian/{id}/kirim', [LaporanHarianController::class, 'kirim'])
        ->middleware('role:admin_cabang');
});
