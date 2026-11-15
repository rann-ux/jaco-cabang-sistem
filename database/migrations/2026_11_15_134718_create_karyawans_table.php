<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karyawans', function (Blueprint $table) {
            $table->string('id_karyawan', 20)->primary();
            $table->string('id_cabang', 20);
            $table->string('nama');
            $table->string('peran', 30);
            $table->string('status', 20);

            $table->foreign('id_cabang')
                ->references('id_cabang')
                ->on('cabangs')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karyawans');
    }
};