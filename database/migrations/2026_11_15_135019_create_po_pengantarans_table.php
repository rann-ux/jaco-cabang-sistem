<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_pengantarans', function (Blueprint $table) {
            $table->increments('id_po');
            $table->unsignedInteger('id_faktur')->unique();
            $table->date('tanggal');
            $table->string('status', 20)->default('baru');

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
