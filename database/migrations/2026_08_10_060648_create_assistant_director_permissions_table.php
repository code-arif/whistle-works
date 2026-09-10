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
        Schema::create('assistant_director_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('director_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assistant_director_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('camp_id')->constrained('camps')->cascadeOnDelete();
            $table->boolean('build_schedule')->default(false);
            $table->boolean('assign_referees')->default(false);
            $table->boolean('publish_camp')->default(false);
            $table->boolean('manage_ranking_reports')->default(false);
            $table->boolean('manage_roster_referees')->default(false);
            $table->boolean('manage_roster_evaluators')->default(false);
            $table->boolean('manage_roster_crews')->default(false);
            $table->boolean('manage_announcements')->default(false);
            $table->timestamps();

            $table->unique(
                ['director_id', 'assistant_director_id', 'camp_id'],
                'assistant_director_permissions_unique'
            );

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistant_director_permissions');
    }
};
