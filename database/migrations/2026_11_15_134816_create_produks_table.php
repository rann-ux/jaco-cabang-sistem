<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produks', function (Blueprint $table) {
            $table->increments('id_produk');
            $table->string('kode_produk', 30)->unique();
            $table->string('nama_produk', 150);
            $table->string('kategori', 50);
            $table->decimal('harga', 15, 2);
            $table->integer('masa_garansi')->default(0);
        });

        DB::statement("
            ALTER TABLE produks
            ADD CONSTRAINT chk_produk_harga_nonneg
            CHECK (harga >= 0)
        ");

        DB::statement("
            ALTER TABLE produks
            ADD CONSTRAINT chk_produk_masa_garansi_nonneg
            CHECK (masa_garansi >= 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('produks');
    }
};
