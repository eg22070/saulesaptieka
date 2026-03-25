<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('artikuli', function (Blueprint $table) {
            $table->string('atk', 50)->nullable()->after('id_numurs');     // ATĶ
            $table->text('info')->nullable()->after('atk');               // Info
            $table->text('pielietojums')->nullable()->after('info');      // Pielietojums

            // visibility flags
            $table->boolean('hide_from_kruzes')->default(false)->after('pielietojums');
            $table->boolean('hide_from_farmaceiti')->default(false)->after('hide_from_kruzes');
        });
    }

    public function down()
    {
        Schema::table('artikuli', function (Blueprint $table) {
            $table->dropColumn(['atk', 'info', 'pielietojums', 'hide_from_kruzes', 'hide_from_farmaceiti']);
        });
    }
};
