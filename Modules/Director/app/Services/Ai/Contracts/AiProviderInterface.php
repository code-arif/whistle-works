<?php

namespace Modules\Director\app\Services\Ai\Contracts;

interface AiProviderInterface
{
    /**
     * Send chat messages with optional tool definitions to the LLM.
     *
     * @param array $messages List of conversation messages [['role' => 'user'|'assistant'|'system'|'tool', 'content' => ...]]
     * @param array $tools List of tool declarations
     * @param array $options Model hyperparameters (temperature, max_tokens, etc.)
     * @return array [
     *   'content' => string|null,
     *   'tool_calls' => array, // [['id' => string, 'name' => string, 'arguments' => array]]
     *   'tokens_used' => int,
     *   'finish_reason' => string
     * ]
     */
    public function chat(array $messages, array $tools = [], array $options = []): array;
}
