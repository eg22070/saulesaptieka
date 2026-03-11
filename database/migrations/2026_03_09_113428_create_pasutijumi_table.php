<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pasutijumi', function (Blueprint $table) {
            $table->id();
            $table->date('datums')->nullable;
            $table->unsignedBigInteger('artikula_id'); // FK to artikuli (Product)
            $table->integer('skaits')->nullable(false);
            $table->string('pasutijuma_numurs')->nullable();
            $table->string('receptes_numurs')->nullable();
            $table->string('vards_uzvards')->nullable(false);
            $table->string('talrunis_epasts')->nullable();
            $table->date('pasutijuma_datums')->nullable();
            $table->text('komentari')->nullable();
            $table->string('statuss', 32)->default('neizpildits')->after('komentari');
            $table->timestamps();
            $table->foreign('artikula_id')->references('id')->on('artikuli')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pasutijumi');
    }
};
