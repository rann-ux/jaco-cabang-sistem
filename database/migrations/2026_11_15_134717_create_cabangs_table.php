<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cabangs', function (Blueprint $table) {
            $table->increments('id_cabang');
            $table->string('nama_cabang', 100);
            $table->enum('jenis', ['showroom', 'kantor', 'gudang']);
            $table->text('alamat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cabangs');
    }
};