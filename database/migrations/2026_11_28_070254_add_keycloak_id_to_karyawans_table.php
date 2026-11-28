<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karyawans', function (Blueprint $table) {
            $table->string('keycloak_id', 36)
                ->nullable()
                ->unique()
                ->after('id_karyawan');
        });
    }

    public function down(): void
    {
        Schema::table('karyawans', function (Blueprint $table) {
            $table->dropUnique(['keycloak_id']);
            $table->dropColumn('keycloak_id');
        });
    }
};