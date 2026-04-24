<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pasutijumi', function (Blueprint $table) {
            $table->json('previous_artikuli_ids')->nullable()->after('artikula_id');
        });
    }

    public function down(): void
    {
        Schema::table('pasutijumi', function (Blueprint $table) {
            $table->dropColumn('previous_artikuli_ids');
        });
    }
};
