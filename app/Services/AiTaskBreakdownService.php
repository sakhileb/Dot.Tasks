<?php

namespace App\Services;

use App\Models\AiBreakdownLog;
use App\Models\Task;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiTaskBreakdownService
{
    private string $apiKey;

    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key', '');
        $this->model = config('services.anthropic.model', 'claude-sonnet-4-6');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Break a task down into subtasks.
     * Returns ['subtasks' => [['title', 'priority', 'estimated_minutes']]]
     * or null on failure.
     *
     * @return array<string, mixed>|null
     */
    public function breakdown(Task $task, int $userId): ?array
    {
        $prompt = $this->buildPrompt($task);

        if (! $this->isConfigured()) {
            return $this->mockBreakdown($task);
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => 800,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

        if (! $response->successful()) {
            Log::error('AiTaskBreakdown API error', ['status' => $response->status()]);

            return null;
        }

        $text = $response->json('content.0.text', '');
        $result = $this->parseBreakdown($text);

        AiBreakdownLog::create([
            'task_id' => $task->id,
            'user_id' => $userId,
            'prompt' => $prompt,
            'response' => $text,
            'tokens_used' => $response->json('usage.output_tokens'),
        ]);

        return $result;
    }

    private function buildPrompt(Task $task): string
    {
        $desc = $task->description ? "\nDescription: {$task->description}" : '';

        return <<<PROMPT
Break this task down into actionable subtasks.

Task: {$task->title}{$desc}
Priority: {$task->priority}

Return a JSON object:
{
  "subtasks": [
    {"title": "Subtask title", "priority": "medium", "estimated_minutes": 30}
  ]
}

Create 3-7 subtasks. Priority: low/medium/high/urgent. estimated_minutes: realistic time estimate.
Return only the JSON object.
PROMPT;
    }

    /** @return array<string, mixed>|null */
    private function parseBreakdown(string $text): ?array
    {
        $json = trim($text);
        if (str_starts_with($json, '```')) {
            $json = preg_replace('/^```[a-z]*\n?/', '', $json);
            $json = preg_replace('/\n?```$/', '', $json);
        }

        $data = json_decode(trim($json ?? ''), true);
        if (! is_array($data) || ! isset($data['subtasks'])) {
            return null;
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function mockBreakdown(Task $task): array
    {
        return [
            'subtasks' => [
                ['title' => 'Research and gather requirements for: '.$task->title, 'priority' => 'high',   'estimated_minutes' => 30],
                ['title' => 'Design the solution approach',                            'priority' => 'medium', 'estimated_minutes' => 45],
                ['title' => 'Implement the core logic',                                'priority' => 'high',   'estimated_minutes' => 90],
                ['title' => 'Write tests',                                             'priority' => 'medium', 'estimated_minutes' => 30],
                ['title' => 'Review and document',                                     'priority' => 'low',    'estimated_minutes' => 20],
            ],
        ];
    }
}
