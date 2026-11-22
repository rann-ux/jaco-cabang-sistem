<?php

namespace Database\Seeders;

use App\Models\Cabang;
use App\Models\Karyawan;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\Stok;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Data Cabang
        $cabang = Cabang::firstOrCreate(
            ['nama_cabang' => 'Cabang Utama'],
            [
                'jenis' => 'showroom',
                'alamat' => 'Alamat Cabang',
            ]
        );

        // 2. Data Karyawan
        Karyawan::firstOrCreate(
            [
                'id_cabang' => $cabang->id_cabang,
                'nama' => 'Admin Cabang',
                'peran' => 'sales',
            ],
            [
                'status' => 'aktif',
            ]
        );

        // 3. Data Pelanggan
        Pelanggan::firstOrCreate(
            ['no_hp' => '081200000000'],
            [
                'nama' => 'Contoh Pelanggan',
                'alamat' => 'Alamat Pelanggan',
            ]
        );

        // 4. Data Produk
        $produk = Produk::updateOrCreate(
            ['kode_produk' => 'KP-001'],
            [
                'nama_produk' => 'Contoh Produk',
                'kategori' => 'Elektronik',
                'harga' => 1500000,
                'masa_garansi' => 12,
            ]
        );

        // 5. Data Stok Awal
        Stok::updateOrCreate(
            [
                'id_cabang' => $cabang->id_cabang,
                'id_produk' => $produk->id_produk,
            ],
            [
                'jumlah' => 10,
                'batas_minimum' => 5,
                'tgl_update' => now(),
            ]
        );
    }
}
