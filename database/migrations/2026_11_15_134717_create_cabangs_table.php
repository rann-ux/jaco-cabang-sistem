<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cabangs', function (Blueprint $table) {
            $table->string('id_cabang', 20)->primary();
            $table->string('nama_cabang');
            $table->string('jenis', 20);
            $table->text('alamat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cabangs');
    }
};