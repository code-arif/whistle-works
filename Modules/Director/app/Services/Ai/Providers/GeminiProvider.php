<?php

namespace Modules\Director\app\Services\Ai\Providers;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Director\app\Services\Ai\Contracts\AiProviderInterface;

class GeminiProvider implements AiProviderInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;

    public function __construct(string $apiKey, string $model = 'gemini-1.5-flash', string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta')
    {
        $this->apiKey = trim($apiKey);
        $cleanModel = str_replace(['models/', 'models:'], '', trim($model ?: 'gemini-1.5-flash'));
        $this->model = $cleanModel;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Send chat messages with function declarations to Google Gemini API.
     */
    public function chat(array $messages, array $tools = [], array $options = []): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('Google Gemini API key is missing or not configured.');
        }

        $geminiContents = [];
        $systemInstruction = null;

        foreach ($messages as $msg) {
            $role = $msg['role'];
            $content = $msg['content'] ?? '';

            if ($role === 'system') {
                $systemInstruction = [
                    'parts' => [['text' => $content]],
                ];
                continue;
            }

            if ($role === 'user') {
                $geminiContents[] = [
                    'role' => 'user',
                    'parts' => [['text' => $content]],
                ];
            } elseif ($role === 'assistant') {
                $parts = [];
                if (!empty($content)) {
                    $parts[] = ['text' => $content];
                }
                if (!empty($msg['tool_calls'])) {
                    foreach ($msg['tool_calls'] as $tc) {
                        $rawArgs = $tc['arguments'] ?? $tc['function']['arguments'] ?? [];
                        if (is_string($rawArgs)) {
                            $rawArgs = json_decode($rawArgs, true) ?: [];
                        }
                        $part = [
                            'functionCall' => [
                                'name' => $tc['name'] ?? $tc['function']['name'] ?? '',
                                'args' => (object) ($rawArgs ?: []),
                            ],
                        ];
                        $thoughtSig = $tc['thought_signature'] ?? $tc['thoughtSignature'] ?? $tc['function']['thought_signature'] ?? $tc['function']['thoughtSignature'] ?? null;
                        if (!empty($thoughtSig)) {
                            $part['thought_signature'] = $thoughtSig;
                        }
                        $parts[] = $part;
                    }
                }
                $geminiContents[] = [
                    'role' => 'model',
                    'parts' => !empty($parts) ? $parts : [['text' => ' ']],
                ];
            } elseif ($role === 'tool') {
                $decoded = json_decode($content, true);
                $contentPayload = (is_array($decoded) && !empty($decoded)) ? $decoded : ['result' => $content];
                $geminiContents[] = [
                    'role' => 'function',
                    'parts' => [
                        [
                            'functionResponse' => [
                                'name' => $msg['tool_name'] ?? 'tool_result',
                                'response' => (object) [
                                    'name' => $msg['tool_name'] ?? 'tool_result',
                                    'content' => $contentPayload,
                                ],
                            ],
                        ],
                    ],
                ];
            }
        }

        $payload = [
            'contents' => $geminiContents,
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? 0.3,
            ],
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = $systemInstruction;
        }

        if (!empty($tools)) {
            $functionDeclarations = array_map(function ($tool) {
                return [
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'parameters' => $this->formatParametersForGemini($tool['parameters'] ?? []),
                ];
            }, $tools);

            $payload['tools'] = [
                ['functionDeclarations' => $functionDeclarations],
            ];
        }

        $url = "{$this->baseUrl}/models/{$this->model}:generateContent";

        $response = Http::timeout(60)
            ->withHeaders([
                'x-goog-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($url, $payload);

        if (!$response->successful()) {
            $errorMsg = $response->json('error.message') ?? $response->body();
            Log::error('[GeminiProvider] Request failed: ' . $errorMsg, [
                'status' => $response->status(),
                'model' => $this->model,
                'payload' => $payload,
            ]);
            throw new Exception("Gemini API error: {$errorMsg}");
        }

        $data = $response->json();
        $candidate = $data['candidates'][0] ?? [];
        $parts = $candidate['content']['parts'] ?? [];
        $finishReason = $candidate['finishReason'] ?? 'STOP';
        $totalTokens = $data['usageMetadata']['totalTokenCount'] ?? 0;

        $textContent = '';
        $toolCalls = [];

        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $textContent .= $part['text'];
            }
            if (isset($part['functionCall'])) {
                $fc = $part['functionCall'];
                $thoughtSig = $part['thought_signature'] ?? $part['thoughtSignature'] ?? $fc['thought_signature'] ?? $fc['thoughtSignature'] ?? null;
                $toolCalls[] = [
                    'id' => 'gemini_call_' . uniqid(),
                    'name' => $fc['name'],
                    'arguments' => $fc['args'] ?? [],
                    'raw_arguments' => json_encode($fc['args'] ?? []),
                    'thought_signature' => $thoughtSig,
                ];
            }
        }

        return [
            'content' => !empty($textContent) ? $textContent : null,
            'tool_calls' => $toolCalls,
            'tokens_used' => $totalTokens,
            'finish_reason' => $finishReason,
            'raw_message' => $candidate,
        ];
    }

    /**
     * Recursively format JSON Schema parameter types to uppercase for Google Gemini REST specification.
     */
    protected function formatParametersForGemini(array $parameters): array
    {
        $formatted = [];
        foreach ($parameters as $key => $value) {
            if ($key === 'type' && is_string($value)) {
                $formatted[$key] = strtoupper($value);
            } elseif (is_array($value)) {
                $formatted[$key] = $this->formatParametersForGemini($value);
            } else {
                $formatted[$key] = $value;
            }
        }
        return $formatted;
    }
}
