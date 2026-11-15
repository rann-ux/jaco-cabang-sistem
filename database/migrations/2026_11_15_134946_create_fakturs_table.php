<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fakturs', function (Blueprint $table) {
            $table->string('id_faktur', 20)->primary();
            $table->string('no_faktur', 30)->unique();
            $table->date('tanggal');
            $table->decimal('total', 15, 2);
            $table->string('status', 30);

            $table->string('id_cabang', 20);
            $table->string('id_pelanggan', 20);
            $table->string('id_karyawan', 20);
            $table->string('id_promo', 20)->nullable();

            $table->foreign('id_cabang')
                ->references('id_cabang')
                ->on('cabangs')
                ->restrictOnDelete();

            $table->foreign('id_pelanggan')
                ->references('id_pelanggan')
                ->on('pelanggans')
                ->restrictOnDelete();

            $table->foreign('id_karyawan')
                ->references('id_karyawan')
                ->on('karyawans')
                ->restrictOnDelete();

            $table->foreign('id_promo')
                ->references('id_promo')
                ->on('promos')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fakturs');
    }
};