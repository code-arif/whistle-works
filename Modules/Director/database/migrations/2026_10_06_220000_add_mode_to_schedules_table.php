<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('schedules', 'mode')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->enum('mode', ['individual', 'crew'])->default('individual')->after('status');
            });
        }

        // Backfill mode for existing schedules based on their game_slots mode
        $schedules = DB::table('schedules')->get();
        foreach ($schedules as $schedule) {
            $slotMode = DB::table('game_slots')
                ->where('schedule_id', $schedule->id)
                ->orderByDesc('id')
                ->value('mode');

            if ($slotMode && in_array($slotMode, ['individual', 'crew'])) {
                DB::table('schedules')->where('id', $schedule->id)->update(['mode' => $slotMode]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('schedules', 'mode')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->dropColumn('mode');
            });
        }
    }
};
