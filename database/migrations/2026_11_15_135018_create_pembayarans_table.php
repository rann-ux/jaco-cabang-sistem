<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->string('id_bayar', 20)->primary();
            $table->string('id_faktur', 20);
            $table->string('metode', 30);
            $table->decimal('jumlah', 15, 2);
            $table->date('tanggal');
            $table->boolean('status_verifikasi_sales')->default(false);
            $table->boolean('status_cek_pusat')->default(false);
            $table->date('tgl_cek_pusat')->nullable();

            $table->foreign('id_faktur')
                ->references('id_faktur')
                ->on('fakturs')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayarans');
    }
};