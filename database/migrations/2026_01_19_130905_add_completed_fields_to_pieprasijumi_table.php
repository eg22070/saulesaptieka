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
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('who_completed')->nullable();
            
            // Optional: Add foreign key constraint if you want strict integrity
            $table->foreign('who_completed')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('pieprasijumi', function (Blueprint $table) {
            $table->dropForeign(['who_completed']); // Remove this line if you didn't add the constraint above
            $table->dropColumn(['completed_at', 'who_completed']);
        });
    }
};
