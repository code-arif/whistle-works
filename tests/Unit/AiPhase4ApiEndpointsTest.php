<?php

namespace Tests\Unit;

use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\CampEvaluatorRegistration;
use App\Models\SportsType;
use App\Models\User;
use App\Models\UserAiQuota;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Director\app\Services\Ai\AiEngineService;
use Modules\Director\app\Services\Ai\AiToolRegistry;
use Modules\Director\app\Services\Ai\Contracts\AiProviderInterface;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;
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

    public function test_get_camps_directory_endpoint(): void
    {
        $sport = SportsType::firstOrCreate(
            ['sports_name' => 'Basketball'],
            ['sports_fee' => 10, 'status' => 'active']
        );

        $camp = Camp::firstOrCreate(
            ['camp_name' => 'Elite Director AI Showcase Camp'],
            [
                'sports_type_id' => $sport->id,
                'sports_type_name' => $sport->sports_name,
                'location' => 'Dallas, TX',
                'price' => 150.00,
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-05',
                'director_id' => $this->directorUser->id,
                'status' => 'active',
            ]
        );

        // Referees with distinct last names to test sorting (Adams should be before Zimmerman)
        $refereeZ = User::firstOrCreate(
            ['email' => 'ref_zimmerman@whistleworks.org'],
            [
                'first_name' => 'Zack',
                'last_name' => 'Zimmerman',
                'username' => 'ref_zimmerman',
                'slug' => 'ref-zimmerman',
            ]
        );

        $refereeA = User::firstOrCreate(
            ['email' => 'ref_adams@whistleworks.org'],
            [
                'first_name' => 'Alex',
                'last_name' => 'Adams',
                'username' => 'ref_adams',
                'slug' => 'ref-adams',
            ]
        );

        CampRefereeCheckin::firstOrCreate([
            'camp_id' => $camp->id,
            'referee_id' => $refereeZ->id,
        ], [
            'registration_status' => 'registered',
            'registered_at' => now(),
        ]);

        CampRefereeCheckin::firstOrCreate([
            'camp_id' => $camp->id,
            'referee_id' => $refereeA->id,
        ], [
            'registration_status' => 'checked_in',
            'registered_at' => now(),
            'checked_in_at' => now(),
        ]);

        // Evaluators with distinct last names to test sorting (Baker before Williams)
        $evaluatorW = User::firstOrCreate(
            ['email' => 'eval_williams@whistleworks.org'],
            [
                'first_name' => 'Walter',
                'last_name' => 'Williams',
                'username' => 'eval_williams',
                'slug' => 'eval-williams',
            ]
        );

        $evaluatorB = User::firstOrCreate(
            ['email' => 'eval_baker@whistleworks.org'],
            [
                'first_name' => 'Bob',
                'last_name' => 'Baker',
                'username' => 'eval_baker',
                'slug' => 'eval-baker',
            ]
        );

        CampEvaluatorRegistration::firstOrCreate([
            'camp_id' => $camp->id,
            'evaluator_id' => $evaluatorW->id,
        ], [
            'status' => 'approved',
            'registered_at' => now(),
        ]);

        CampEvaluatorRegistration::firstOrCreate([
            'camp_id' => $camp->id,
            'evaluator_id' => $evaluatorB->id,
        ], [
            'status' => 'approved',
            'registered_at' => now(),
        ]);

        $response = $this->actingAs($this->directorUser, 'api')
            ->getJson('/api/v1/director/ai/camps?search=Showcase');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'logo',
                ],
            ],
            'pagination' => [
                'total',
                'per_page',
                'current_page',
                'last_page',
            ],
            'code',
        ]);

        $campsData = collect($response->json('data'));
        $targetCamp = $campsData->firstWhere('id', $camp->id);
        $this->assertNotNull($targetCamp);
        $this->assertEquals('Elite Director AI Showcase Camp', $targetCamp['name']);
        $this->assertGreaterThanOrEqual(1, $response->json('pagination.total'));
    }

    public function test_get_participants_directory_endpoint(): void
    {
        $camp = Camp::where('camp_name', 'Elite Director AI Showcase Camp')->first();

        // 1. Test fetching with specific camp_id
        $responseCamp = $this->actingAs($this->directorUser, 'api')
            ->getJson("/api/v1/director/ai/participants?camp_id={$camp->id}");

        $responseCamp->assertStatus(200);
        $responseCamp->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => ['id', 'name', 'avatar', 'role'],
            ],
            'pagination' => [
                'total',
                'per_page',
                'current_page',
                'last_page',
            ],
            'code',
        ]);

        $this->assertGreaterThanOrEqual(4, $responseCamp->json('pagination.total'));

        // Check alphabetical sorting by last name (Alex Adams before Bob Baker before Walter Williams before Zack Zimmerman)
        $names = collect($responseCamp->json('data'))->pluck('name')->all();
        $this->assertContains('Alex Adams', $names);
        $this->assertContains('Zack Zimmerman', $names);
        $adamsIndex = array_search('Alex Adams', $names);
        $zimmermanIndex = array_search('Zack Zimmerman', $names);
        $this->assertLessThan($zimmermanIndex, $adamsIndex);

        // 2. Test filtering by role=referee
        $responseRef = $this->actingAs($this->directorUser, 'api')
            ->getJson("/api/v1/director/ai/participants?camp_id={$camp->id}&role=referee");

        $responseRef->assertStatus(200);
        $refRoles = collect($responseRef->json('data'))->pluck('role')->unique()->all();
        $this->assertContains('referee', $refRoles);
        $this->assertNotContains('evaluator', $refRoles);

        // 3. Test filtering by role=evaluator
        $responseEval = $this->actingAs($this->directorUser, 'api')
            ->getJson("/api/v1/director/ai/participants?camp_id={$camp->id}&role=evaluator");

        $responseEval->assertStatus(200);
        $evalRoles = collect($responseEval->json('data'))->pluck('role')->unique()->all();
        $this->assertContains('evaluator', $evalRoles);
        $this->assertNotContains('referee', $evalRoles);

        // 4. Test searching by name
        $responseSearch = $this->actingAs($this->directorUser, 'api')
            ->getJson("/api/v1/director/ai/participants?search=Adams");

        $responseSearch->assertStatus(200);
        $searchNames = collect($responseSearch->json('data'))->pluck('name')->all();
        $this->assertContains('Alex Adams', $searchNames);
    }

    public function test_get_sessions_list_with_pagination(): void
    {
        AiChatSession::where('user_id', $this->directorUser->id)->delete();

        // Create multiple sessions for the director
        $session1 = AiChatSession::create([
            'user_id' => $this->directorUser->id,
            'title' => 'Referee Performance Discussion',
            'total_tokens' => 150,
            'last_interaction_at' => now()->subMinutes(10),
        ]);

        $session2 = AiChatSession::create([
            'user_id' => $this->directorUser->id,
            'title' => 'Camp Tulsa Location Query',
            'total_tokens' => 80,
            'last_interaction_at' => now()->subMinutes(5),
        ]);

        // Add a message to session2 to verify latest_message and message_count
        AiChatMessage::create([
            'session_id' => $session2->id,
            'user_id' => $this->directorUser->id,
            'role' => 'user',
            'content' => 'Show me all referees in Tulsa',
        ]);
        AiChatMessage::create([
            'session_id' => $session2->id,
            'user_id' => $this->directorUser->id,
            'role' => 'assistant',
            'content' => 'Here are 3 referees located in Tulsa',
        ]);

        $response = $this->actingAs($this->directorUser, 'api')
            ->getJson('/api/v1/director/ai/sessions?per_page=10');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'sessions' => [
                    '*' => [
                        'id',
                        'session_uuid',
                        'title',
                        'total_tokens',
                        'messages_count',
                        'last_interaction_at',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'pagination' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page',
                ],
            ],
            'code',
        ]);

        $this->assertGreaterThanOrEqual(2, $response->json('data.pagination.total'));
        $this->assertEquals(10, $response->json('data.pagination.per_page'));
        $this->assertEquals(1, $response->json('data.pagination.current_page'));

        // Verify ordering: session2 has more recent interaction than session1
        $sessions = collect($response->json('data.sessions'));
        $foundSession2 = $sessions->firstWhere('session_uuid', $session2->session_uuid);
        $this->assertNotNull($foundSession2);
        $this->assertEquals('Camp Tulsa Location Query', $foundSession2['title']);
        $this->assertEquals(2, $foundSession2['messages_count']);
        $this->assertNotNull($foundSession2['last_message']);
    }

    public function test_get_sessions_list_search_and_alias(): void
    {
        AiChatSession::create([
            'user_id' => $this->directorUser->id,
            'title' => 'Special Keyword Session Search Target',
        ]);

        $response = $this->actingAs($this->directorUser, 'api')
            ->getJson('/api/v1/director/ai/session/list?search=Keyword');

        $response->assertStatus(200);
        $sessions = collect($response->json('data.sessions'));
        $this->assertTrue($sessions->contains(fn($s) => str_contains($s['title'], 'Special Keyword')));
    }

    public function test_update_and_delete_session(): void
    {
        $session = AiChatSession::create([
            'user_id' => $this->directorUser->id,
            'title' => 'Initial Title',
        ]);

        // 1. Rename
        $renameResponse = $this->actingAs($this->directorUser, 'api')
            ->patchJson("/api/v1/director/ai/session/{$session->session_uuid}", [
                'title' => 'Renamed Conversation',
            ]);

        $renameResponse->assertStatus(200);
        $this->assertEquals('Renamed Conversation', $renameResponse->json('data.title'));
        $this->assertEquals('Renamed Conversation', $session->fresh()->title);

        // 2. Delete
        $deleteResponse = $this->actingAs($this->directorUser, 'api')
            ->deleteJson("/api/v1/director/ai/session/{$session->session_uuid}");

        $deleteResponse->assertStatus(200);
        $this->assertNull(AiChatSession::find($session->id));
    }
}
