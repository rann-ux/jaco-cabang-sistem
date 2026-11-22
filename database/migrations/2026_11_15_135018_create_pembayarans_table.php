<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->increments('id_bayar');
            $table->unsignedInteger('id_faktur');
            $table->enum('metode', [
                'tunai',
                'debit',
                'transfer',
                'cicilan',
            ]);
            $table->decimal('jumlah', 15, 2);
            $table->dateTime('tanggal')->useCurrent();
            $table->boolean('status_verifikasi_sales')->default(false);
            $table->boolean('status_cek_pusat')->default(false);
            $table->dateTime('tgl_cek_pusat')->nullable();

            $table->foreign('id_faktur')
                ->references('id_faktur')
                ->on('fakturs')
                ->restrictOnDelete();
        });

        DB::statement("
            ALTER TABLE pembayarans
            ADD CONSTRAINT chk_pembayaran_jumlah_positive
            CHECK (jumlah > 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayarans');
    }
};
