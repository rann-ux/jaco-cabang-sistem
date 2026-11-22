<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_transaksis', function (Blueprint $table) {
            $table->increments('id_kas');
            $table->unsignedInteger('id_cabang');
            $table->unsignedInteger('id_karyawan');
            $table->unsignedInteger('id_faktur')->nullable();
            $table->dateTime('tanggal')->useCurrent();
            $table->enum('jenis', ['masuk', 'keluar', 'setoran']);
            $table->decimal('jumlah', 15, 2);
            $table->string('keterangan', 255)->nullable();

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

            $table->index(['id_cabang', 'tanggal']);
        });

        DB::statement("
            ALTER TABLE kas_transaksis
            ADD CONSTRAINT chk_kas_jumlah_positive
            CHECK (jumlah > 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_transaksis');
    }
};
