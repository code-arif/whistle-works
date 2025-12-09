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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referee_assignments');
    }
};
