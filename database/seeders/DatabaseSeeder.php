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
        Cabang::updateOrCreate(
            ['id_cabang' => 'JMB-01'],
            [
                'nama_cabang' => 'Cabang Jambi',
                'jenis' => 'showroom',
                'alamat' => 'Kota Jambi',
            ]
        );

        // 2. Data Karyawan
        Karyawan::updateOrCreate(
            ['id_karyawan' => 'KRY-0001'],
            [
                'id_cabang' => 'JMB-01',
                'nama' => 'Admin Jambi',
                'peran' => 'sales',
                'status' => 'aktif',
            ]
        );

        // 3. Data Pelanggan
        Pelanggan::updateOrCreate(
            ['id_pelanggan' => 'PLG-0001'],
            [
                'nama' => 'Contoh Pelanggan',
                'alamat' => 'Kota Jambi',
                'no_hp' => '081200000000',
            ]
        );

        // 4. Data Produk
        Produk::updateOrCreate(
            ['id_produk' => 'PRD-001'],
            [
                'kode_produk' => 'KP-001',
                'nama_produk' => 'Contoh Produk',
                'kategori' => 'Elektronik',
                'harga' => 1500000,
                'masa_garansi' => 12,
            ]
        );

        // 5. Data Stok Awal
        Stok::updateOrCreate(
            ['id_stok' => 'STK-0001'],
            [
                'id_cabang' => 'JMB-01',
                'id_produk' => 'PRD-001',
                'jumlah' => 10,
                'batas_minimum' => 5,
                'tgl_update' => '2026-11-03',
            ]
        );
    }
}