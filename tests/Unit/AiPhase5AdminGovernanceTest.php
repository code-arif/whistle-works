<?php

namespace Tests\Unit;

use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\AiSetting;
use App\Models\User;
use App\Models\UserAiQuota;
use App\Services\Admin\AiGovernanceService;
use Tests\TestCase;

class AiPhase5AdminGovernanceTest extends TestCase
{
    protected AiGovernanceService $service;
    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AiGovernanceService();

        $this->testUser = User::firstOrCreate(
            ['email' => 'admin_governance_test@whistleworks.org'],
            [
                'first_name' => 'Governance',
                'last_name' => 'Tester',
                'username' => 'gov_tester',
                'slug' => 'gov-tester',
            ]
        );
    }

    public function test_get_overview_metrics(): void
    {
        $quota = $this->testUser->getOrCreateAiQuota();
        $quota->queries_used_this_month = 12;
        $quota->tokens_used_this_month = 1500;
        $quota->save();

        $overview = $this->service->getOverviewMetrics();

        $this->assertArrayHasKey('metrics', $overview);
        $this->assertArrayHasKey('chart_data', $overview);
        $this->assertArrayHasKey('top_users', $overview);
        $this->assertCount(14, $overview['chart_data']['labels']);
        $this->assertGreaterThanOrEqual(12, $overview['metrics']['total_queries_month']);
        $this->assertGreaterThanOrEqual(1500, $overview['metrics']['total_tokens_month']);
    }

    public function test_get_and_update_ai_settings(): void
    {
        $this->service->updateAiSettings([
            'ai_provider' => 'gemini',
            'gemini_model' => 'gemini-1.5-flash',
            'gemini_api_key' => 'AIzaSyFakeTestKey1234567890',
            'default_monthly_quota' => 100,
            'enable_ai_coach' => true,
        ]);

        $settings = $this->service->getAiSettings();

        $this->assertEquals('gemini', $settings['ai_provider']);
        $this->assertEquals('gemini-1.5-flash', $settings['gemini_model']);
        $this->assertEquals(100, $settings['default_monthly_quota']);
        $this->assertTrue($settings['enable_ai_coach']);
        $this->assertTrue($settings['has_gemini_key']);
        $this->assertStringContainsString('***', $settings['gemini_api_key']); // Verify masked key

        // Restore default provider
        $this->service->updateAiSettings([
            'ai_provider' => 'openai',
            'default_monthly_quota' => 50,
        ]);
    }

    public function test_get_user_quotas_pagination(): void
    {
        $paginator = $this->service->getUserQuotas(['search' => 'Governance']);
        $this->assertGreaterThanOrEqual(1, $paginator->total());
        $this->assertEquals('Governance', $paginator->items()[0]->user->first_name);
    }

    public function test_update_user_quota_and_reset(): void
    {
        $updatedQuota = $this->service->updateUserQuota($this->testUser->id, [
            'monthly_query_limit' => 250,
            'plan_tier' => 'pro',
            'queries_used_this_month' => 45,
            'reset_usage' => true,
        ]);

        $this->assertEquals(250, $updatedQuota->monthly_query_limit);
        $this->assertEquals('pro', $updatedQuota->plan_tier);
        $this->assertEquals(0, $updatedQuota->queries_used_this_month);
    }

    public function test_toggle_user_block_and_unblock(): void
    {
        // Block user
        $blocked = $this->service->toggleUserBlock($this->testUser->id, true, 'Violation of terms');
        $this->assertTrue($blocked->is_blocked);
        $this->assertEquals('Violation of terms', $blocked->block_reason);

        // Unblock user
        $unblocked = $this->service->toggleUserBlock($this->testUser->id, false);
        $this->assertFalse($unblocked->is_blocked);
        $this->assertNull($unblocked->block_reason);
    }

    public function test_get_user_ai_history(): void
    {
        $session = AiChatSession::create([
            'user_id' => $this->testUser->id,
            'title' => 'Audit Test Session',
        ]);

        AiChatMessage::create([
            'session_id' => $session->id,
            'user_id' => $this->testUser->id,
            'role' => 'user',
            'content' => 'Show me referee rankings',
        ]);

        AiChatMessage::create([
            'session_id' => $session->id,
            'user_id' => $this->testUser->id,
            'role' => 'assistant',
            'content' => 'Here are the rankings.',
            'tokens_used' => 50,
            'tools_called' => ['compile_camp_rankings_excel'],
        ]);

        $history = $this->service->getUserAiHistory($this->testUser->id, 10);
        $this->assertGreaterThanOrEqual(2, $history->total());
        $this->assertContains('compile_camp_rankings_excel', $history->items()[0]->tools_called ?? []);
    }
}
