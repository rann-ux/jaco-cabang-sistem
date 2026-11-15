<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_pengantarans', function (Blueprint $table) {
            $table->string('id_po', 20)->primary();
            $table->string('id_faktur', 20)->unique();
            $table->date('tanggal');
            $table->string('status', 30);

            $table->foreign('id_faktur')
                ->references('id_faktur')
                ->on('fakturs')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_pengantarans');
    }
};