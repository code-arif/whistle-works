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
        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('ai_chat_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('role', ['user', 'assistant', 'system', 'tool']);
            $table->longText('content')->nullable();
            $table->string('widget_type', 50)->nullable(); // 'chart', 'excel_download', 'table', null
            $table->json('widget_payload')->nullable();
            $table->unsignedInteger('tokens_used')->default(0);
            $table->json('tools_called')->nullable();
            $table->timestamps();

            $table->index('session_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_messages');
    }
};
