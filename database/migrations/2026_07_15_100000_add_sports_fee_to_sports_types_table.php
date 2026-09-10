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
        Schema::table('sports_types', function (Blueprint $table) {
            $table->decimal('sports_fee', 10, 2)->default(0)->after('icon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sports_types', function (Blueprint $table) {
            $table->dropColumn('sports_fee');
        });
    }
};
