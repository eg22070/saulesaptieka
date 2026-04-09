<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pasutijumi', function (Blueprint $table) {
            $table->unsignedBigInteger('who_cancelled')->nullable()->after('who_completed');
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');

            $table->foreign('who_cancelled')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pasutijumi', function (Blueprint $table) {
            $table->dropForeign(['who_cancelled']);
            $table->dropColumn(['who_cancelled', 'cancelled_at']);
        });
    }
};

