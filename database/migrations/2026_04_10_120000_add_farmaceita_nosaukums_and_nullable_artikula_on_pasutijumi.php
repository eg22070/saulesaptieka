<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pasutijumi', function (Blueprint $table) {
            if (! Schema::hasColumn('pasutijumi', 'farmaceita_nosaukums')) {
                $table->string('farmaceita_nosaukums', 512)->nullable()->after('artikula_id');
            }
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            Schema::table('pasutijumi', function (Blueprint $table) {
                $table->dropForeign(['artikula_id']);
            });
        }

        Schema::table('pasutijumi', function (Blueprint $table) {
            $table->unsignedBigInteger('artikula_id')->nullable()->change();
        });

        if ($driver === 'mysql') {
            Schema::table('pasutijumi', function (Blueprint $table) {
                $table->foreign('artikula_id')->references('id')->on('artikuli')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('pasutijumi', function (Blueprint $table) {
            if (Schema::hasColumn('pasutijumi', 'farmaceita_nosaukums')) {
                $table->dropColumn('farmaceita_nosaukums');
            }
        });
    }
};
