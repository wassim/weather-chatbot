<?php

namespace App\Services;

use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Tool;
use Prism\Prism\Prism;

class PrismService
{
    public function __construct(
        private OpenMeteoService $openMeteoService,
    ) {}

    /**
     * Get the weather for a given city
     */
    public function getWeather(string $prompt): string
    {
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
            ->withMaxSteps(2)
            ->withPrompt($prompt)
            ->withTools([$weatherTool])
            ->asText();

        return $response->text;
    }
}
