<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 1. Create coupon_referees pivot table (many-to-many).
     * 2. Drop the old single referee_id column from coupons.
     */
    public function up(): void
    {
        // 1. Create pivot table
        Schema::create('coupon_referees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->onDelete('cascade');
            $table->foreignId('referee_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['coupon_id', 'referee_id'], 'uq_coupon_referees');
        });

        // 2. Drop old single-column FK from coupons
        Schema::table('coupons', function (Blueprint $table) {
            // Drop the foreign key first, then the column
            if (Schema::hasColumn('coupons', 'referee_id')) {
                $table->dropForeign(['referee_id']);
                $table->dropColumn('referee_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the referee_id column
        Schema::table('coupons', function (Blueprint $table) {
            $table->foreignId('referee_id')->nullable()->constrained('users')->onDelete('cascade');
        });

        Schema::dropIfExists('coupon_referees');
    }
};
