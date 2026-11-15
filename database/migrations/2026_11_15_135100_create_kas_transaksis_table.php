<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_transaksis', function (Blueprint $table) {
            $table->string('id_kas', 20)->primary();
            $table->string('id_cabang', 20);
            $table->string('id_karyawan', 20);
            $table->string('id_faktur', 20)->nullable();
            $table->date('tanggal');
            $table->string('jenis', 20);
            $table->decimal('jumlah', 15, 2);
            $table->text('keterangan')->nullable();

            $table->foreign('id_cabang')
                ->references('id_cabang')
                ->on('cabangs')
                ->restrictOnDelete();

            $table->foreign('id_karyawan')
                ->references('id_karyawan')
                ->on('karyawans')
                ->restrictOnDelete();

            $table->foreign('id_faktur')
                ->references('id_faktur')
                ->on('fakturs')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_transaksis');
    }
};