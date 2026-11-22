<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penerimaan_barangs', function (Blueprint $table) {
            $table->increments('id_penerimaan');
            $table->unsignedInteger('id_cabang');
            $table->unsignedInteger('id_produk');
            $table->unsignedInteger('id_karyawan');
            $table->integer('jumlah');
            $table->dateTime('tanggal')->useCurrent();
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

            $table->unique([
                'no_dokumen_pusat',
                'id_cabang',
                'id_produk',
            ]);
        });

        DB::statement("
            ALTER TABLE penerimaan_barangs
            ADD CONSTRAINT chk_penerimaan_jumlah
            CHECK (jumlah > 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('penerimaan_barangs');
    }
};
