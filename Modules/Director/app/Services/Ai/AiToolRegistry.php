<?php

namespace Modules\Director\app\Services\Ai;

use Exception;
use Illuminate\Support\Facades\Log;
use Modules\Director\app\Services\Ai\Contracts\AiToolInterface;

class AiToolRegistry
{
    /**
     * @var array<string, AiToolInterface>
     */
    protected array $tools = [];

    public function __construct()
    {
        $this->registerDefaultTools();
    }

    /**
     * Register a new tool with the registry.
     */
    public function register(AiToolInterface $tool): self
    {
        $this->tools[$tool->getName()] = $tool;
        return $this;
    }

    /**
     * Check if a tool exists by name.
     */
    public function hasTool(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    /**
     * Get all registered tool instances.
     *
     * @return array<string, AiToolInterface>
     */
    public function getTools(): array
    {
        return $this->tools;
    }

    /**
     * Get JSON schemas for all registered tools to send to the LLM.
     */
    public function getToolsSchema(): array
    {
        $schemas = [];
        foreach ($this->tools as $tool) {
            $schemas[] = [
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'parameters' => $tool->getParametersSchema(),
            ];
        }
        return $schemas;
    }

    /**
     * Execute a specific tool safely with logging.
     *
     * @throws Exception
     */
    public function executeTool(string $name, array $arguments, int $userId): array
    {
        if (!$this->hasTool($name)) {
            Log::warning("[AiToolRegistry] Unknown tool requested: {$name}");
            throw new Exception("Tool '{$name}' is not registered or supported.");
        }

        $tool = $this->tools[$name];
        
        Log::info("[AiToolRegistry] Executing tool: {$name}", [
            'user_id' => $userId,
            'arguments' => $arguments,
        ]);

        try {
            $startTime = microtime(true);
            $executionResult = $tool->execute($arguments, $userId);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::info("[AiToolRegistry] Tool {$name} executed in {$duration}ms", [
                'has_widget' => !empty($executionResult['widget_type']),
            ]);

            return $executionResult;
        } catch (\Throwable $e) {
            Log::error("[AiToolRegistry] Tool {$name} execution failed: " . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'result' => [
                    'error' => true,
                    'message' => "Tool execution failed: " . $e->getMessage(),
                ],
                'widget_type' => null,
                'widget_payload' => null,
                'summary' => "Error executing tool: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Register core built-in tools.
     */
    protected function registerDefaultTools(): void
    {
        $this->register(new \Modules\Director\app\Services\Ai\Tools\CompareRefereesTool());
        $this->register(new \Modules\Director\app\Services\Ai\Tools\RefereeImprovementTool());
        $this->register(new \Modules\Director\app\Services\Ai\Tools\CampRefereesLocationTool());
        $this->register(new \Modules\Director\app\Services\Ai\Tools\PendingEvaluatorsTool());
        $this->register(new \Modules\Director\app\Services\Ai\Tools\RefereePerformanceTimelineTool());
        $this->register(new \Modules\Director\app\Services\Ai\Tools\CompileCampRankingsExcelTool());
        $this->register(new \Modules\Director\app\Services\Ai\Tools\RefereeCheckinStatusTool());
    }
}
