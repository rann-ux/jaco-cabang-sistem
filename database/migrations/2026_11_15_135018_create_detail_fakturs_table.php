<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_fakturs', function (Blueprint $table) {
            $table->increments('id_detail');
            $table->unsignedInteger('id_faktur');
            $table->unsignedInteger('id_produk');
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

        DB::statement("
            ALTER TABLE detail_fakturs
            ADD CONSTRAINT chk_detail_jumlah_positive
            CHECK (jumlah > 0)
        ");

        DB::statement("
            ALTER TABLE detail_fakturs
            ADD CONSTRAINT chk_detail_harga_nonneg
            CHECK (harga_satuan >= 0)
        ");

        DB::statement("
            ALTER TABLE detail_fakturs
            ADD CONSTRAINT chk_detail_subtotal
            CHECK (subtotal = jumlah * harga_satuan)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_fakturs');
    }
};
