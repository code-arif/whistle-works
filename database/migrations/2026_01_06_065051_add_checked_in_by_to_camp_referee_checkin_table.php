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
        Schema::table('camp_referee_checkins', function (Blueprint $table) {
            $table->string('checked_in_by')->nullable()->after('checked_in_at'); // 'self' or 'director'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('camp_referee_checkins', function (Blueprint $table) {
            $table->dropColumn('checked_in_by');
        });
    }
};
