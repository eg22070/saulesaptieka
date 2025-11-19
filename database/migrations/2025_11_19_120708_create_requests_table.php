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
        Schema::create('pieprasijumi', function (Blueprint $table) {
            $table->id();
            $table->date('datums');
            $table->foreignId('aptiekas_id')->constrained()->onDelete('cascade');
            $table->foreignId('artikuli_id')->constrained()->onDelete('cascade');
            $table->integer('daudzums');
            $table->integer('izrakstitais_daudzums');
            $table->date('pazinojuma_datums');
            $table->enum('statuss', ['Pasūtīts', 'Atcelts', 'Mainīta piegāde', 'Ir noliktavā', 'Daļēji atlikumā']);
            $table->enum('aizliegums', ['Drīkst aizvietot', 'Nedrīkst aizvietot', 'NVD', 'Stacionārs']);
            $table->enum('Iepircējs', ['Artūrs', 'Liene', 'Anna', 'Iveta']);
            $table->date('piegades_datums')->nullable();
            $table->text('piezimes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
