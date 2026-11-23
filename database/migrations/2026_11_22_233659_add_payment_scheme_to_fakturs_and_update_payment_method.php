<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fakturs', function (Blueprint $table) {
            $table->enum('skema_pembayaran', [
                'sekali_bayar',
                'cicilan',
            ])->default('sekali_bayar')->after('total');
        });

        DB::statement("
            ALTER TABLE pembayarans
            MODIFY metode ENUM('tunai', 'debit', 'transfer') NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE pembayarans
            MODIFY metode ENUM('tunai', 'debit', 'transfer', 'cicilan') NOT NULL
        ");

        Schema::table('fakturs', function (Blueprint $table) {
            $table->dropColumn('skema_pembayaran');
        });
    }
};
