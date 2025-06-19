<?php

namespace App\Services;

use App\Models\Conversation;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Tool;
use Prism\Prism\Prism;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

class PrismService
{
    public function __construct(
        private OpenMeteoService $openMeteoService,
    ) {}

    /**
     * Get the weather for a given city with conversation history
     */
    public function getWeather(string $prompt, string $sessionId = 'default'): string
    {
        // Load conversation history
        $conversationHistory = Conversation::getSessionHistory($sessionId);

        // Convert history to Prism messages and add current prompt
        $messages = $this->buildMessagesFromHistory($conversationHistory, $prompt);

        // Store the user message
        Conversation::addMessage($sessionId, 'user', $prompt);

        $weatherTool = Tool::as('weather')
            ->for('Get current weather conditions')
            ->withStringParameter('city', 'The city to get weather for')
            ->using(function (string $city): string {
                $coords = $this->openMeteoService->getCoordinates($city);
                $data = $this->openMeteoService->getCurrentWeather($coords['latitude'], $coords['longitude']);

                return (string) json_encode($data);
            });

        $response = Prism::text()
            ->using(Provider::OpenAI, 'gpt-4o-mini')
            ->withMessages($messages)
            ->withMaxSteps(2)
            ->withTools([$weatherTool])
            ->asText();

        // Store the assistant response
        Conversation::addMessage($sessionId, 'assistant', $response->text);

        return $response->text;
    }

    /**
     * Build Prism message objects from conversation history
     */
    private function buildMessagesFromHistory(array $history, ?string $currentPrompt = null): array
    {
        $messages = [];

        // Add system message for context
        $messages[] = new SystemMessage('You are a helpful weather assistant. Provide current weather information for any city using the weather tool. Be conversational and remember the context of previous exchanges. Use the last known location from previous messages as the default location. Do no answer or give assistance with anything else than the weather information.');

        // Convert stored history to message objects
        foreach ($history as $message) {
            switch ($message['role']) {
                case 'user':
                    $messages[] = new UserMessage($message['content']);
                    break;
                case 'assistant':
                    $messages[] = new AssistantMessage($message['content']);
                    break;
                case 'system':
                    $messages[] = new SystemMessage($message['content']);
                    break;
            }
        }

        // Add current prompt as the latest user message
        if ($currentPrompt) {
            $messages[] = new UserMessage($currentPrompt);
        }

        return $messages;
    }

    /**
     * Get conversation history for a session
     */
    public function getConversationHistory(string $sessionId = 'default'): array
    {
        return Conversation::getSessionHistory($sessionId);
    }

    /**
     * Clear conversation history for a session
     */
    public function clearConversationHistory(string $sessionId = 'default'): void
    {
        Conversation::where('session_id', $sessionId)->delete();
    }
}
