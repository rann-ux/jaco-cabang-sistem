<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produks', function (Blueprint $table) {
            $table->string('id_produk', 20)->primary();
            $table->string('kode_produk', 30)->unique();
            $table->string('nama_produk');
            $table->string('kategori');
            $table->decimal('harga', 15, 2);
            $table->unsignedInteger('masa_garansi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produks');
    }
};