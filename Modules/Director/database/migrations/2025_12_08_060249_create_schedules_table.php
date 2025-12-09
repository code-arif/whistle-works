<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Schedules table
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camp_id')->constrained()->onDelete('cascade');
            $table->integer('game_duration'); // in minutes
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();
        });

        // Schedule locations (courts)
        Schema::create('schedule_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->onDelete('cascade');
            $table->string('location_name');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('court_count')->default(1);
            $table->timestamps();
        });

        // Daily time ranges
        Schema::create('schedule_time_ranges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });

        // Game slots
        Schema::create('game_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->onDelete('cascade');
            $table->foreignId('schedule_location_id')->constrained()->onDelete('cascade');
            $table->date('game_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('court_name'); // Court 1, Court 2, etc.
            $table->integer('court_number');
            $table->enum('status', ['available', 'assigned', 'completed'])->default('available');
            $table->boolean('is_block')->default(false);
            $table->timestamps();
        });

        // Referee assignments
        Schema::create('referee_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_slot_id')->constrained()->onDelete('cascade');
            $table->foreignId('referee_id')->constrained('users')->onDelete('cascade');
            $table->enum('assignment_type', ['manual', 'auto'])->default('manual');
            $table->timestamps();

            // Prevent duplicate assignments
            $table->unique(['game_slot_id', 'referee_id']);
        });

        // Referee check-ins for camps
        Schema::create('camp_referee_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camp_id')->constrained()->onDelete('cascade');
            $table->foreignId('referee_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('checked_in_at');
            $table->timestamps();

            $table->unique(['camp_id', 'referee_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('referee_assignments');
        Schema::dropIfExists('game_slots');
        Schema::dropIfExists('schedule_time_ranges');
        Schema::dropIfExists('schedule_locations');
        Schema::dropIfExists('camp_referee_checkins');
        Schema::dropIfExists('schedules');
    }
};
