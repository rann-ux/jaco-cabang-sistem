<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fakturs', function (Blueprint $table) {
            $table->increments('id_faktur');
            $table->string('no_faktur', 30)->unique();

            $table->unsignedInteger('id_cabang');
            $table->unsignedInteger('id_pelanggan');
            $table->unsignedInteger('id_karyawan');
            $table->unsignedInteger('id_promo')->nullable();

            $table->dateTime('tanggal')->useCurrent();
            $table->decimal('total', 15, 2)->default(0);
            $table->string('status', 20)->default('belum_dibayar');

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

            $table->index(['id_cabang', 'tanggal']);
        });

        DB::statement("
            ALTER TABLE fakturs
            ADD CONSTRAINT chk_faktur_total
            CHECK (total >= 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('fakturs');
    }
};
