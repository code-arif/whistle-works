<?php

namespace Modules\Director\app\Services\Ai\Providers;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Director\app\Services\Ai\Contracts\AiProviderInterface;

class OpenAiProvider implements AiProviderInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;

    public function __construct(string $apiKey, string $model = 'gpt-4o', string $baseUrl = 'https://api.openai.com/v1')
    {
        $this->apiKey = $apiKey;
        $this->model = $model ?: 'gpt-4o';
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Send chat messages with optional function/tool calling declarations to OpenAI.
     */
    public function chat(array $messages, array $tools = [], array $options = []): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('OpenAI API key is missing or not configured.');
        }

        $formattedMessages = array_map(function ($msg) {
            $formatted = [
                'role' => $msg['role'],
                'content' => $msg['content'] ?? '',
            ];

            if ($msg['role'] === 'tool' && isset($msg['tool_call_id'])) {
                $formatted['tool_call_id'] = $msg['tool_call_id'];
            }

            if ($msg['role'] === 'assistant' && !empty($msg['tool_calls'])) {
                $formatted['tool_calls'] = $msg['tool_calls'];
            }

            return $formatted;
        }, $messages);

        $payload = [
            'model' => $this->model,
            'messages' => $formattedMessages,
            'temperature' => $options['temperature'] ?? 0.3,
        ];

        if (!empty($tools)) {
            $payload['tools'] = array_map(function ($tool) {
                return [
                    'type' => 'function',
                    'function' => [
                        'name' => $tool['name'],
                        'description' => $tool['description'],
                        'parameters' => $tool['parameters'],
                    ],
                ];
            }, $tools);
            $payload['tool_choice'] = $options['tool_choice'] ?? 'auto';
        }

        if (isset($options['max_tokens'])) {
            $payload['max_tokens'] = $options['max_tokens'];
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post("{$this->baseUrl}/chat/completions", $payload);

        if (!$response->successful()) {
            $errorMsg = $response->json('error.message') ?? $response->body();
            Log::error('[OpenAiProvider] Request failed: ' . $errorMsg, [
                'status' => $response->status(),
                'payload' => $payload,
            ]);
            throw new Exception("OpenAI API error: {$errorMsg}");
        }

        $data = $response->json();
        $choice = $data['choices'][0] ?? [];
        $message = $choice['message'] ?? [];
        $finishReason = $choice['finish_reason'] ?? 'stop';
        $totalTokens = $data['usage']['total_tokens'] ?? 0;

        $toolCalls = [];
        if (!empty($message['tool_calls'])) {
            foreach ($message['tool_calls'] as $tc) {
                $args = [];
                if (!empty($tc['function']['arguments'])) {
                    $decoded = json_decode($tc['function']['arguments'], true);
                    $args = is_array($decoded) ? $decoded : [];
                }

                $toolCalls[] = [
                    'id' => $tc['id'],
                    'name' => $tc['function']['name'],
                    'arguments' => $args,
                    'raw_arguments' => $tc['function']['arguments'],
                ];
            }
        }

        return [
            'content' => $message['content'] ?? null,
            'tool_calls' => $toolCalls,
            'tokens_used' => $totalTokens,
            'finish_reason' => $finishReason,
            'raw_message' => $message,
        ];
    }
}
