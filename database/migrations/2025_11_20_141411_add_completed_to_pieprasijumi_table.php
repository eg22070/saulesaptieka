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
        Schema::table('pieprasijumi', function (Blueprint $table) {
            $table->boolean('completed')->default(false); // or true if you want default completed
        });
    }

    public function down()
    {
        Schema::table('pieprasijumi', function (Blueprint $table) {
            $table->dropColumn('completed');
        });
    }
};
