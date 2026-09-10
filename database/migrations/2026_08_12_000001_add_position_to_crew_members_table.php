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
        if (Schema::hasTable('crew_members') && !Schema::hasColumn('crew_members', 'position')) {
            Schema::table('crew_members', function (Blueprint $table) {
                $table->string('position')->nullable()->after('referee_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('crew_members') && Schema::hasColumn('crew_members', 'position')) {
            Schema::table('crew_members', function (Blueprint $table) {
                $table->dropColumn('position');
            });
        }
    }
};
