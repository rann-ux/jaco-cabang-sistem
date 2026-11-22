<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karyawans', function (Blueprint $table) {
            $table->increments('id_karyawan');
            $table->unsignedInteger('id_cabang');
            $table->string('nama', 100);
            $table->enum('peran', [
                'manajer_cabang',
                'sales',
                'admin',
                'gudang',
                'pengantaran',
            ]);
            $table->string('status', 20)->default('aktif');

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