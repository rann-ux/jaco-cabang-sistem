<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stoks', function (Blueprint $table) {
            $table->string('id_stok', 20)->primary();
            $table->string('id_cabang', 20);
            $table->string('id_produk', 20);
            $table->integer('jumlah');
            $table->integer('batas_minimum');
            $table->date('tgl_update');

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
    }

    public function down(): void
    {
        Schema::dropIfExists('stoks');
    }
};