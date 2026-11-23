<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_harians', function (Blueprint $table) {
            $table->increments('id_laporan');
            $table->unsignedInteger('id_cabang');
            $table->unsignedInteger('id_karyawan');
            $table->date('tanggal');
            $table->enum('jenis_laporan', [
                'penjualan',
                'stok',
                'kas_masuk',
                'kas_keluar',
            ]);
            $table->string('status_kirim', 20)->default('belum_kirim');
            $table->dateTime('tgl_kirim')->nullable();

            $table->foreign('id_cabang')
                ->references('id_cabang')
                ->on('cabangs')
                ->restrictOnDelete();

            $table->foreign('id_karyawan')
                ->references('id_karyawan')
                ->on('karyawans')
                ->restrictOnDelete();

            $table->unique([
                'id_cabang',
                'tanggal',
                'jenis_laporan',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_harians');
    }
};
