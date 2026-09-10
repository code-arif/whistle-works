<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tracks one-time per-referee coupon usage.
     */
    public function up(): void
    {
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->onDelete('cascade');
            $table->foreignId('referee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('camp_id')->constrained('camps')->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained('camp_payments')->onDelete('set null');
            $table->timestamp('used_at');
            $table->timestamps();

            // A referee can use a specific coupon only once (regardless of camp)
            $table->unique(['coupon_id', 'referee_id'], 'uq_coupon_referee_once');
            // Index for quick lookups
            $table->index(['coupon_id', 'referee_id', 'camp_id'], 'idx_coupon_referee_camp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};
