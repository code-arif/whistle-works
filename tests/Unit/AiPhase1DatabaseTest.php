<?php

namespace Tests\Unit;

use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\AiSetting;
use App\Models\User;
use App\Models\UserAiQuota;
use Tests\TestCase;

class AiPhase1DatabaseTest extends TestCase
{
    public function test_ai_settings_getter_and_setter(): void
    {
        AiSetting::setValue('test_key', 'test_value_secret', true);
        $this->assertEquals('test_value_secret', AiSetting::getValue('test_key'));

        $setting = AiSetting::where('key_name', 'test_key')->first();
        $this->assertTrue($setting->is_encrypted);
        $this->assertNotEquals('test_value_secret', $setting->key_value);

        $setting->delete();
    }

    public function test_user_ai_quota_creation_and_check(): void
    {
        $user = User::first() ?? User::factory()->create();

        $quota = $user->getOrCreateAiQuota();
        $quota->queries_used_this_month = 0;
        $quota->tokens_used_this_month = 0;
        $quota->save();

        $this->assertInstanceOf(UserAiQuota::class, $quota);
        $this->assertTrue($quota->canPerformQuery());
        $this->assertGreaterThan(0, $quota->remaining_queries);

        $quota->recordUsage(150);
        $this->assertEquals(1, $quota->fresh()->queries_used_this_month);
        $this->assertEquals(150, $quota->fresh()->tokens_used_this_month);
    }

    public function test_ai_session_and_message_lifecycle(): void
    {
        $user = User::first() ?? User::factory()->create();

        $session = AiChatSession::create([
            'user_id' => $user->id,
            'title' => 'Camp A Analytics',
        ]);

        $this->assertNotEmpty($session->session_uuid);
        $this->assertNotNull($session->last_interaction_at);

        $message = AiChatMessage::create([
            'session_id' => $session->id,
            'user_id' => $user->id,
            'role' => 'assistant',
            'content' => 'Here is your evaluation chart.',
            'widget_type' => 'chart',
            'widget_payload' => ['chart_type' => 'line', 'labels' => ['2023', '2024', '2025']],
            'tokens_used' => 200,
            'tools_called' => ['get_referee_evaluations'],
        ]);

        $this->assertEquals('chart', $message->widget_type);
        $this->assertIsArray($message->widget_payload);
        $this->assertIsArray($message->tools_called);

        $this->assertCount(1, $session->fresh()->messages);

        // Delete session and assert cascade delete of messages
        $session->delete();
        $this->assertNull(AiChatMessage::find($message->id));
    }
}
