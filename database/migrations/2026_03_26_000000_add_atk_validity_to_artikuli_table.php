<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('artikuli', function (Blueprint $table) {
            // 90 or 365 (1 gads). Stored as days to keep logic simple.
            $table->unsignedSmallInteger('atk_validity_days')->nullable()->after('atk');
        });
    }

    public function down()
    {
        Schema::table('artikuli', function (Blueprint $table) {
            $table->dropColumn('atk_validity_days');
        });
    }
};

