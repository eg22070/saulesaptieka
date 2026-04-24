<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('pasutijumu_pieprasijumi', 'aizliegums_by_artikuls')) {
            Schema::table('pasutijumu_pieprasijumi', function (Blueprint $table) {
                $table->json('aizliegums_by_artikuls')->nullable()->after('who_completed');
            });
        }

        if (Schema::hasColumn('pasutijumu_pieprasijumi', 'nedrikst_aizvietot_artikuli_ids')) {
            DB::table('pasutijumu_pieprasijumi')
                ->select(['id', 'nedrikst_aizvietot_artikuli_ids'])
                ->orderBy('id')
                ->chunkById(100, function ($rows): void {
                    foreach ($rows as $row) {
                        $raw = $row->nedrikst_aizvietot_artikuli_ids;
                        if ($raw === null || $raw === '') {
                            continue;
                        }

                        $ids = is_string($raw) ? json_decode($raw, true) : $raw;
                        if (!is_array($ids)) {
                            continue;
                        }

                        $mapped = [];
                        foreach ($ids as $id) {
                            $artikulaId = (int) $id;
                            if ($artikulaId > 0) {
                                $mapped[(string) $artikulaId] = 'Nedrīkst aizvietot';
                            }
                        }

                        DB::table('pasutijumu_pieprasijumi')
                            ->where('id', $row->id)
                            ->update(['aizliegums_by_artikuls' => json_encode($mapped, JSON_UNESCAPED_UNICODE)]);
                    }
                });

            Schema::table('pasutijumu_pieprasijumi', function (Blueprint $table) {
                $table->dropColumn('nedrikst_aizvietot_artikuli_ids');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('pasutijumu_pieprasijumi', 'nedrikst_aizvietot_artikuli_ids')) {
            Schema::table('pasutijumu_pieprasijumi', function (Blueprint $table) {
                $table->json('nedrikst_aizvietot_artikuli_ids')->nullable()->after('who_completed');
            });
        }

        if (Schema::hasColumn('pasutijumu_pieprasijumi', 'aizliegums_by_artikuls')) {
            DB::table('pasutijumu_pieprasijumi')
                ->select(['id', 'aizliegums_by_artikuls'])
                ->orderBy('id')
                ->chunkById(100, function ($rows): void {
                    foreach ($rows as $row) {
                        $raw = $row->aizliegums_by_artikuls;
                        if ($raw === null || $raw === '') {
                            continue;
                        }

                        $map = is_string($raw) ? json_decode($raw, true) : $raw;
                        if (!is_array($map)) {
                            continue;
                        }

                        $ids = [];
                        foreach ($map as $artikulaId => $aizliegums) {
                            if ((string) $aizliegums === 'Nedrīkst aizvietot') {
                                $id = (int) $artikulaId;
                                if ($id > 0) {
                                    $ids[] = $id;
                                }
                            }
                        }

                        DB::table('pasutijumu_pieprasijumi')
                            ->where('id', $row->id)
                            ->update(['nedrikst_aizvietot_artikuli_ids' => json_encode(array_values(array_unique($ids)), JSON_UNESCAPED_UNICODE)]);
                    }
                });

            Schema::table('pasutijumu_pieprasijumi', function (Blueprint $table) {
                $table->dropColumn('aizliegums_by_artikuls');
            });
        }
    }
};
