<?php

namespace Tests\Unit;

use App\Http\Middleware\CheckAiUsageQuota;
use App\Models\AiSetting;
use App\Models\User;
use App\Models\UserAiQuota;
use Illuminate\Http\Request;
use Modules\Director\app\Services\Ai\AiToolRegistry;
use Modules\Director\app\Services\Ai\Contracts\AiToolInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AiPhase2EngineTest extends TestCase
{
    public function test_tool_registry_registration_and_schema_generation(): void
    {
        $registry = new AiToolRegistry();

        $mockTool = new class implements AiToolInterface {
            public function getName(): string { return 'mock_analytics_tool'; }
            public function getDescription(): string { return 'Mock tool for test verification'; }
            public function getParametersSchema(): array {
                return [
                    'type' => 'object',
                    'properties' => [
                        'referee_id' => ['type' => 'integer', 'description' => 'Target referee ID'],
                    ],
                    'required' => ['referee_id'],
                ];
            }
            public function execute(array $arguments, int $userId): array {
                return [
                    'result' => ['score' => 9.5, 'referee_id' => $arguments['referee_id']],
                    'widget_type' => 'chart',
                    'widget_payload' => ['score' => 9.5],
                    'summary' => 'Referee scored 9.5',
                ];
            }
        };

        $registry->register($mockTool);
        $this->assertTrue($registry->hasTool('mock_analytics_tool'));

        $schemas = $registry->getToolsSchema();
        $this->assertGreaterThanOrEqual(7, count($schemas));
        $this->assertContains('mock_analytics_tool', array_column($schemas, 'name'));

        $execResult = $registry->executeTool('mock_analytics_tool', ['referee_id' => 42], 1);
        $this->assertEquals('chart', $execResult['widget_type']);
        $this->assertEquals(9.5, $execResult['result']['score']);
    }

    public function test_ai_usage_quota_middleware_blocks_exceeded_quota(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user, 'api');

        $quota = $user->getOrCreateAiQuota();
        $quota->monthly_query_limit = 5;
        $quota->queries_used_this_month = 5;
        $quota->is_blocked = false;
        $quota->quota_resets_at = now()->addDays(15);
        $quota->save();

        $middleware = new CheckAiUsageQuota();
        $request = Request::create('/api/v1/director/ai/chat', 'POST');

        $response = $middleware->handle($request, function () {
            return new Response('OK', 200);
        });

        $this->assertEquals(429, $response->getStatusCode());
    }

    public function test_ai_usage_quota_middleware_blocks_restricted_users(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user, 'api');

        $quota = $user->getOrCreateAiQuota();
        $quota->is_blocked = true;
        $quota->block_reason = 'Suspicious activity';
        $quota->save();

        $middleware = new CheckAiUsageQuota();
        $request = Request::create('/api/v1/director/ai/chat', 'POST');

        $response = $middleware->handle($request, function () {
            return new Response('OK', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());

        // Restore unblocked status
        $quota->is_blocked = false;
        $quota->save();
    }

    public function test_ai_usage_quota_middleware_blocks_when_ai_disabled(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user, 'api');

        AiSetting::setValue('enable_ai_coach', '0');

        $middleware = new CheckAiUsageQuota();
        $request = Request::create('/api/v1/director/ai/chat', 'POST');

        $response = $middleware->handle($request, function () {
            return new Response('OK', 200);
        });

        $this->assertEquals(503, $response->getStatusCode());

        // Re-enable
        AiSetting::setValue('enable_ai_coach', '1');
    }

    public function test_ai_usage_quota_middleware_allows_authorized_users(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user, 'api');

        $quota = $user->getOrCreateAiQuota();
        $quota->monthly_query_limit = 50;
        $quota->queries_used_this_month = 0;
        $quota->is_blocked = false;
        $quota->save();

        $middleware = new CheckAiUsageQuota();
        $request = Request::create('/api/v1/director/ai/chat', 'POST');

        $response = $middleware->handle($request, function () {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }
}
