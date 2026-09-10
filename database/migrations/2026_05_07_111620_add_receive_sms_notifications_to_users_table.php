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
        if (! Schema::hasColumn('users', 'receive_sms_notifications')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('receive_sms_notifications')
                    ->default(false)
                    ->after('phone');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'receive_sms_notifications')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('receive_sms_notifications');
            });
        }
    }
};
