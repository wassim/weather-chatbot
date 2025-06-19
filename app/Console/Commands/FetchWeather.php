<?php

namespace App\Console\Commands;

use App\Services\OpenMeteoService;
use App\Services\PrismService;
use Illuminate\Console\Command;

class FetchWeather extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-weather
                            {--session=default : Session ID for conversation continuity}
                            {--history : Show conversation history}
                            {--clear : Clear conversation history}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactive weather chatbot with conversation memory (starts in chat mode by default)';

    private PrismService $prismService;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->prismService = new PrismService(new OpenMeteoService);
        $sessionId = $this->option('session');

        // Handle history viewing
        if ($this->option('history')) {
            $this->showHistory($sessionId);

            return;
        }

        // Handle clearing history
        if ($this->option('clear')) {
            $this->clearHistory($sessionId);

            return;
        }

        // Start interactive mode by default
        $this->startInteractiveMode($sessionId);
    }

    /**
     * Start interactive chatbot mode
     */
    private function startInteractiveMode(string $sessionId): void
    {
        $this->info("🤖 Weather Chatbot (Session: {$sessionId})");
        $this->info("Type your weather questions. Type 'quit', 'exit', or 'bye' to end the conversation.");
        $this->info("Type 'history' to see conversation history, 'clear' to clear history.");
        $this->newLine();

        while (true) {
            $prompt = $this->ask('You');

            if (! $prompt) {
                continue;
            }

            $prompt = trim($prompt);

            // Handle special commands
            if (in_array(strtolower($prompt), ['quit', 'exit', 'bye'])) {
                $this->info('Goodbye! 👋');
                break;
            }

            if (strtolower($prompt) === 'history') {
                $this->showHistory($sessionId, false);

                continue;
            }

            if (strtolower($prompt) === 'clear') {
                $this->clearHistory($sessionId, false);

                continue;
            }

            // Send message to chatbot
            $this->sendMessage($prompt, $sessionId);
            $this->newLine();
        }
    }

    /**
     * Send a message to the chatbot and display response
     */
    private function sendMessage(string $prompt, string $sessionId): void
    {
        try {
            $this->info('🤔 Thinking...');
            $response = $this->prismService->getWeather($prompt, $sessionId);
            $this->newLine();
            $this->line("🤖 <fg=cyan>{$response}</>");
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());
        }
    }

    /**
     * Show conversation history
     */
    private function showHistory(string $sessionId, bool $exitAfter = true): void
    {
        $history = $this->prismService->getConversationHistory($sessionId);

        if (empty($history)) {
            $this->info("No conversation history found for session: {$sessionId}");
            if ($exitAfter) {
                return;
            }
        }

        $this->info("📜 Conversation History (Session: {$sessionId}):");
        $this->newLine();

        foreach ($history as $message) {
            $role = $message['role'];
            $content = $message['content'];
            $timestamp = date('Y-m-d H:i:s', strtotime($message['created_at']));

            $icon = match ($role) {
                'user' => '👤',
                'assistant' => '🤖',
                'system' => '⚙️',
                default => '💬'
            };

            $color = match ($role) {
                'user' => 'green',
                'assistant' => 'cyan',
                'system' => 'yellow',
                default => 'white'
            };

            $this->line("<fg={$color}>{$icon} {$role} [{$timestamp}]:</>");
            $this->line("<fg={$color}>{$content}</>");
            $this->newLine();
        }
    }

    /**
     * Clear conversation history
     */
    private function clearHistory(string $sessionId, bool $exitAfter = true): void
    {
        if ($this->confirm("Are you sure you want to clear conversation history for session '{$sessionId}'?")) {
            $this->prismService->clearConversationHistory($sessionId);
            $this->info("🗑️ Conversation history cleared for session: {$sessionId}");
        } else {
            $this->info('History clearing cancelled.');
        }
    }
}
