<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiTool;
use InvalidArgumentException;

/**
 * Registry for managing and retrieving AI tools.
 * Tools are used by the AI model to perform specific actions.
 */
class ToolRegistry
{
    /** @var array<string, AiTool> Registered tools keyed by name */
    private array $tools = [];

    /**
     * Register a single tool.
     *
     * @param AiTool $tool The tool instance to register.
     * @return void
     */
    public function register(AiTool $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }

    /**
     * Register multiple tools at once.
     *
     * @param iterable<AiTool> $tools Iterable of tool instances.
     * @return void
     */
    public function registerMany(iterable $tools): void
    {
        foreach ($tools as $tool) {
            $this->register($tool);
        }
    }

    /**
     * Get a registered tool by its name.
     *
     * @param string $name The name of the tool.
     * @return AiTool The tool instance.
     * @throws InvalidArgumentException If the tool is not registered.
     */
    public function get(string $name): AiTool
    {
        if (!isset($this->tools[$name])) {
            throw new InvalidArgumentException("Unknown AI tool: {$name}");
        }

        return $this->tools[$name];
    }

    /**
     * Get the JSON schemas for all registered tools.
     * Formatted for compatibility with AI API tool definitions.
     *
     * @return array<int, array<string, mixed>> List of tool schemas.
     */
    public function schemas(): array
    {
        return array_map(
            fn (AiTool $tool) => [
                'type' => 'function',
                'function' => $tool->schema(),
            ],
            array_values($this->tools)
        );
    }

    /**
     * Get all registered tools.
     *
     * @return array<string, AiTool> Array of tool instances keyed by name.
     */
    public function all(): array
    {
        return $this->tools;
    }
}

