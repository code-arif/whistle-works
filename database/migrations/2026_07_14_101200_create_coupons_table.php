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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['fixed', 'percentage'])->default('percentage');
            $table->decimal('discount_value', 8, 2);
            $table->integer('max_uses')->nullable();
            $table->integer('used_count')->default(0);

            // Scope fields (nullable means global or not scoped)
            $table->foreignId('camp_id')->nullable()->constrained('camps')->onDelete('cascade');
            $table->foreignId('director_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('referee_id')->nullable()->constrained('users')->onDelete('cascade');

            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
