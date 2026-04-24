<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pasutijumu_pieprasijuma_brivie_artikuli', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pieprasijuma_id')
                ->constrained('pasutijumu_pieprasijumi')
                ->cascadeOnDelete();
            $table->foreignId('artikula_id')->constrained('artikuli')->cascadeOnDelete();
            $table->decimal('skaits', 10, 2);
            $table->string('arstniecibas_iestade')->nullable();
            $table->string('arsts')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pasutijumu_pieprasijuma_brivie_artikuli');
    }
};
