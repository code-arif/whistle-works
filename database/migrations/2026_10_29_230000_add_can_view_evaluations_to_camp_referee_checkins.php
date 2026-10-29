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
        if (Schema::hasTable('camp_referee_checkins')) {
            Schema::table('camp_referee_checkins', function (Blueprint $table) {
                if (!Schema::hasColumn('camp_referee_checkins', 'can_view_evaluations')) {
                    $table->boolean('can_view_evaluations')->default(true)->after('registration_status');
                }
            });
        }

        if (Schema::hasTable('camps')) {
            Schema::table('camps', function (Blueprint $table) {
                if (!Schema::hasColumn('camps', 'publish_evaluations_for_referees')) {
                    $table->boolean('publish_evaluations_for_referees')->default(true)->after('publish_ranking_for_referees');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('camp_referee_checkins')) {
            Schema::table('camp_referee_checkins', function (Blueprint $table) {
                if (Schema::hasColumn('camp_referee_checkins', 'can_view_evaluations')) {
                    $table->dropColumn('can_view_evaluations');
                }
            });
        }

        if (Schema::hasTable('camps')) {
            Schema::table('camps', function (Blueprint $table) {
                if (Schema::hasColumn('camps', 'publish_evaluations_for_referees')) {
                    $table->dropColumn('publish_evaluations_for_referees');
                }
            });
        }
    }
};

