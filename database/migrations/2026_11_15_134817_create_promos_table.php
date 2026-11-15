<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promos', function (Blueprint $table) {
            $table->string('id_promo', 20)->primary();
            $table->string('kode_promo', 30)->unique();
            $table->string('deskripsi');
            $table->string('jenis_potongan', 30);
            $table->decimal('nilai', 15, 2);
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->string('status', 20);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promos');
    }
};