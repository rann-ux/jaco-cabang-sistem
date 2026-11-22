<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stoks', function (Blueprint $table) {
            $table->increments('id_stok');
            $table->unsignedInteger('id_cabang');
            $table->unsignedInteger('id_produk');
            $table->integer('jumlah')->default(0);
            $table->integer('batas_minimum')->default(0);
            $table->dateTime('tgl_update')
                ->useCurrent()
                ->useCurrentOnUpdate();

            $table->foreign('id_cabang')
                ->references('id_cabang')
                ->on('cabangs')
                ->restrictOnDelete();

            $table->foreign('id_produk')
                ->references('id_produk')
                ->on('produks')
                ->restrictOnDelete();

            $table->unique(['id_cabang', 'id_produk']);
        });

        DB::statement("
            ALTER TABLE stoks
            ADD CONSTRAINT chk_stok_jumlah_nonneg
            CHECK (jumlah >= 0)
        ");

        DB::statement("
            ALTER TABLE stoks
            ADD CONSTRAINT chk_stok_minimum_nonneg
            CHECK (batas_minimum >= 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('stoks');
    }
};
