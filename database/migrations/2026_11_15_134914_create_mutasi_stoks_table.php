<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_stoks', function (Blueprint $table) {
            $table->string('id_mutasi', 20)->primary();
            $table->string('id_stok', 20);
            $table->string('id_karyawan', 20);
            $table->string('jenis', 20);
            $table->integer('jumlah');
            $table->date('tanggal');
            $table->string('referensi_dokumen')->nullable();

            $table->foreign('id_stok')
                ->references('id_stok')
                ->on('stoks')
                ->restrictOnDelete();

            $table->foreign('id_karyawan')
                ->references('id_karyawan')
                ->on('karyawans')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_stoks');
    }
};