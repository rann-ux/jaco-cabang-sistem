<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_fakturs', function (Blueprint $table) {
            $table->string('id_detail', 20)->primary();
            $table->string('id_faktur', 20);
            $table->string('id_produk', 20);
            $table->integer('jumlah');
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('subtotal', 15, 2);

            $table->foreign('id_faktur')
                ->references('id_faktur')
                ->on('fakturs')
                ->restrictOnDelete();

            $table->foreign('id_produk')
                ->references('id_produk')
                ->on('produks')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_fakturs');
    }
};