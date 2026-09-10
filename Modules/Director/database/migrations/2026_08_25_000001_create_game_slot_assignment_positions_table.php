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
        Schema::create('game_slot_assignment_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camp_id')->constrained('camps')->onDelete('cascade');
            $table->foreignId('game_slot_id')->constrained('game_slots')->onDelete('cascade');
            $table->foreignId('game_slot_assignment_id')->nullable()->constrained('game_slot_assignments')->onDelete('set null');
            $table->string('position');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_slot_assignment_positions');
    }
};
