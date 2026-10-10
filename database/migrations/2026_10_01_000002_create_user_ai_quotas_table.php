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
        Schema::create('user_ai_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('plan_tier', 50)->default('free');
            $table->unsignedInteger('monthly_query_limit')->default(50);
            $table->unsignedInteger('queries_used_this_month')->default(0);
            $table->unsignedBigInteger('tokens_used_this_month')->default(0);
            $table->boolean('is_blocked')->default(false);
            $table->string('block_reason', 255)->nullable();
            $table->timestamp('quota_resets_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('is_blocked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_ai_quotas');
    }
};
