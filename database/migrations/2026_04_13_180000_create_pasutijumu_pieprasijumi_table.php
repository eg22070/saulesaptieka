<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pasutijumu_pieprasijumi', function (Blueprint $table) {
            $table->id();
            $table->date('datums');
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('who_completed')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('pasutijumi', function (Blueprint $table) {
            $table->foreignId('pieprasijuma_id')
                ->nullable()
                ->after('hide_from_visiem')
                ->constrained('pasutijumu_pieprasijumi')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pasutijumi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pieprasijuma_id');
        });

        Schema::dropIfExists('pasutijumu_pieprasijumi');
    }
};
