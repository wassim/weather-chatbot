<?php

namespace App\Services;

use App\Models\Conversation;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Enums\ToolChoice;
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

                if ($coords === null) {
                    throw new \Exception("Could not find coordinates for city: {$city}");
                }

                $data = $this->openMeteoService->getCurrentWeather($coords['latitude'], $coords['longitude']);

                return (string) json_encode($data);
            });

        $response = Prism::text()
            ->using(Provider::OpenAI, 'gpt-4o-mini')
            ->withMessages($messages)
            ->withMaxSteps(2)
            ->withTools([$weatherTool])
            ->withToolChoice(ToolChoice::Auto)
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
        $systemPrompt = 'You are a helpful weather assistant. Provide current weather information in human readable format for any city using the weather tool. Be conversational and remember the context of previous exchanges.

IMPORTANT CONTEXT RULES:
1. If someone asks "What\'s the weather?" or similar without specifying a city, look at the conversation history for the most recently mentioned city and use that.
2. If you find a recent city in our conversation, use it as the default.
3. Only ask for a city if no city has been mentioned in recent conversation history.
4. Do NOT answer questions about anything other than weather information.';

        $messages[] = new SystemMessage($systemPrompt);

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
