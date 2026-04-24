<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pasutijumu_pieprasijumi', function (Blueprint $table) {
            $table->json('aizliegums_by_artikuls')->nullable()->after('who_completed');
        });
    }

    public function down(): void
    {
        Schema::table('pasutijumu_pieprasijumi', function (Blueprint $table) {
            $table->dropColumn('aizliegums_by_artikuls');
        });
    }
};
