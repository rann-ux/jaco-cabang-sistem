<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promos', function (Blueprint $table) {
            $table->increments('id_promo');
            $table->string('kode_promo', 30)->unique();
            $table->string('deskripsi', 255);
            $table->enum('jenis_potongan', ['persen', 'nominal']);
            $table->decimal('nilai', 15, 2);
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->string('status', 20)->default('aktif');
        });

        DB::statement("
            ALTER TABLE promos
            ADD CONSTRAINT chk_promo_nilai_positive
            CHECK (nilai > 0)
        ");

        DB::statement("
            ALTER TABLE promos
            ADD CONSTRAINT chk_promo_persen_max
            CHECK (jenis_potongan != 'persen' OR nilai <= 100)
        ");

        DB::statement("
            ALTER TABLE promos
            ADD CONSTRAINT chk_promo_tanggal
            CHECK (tgl_selesai >= tgl_mulai)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('promos');
    }
};
