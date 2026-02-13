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
        Schema::create('delete_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');  // original Requests id

            // copy all columns from `requests` you want to track:
            $table->string('datums')->nullable();
            $table->unsignedBigInteger('aptiekas_id')->nullable();
            $table->unsignedBigInteger('artikula_id')->nullable();
            $table->integer('daudzums')->nullable();
            $table->integer('izrakstitais_daudzums')->nullable();
            $table->string('statuss')->nullable();
            $table->string('aizliegums')->nullable();
            $table->string('iepircejs')->nullable();
            $table->text('piegades_datums')->nullable();
            $table->text('piezimes')->nullable();
            $table->boolean('completed')->default(false);

            $table->timestamp('deleted_at'); // when it was deleted
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delete_histories');
    }
};
