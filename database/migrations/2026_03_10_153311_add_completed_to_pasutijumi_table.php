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
        Schema::table('pasutijumi', function (Blueprint $table) {
            $table->unsignedBigInteger('who_completed')->nullable();
            $table->timestamp('completed_at')->nullable()->after('who_completed');

            $table->foreign('who_completed')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pasutijumi', function (Blueprint $table) {
            $table->dropForeign(['who_completed']);
            $table->dropColumn(['who_completed', 'completed_at']);
        });
    }
};
