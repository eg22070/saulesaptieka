<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artikuli', function (Blueprint $table) {
            $table->boolean('without_arst')->default(false)->after('hide_from_farmaceiti');
            $table->boolean('nemedikamenti')->default(false)->after('without_arst');
        });
    }

    public function down(): void
    {
        Schema::table('artikuli', function (Blueprint $table) {
            $table->dropColumn(['without_arst', 'nemedikamenti']);
        });
    }
};
