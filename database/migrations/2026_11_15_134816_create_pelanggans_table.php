<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelanggans', function (Blueprint $table) {
            $table->string('id_pelanggan', 20)->primary();
            $table->string('nama');
            $table->text('alamat');
            $table->string('no_hp', 20);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggans');
    }
};