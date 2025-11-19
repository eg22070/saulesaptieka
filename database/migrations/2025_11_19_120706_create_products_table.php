<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('artikuli', function (Blueprint $table) {
            $table->id();
        $table->string('nosaukums');
        $table->string('id_numurs');
        $table->string('valsts');
        $table->string('snn');
        $table->string('analogs');
        $table->text('atzimes')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
