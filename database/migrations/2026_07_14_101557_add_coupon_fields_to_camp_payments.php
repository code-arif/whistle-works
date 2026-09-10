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
        Schema::table('camp_payment_attempts', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->onDelete('set null');
            $table->decimal('discount_amount', 8, 2)->default(0);
            $table->decimal('admin_fee', 8, 2)->default(0);
            $table->decimal('director_amount', 8, 2)->default(0);
        });

        Schema::table('camp_payments', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->onDelete('set null');
            $table->decimal('discount_amount', 8, 2)->default(0);
            $table->decimal('admin_fee', 8, 2)->default(0);
            $table->decimal('director_amount', 8, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('camp_payment_attempts', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn(['coupon_id', 'discount_amount', 'admin_fee', 'director_amount']);
        });

        Schema::table('camp_payments', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn(['coupon_id', 'discount_amount', 'admin_fee', 'director_amount']);
        });
    }
};
