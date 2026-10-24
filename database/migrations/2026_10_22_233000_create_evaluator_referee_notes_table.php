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
        Schema::create('evaluator_referee_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('camp_id')->constrained('camps')->onDelete('cascade');
            $table->foreignId('referee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('game_slot_id')->nullable()->constrained('game_slots')->onDelete('cascade');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['evaluator_id', 'camp_id', 'referee_id', 'game_slot_id'], 'eval_camp_ref_slot_note_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluator_referee_notes');
    }
};
