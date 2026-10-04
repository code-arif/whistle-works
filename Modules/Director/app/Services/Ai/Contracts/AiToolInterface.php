<?php

namespace Modules\Director\app\Services\Ai\Contracts;

interface AiToolInterface
{
    /**
     * Unique name of the tool (must match function name in LLM schema).
     */
    public function getName(): string;

    /**
     * Human and LLM readable description of what the tool does and when to call it.
     */
    public function getDescription(): string;

    /**
     * JSON Schema definition of parameters expected by the tool.
     */
    public function getParametersSchema(): array;

    /**
     * Execute the deterministic tool logic.
     *
     * @param array $arguments Arguments passed by the LLM
     * @param int $userId Authenticated Director/User ID
     * @return array [
     *   'result' => mixed,
     *   'widget_type' => 'chart'|'excel_download'|'table'|null,
     *   'widget_payload' => array|null,
     *   'summary' => string|null
     * ]
     */
    public function execute(array $arguments, int $userId): array;
}
