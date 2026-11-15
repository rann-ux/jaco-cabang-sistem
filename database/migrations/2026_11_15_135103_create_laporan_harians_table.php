<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_harians', function (Blueprint $table) {
            $table->string('id_laporan', 20)->primary();
            $table->string('id_cabang', 20);
            $table->string('id_karyawan', 20);
            $table->date('tanggal');
            $table->string('jenis_laporan', 30);
            $table->string('status_kirim', 20);
            $table->dateTime('tgl_kirim')->nullable();

            $table->foreign('id_cabang')
                ->references('id_cabang')
                ->on('cabangs')
                ->restrictOnDelete();

            $table->foreign('id_karyawan')
                ->references('id_karyawan')
                ->on('karyawans')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_harians');
    }
};