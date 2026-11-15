<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penerimaan_barangs', function (Blueprint $table) {
            $table->string('id_penerimaan', 20)->primary();
            $table->string('id_cabang', 20);
            $table->string('id_produk', 20);
            $table->string('id_karyawan', 20);
            $table->integer('jumlah');
            $table->date('tanggal');
            $table->string('no_dokumen_pusat', 30);

            $table->foreign('id_cabang')
                ->references('id_cabang')
                ->on('cabangs')
                ->restrictOnDelete();

            $table->foreign('id_produk')
                ->references('id_produk')
                ->on('produks')
                ->restrictOnDelete();

            $table->foreign('id_karyawan')
                ->references('id_karyawan')
                ->on('karyawans')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penerimaan_barangs');
    }
};