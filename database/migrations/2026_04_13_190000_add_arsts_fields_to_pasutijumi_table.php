<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pasutijumi', function (Blueprint $table) {
            $table->string('arstniecibas_iestade')->nullable()->after('talrunis_epasts');
            $table->string('arsts')->nullable()->after('arstniecibas_iestade');
        });
    }

    public function down(): void
    {
        Schema::table('pasutijumi', function (Blueprint $table) {
            $table->dropColumn(['arstniecibas_iestade', 'arsts']);
        });
    }
};
