<?php

namespace Tests\Unit;

use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\User;
use App\Models\UserAiQuota;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Director\app\Services\Ai\AiEngineService;
use Modules\Director\app\Services\Ai\AiToolRegistry;
use Modules\Director\app\Services\Ai\Contracts\AiProviderInterface;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AiPhase4ApiEndpointsTest extends TestCase
{
    protected User $directorUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure director role exists
        Role::firstOrCreate(['name' => 'director', 'guard_name' => 'api']);

        $this->directorUser = User::firstOrCreate(
            ['email' => 'test_director_ai@whistleworks.org'],
            [
                'first_name' => 'Test',
                'last_name' => 'Director',
                'username' => 'director_ai_test',
                'slug' => 'director-ai-test',
            ]
        );

        if (!$this->directorUser->hasRole('director', 'api')) {
            $this->directorUser->assignRole('director');
        }

        $quota = $this->directorUser->getOrCreateAiQuota();
        $quota->monthly_query_limit = 50;
        $quota->queries_used_this_month = 0;
        $quota->is_blocked = false;
        $quota->save();
    }

    public function test_get_ai_quota_endpoint(): void
    {
        $response = $this->actingAs($this->directorUser, 'api')
            ->getJson('/api/v1/director/ai/quota');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'plan_tier',
                'monthly_query_limit',
                'queries_used_this_month',
                'remaining_queries',
                'tokens_used_this_month',
                'quota_resets_at',
                'is_blocked',
            ],
        ]);
        $this->assertEquals(50, $response->json('data.monthly_query_limit'));
        $this->assertEquals(50, $response->json('data.remaining_queries'));
    }

    public function test_reset_session_endpoint(): void
    {
        $response = $this->actingAs($this->directorUser, 'api')
            ->postJson('/api/v1/director/ai/session/reset');

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'session_uuid',
                'session_title',
                'created_at',
            ],
        ]);
        $this->assertNotEmpty($response->json('data.session_uuid'));
    }

    public function test_get_session_history_endpoint(): void
    {
        $session = AiChatSession::create([
            'user_id' => $this->directorUser->id,
            'title' => 'Camp Performance Discussion',
        ]);

        AiChatMessage::create([
            'session_id' => $session->id,
            'user_id' => $this->directorUser->id,
            'role' => 'user',
            'content' => 'Which referees live in Tulsa?',
        ]);

        AiChatMessage::create([
            'session_id' => $session->id,
            'user_id' => $this->directorUser->id,
            'role' => 'assistant',
            'content' => 'Here are the referees living in Tulsa, OK.',
            'widget_type' => 'table',
            'widget_payload' => ['referees' => ['Michael Jordan']],
            'tokens_used' => 120,
        ]);

        $response = $this->actingAs($this->directorUser, 'api')
            ->getJson("/api/v1/director/ai/session/{$session->session_uuid}/history");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'session_uuid',
                'title',
                'messages' => [
                    '*' => [
                        'id',
                        'role',
                        'content',
                        'widget_type',
                        'widget_payload',
                        'tokens_used',
                        'created_at',
                    ],
                ],
            ],
        ]);
        $this->assertCount(2, $response->json('data.messages'));
    }

    public function test_chat_endpoint_with_mock_llm_provider(): void
    {
        // Bind mock provider to avoid external API calls during unit test
        $mockProvider = new class implements AiProviderInterface {
            public function chat(array $messages, array $tools = [], array $options = []): array {
                return [
                    'content' => 'Michael Jordan scored 8.5 on call accuracy and was evaluated 3 times.',
                    'tool_calls' => [],
                    'tokens_used' => 85,
                    'finish_reason' => 'stop',
                ];
            }
        };

        $toolRegistry = app(AiToolRegistry::class);
        $customEngineService = new class($toolRegistry, $mockProvider) extends AiEngineService {
            protected AiProviderInterface $customMock;
            public function __construct(AiToolRegistry $r, AiProviderInterface $p) {
                parent::__construct($r);
                $this->customMock = $p;
            }
            public function getProvider(): AiProviderInterface {
                return $this->customMock;
            }
        };

        $this->app->instance(AiEngineService::class, $customEngineService);

        $response = $this->actingAs($this->directorUser, 'api')
            ->postJson('/api/v1/director/ai/chat', [
                'message' => 'Tell me how Michael Jordan performed',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'session_uuid',
                'role',
                'content',
                'tokens_used',
                'quota' => [
                    'remaining_queries',
                    'monthly_limit',
                    'queries_used_this_month',
                ],
            ],
        ]);

        $this->assertEquals('assistant', $response->json('data.role'));
        $this->assertStringContainsString('Michael Jordan', $response->json('data.content'));
        $this->assertEquals(49, $response->json('data.quota.remaining_queries'));
        $this->assertEquals(1, $response->json('data.quota.queries_used_this_month'));
    }

    public function test_chat_validation_fails_on_empty_message(): void
    {
        $response = $this->actingAs($this->directorUser, 'api')
            ->postJson('/api/v1/director/ai/chat', [
                'message' => '',
            ]);

        $response->assertStatus(422);
    }
}
