<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_stoks', function (Blueprint $table) {
            $table->increments('id_mutasi');
            $table->unsignedInteger('id_stok');
            $table->unsignedInteger('id_karyawan');
            $table->enum('jenis', ['masuk', 'keluar']);
            $table->integer('jumlah');
            $table->dateTime('tanggal')->useCurrent();
            $table->string('referensi_dokumen', 50)->nullable();

            $table->foreign('id_stok')
                ->references('id_stok')
                ->on('stoks')
                ->restrictOnDelete();

            $table->foreign('id_karyawan')
                ->references('id_karyawan')
                ->on('karyawans')
                ->restrictOnDelete();
        });

        DB::statement("
            ALTER TABLE mutasi_stoks
            ADD CONSTRAINT chk_mutasi_jumlah_positive
            CHECK (jumlah > 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_stoks');
    }
};
