<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenMeteoService
{
    private const BASE_URL = 'https://api.open-meteo.com/v1/forecast';

    private const GEOCODING_URL = 'https://geocoding-api.open-meteo.com/v1/search';

    private string $temperatureUnit;

    private string $windSpeedUnit;

    private string $precipitationUnit;

    private string $timeFormat;

    public function __construct(
        string $temperatureUnit = 'celsius',
        string $windSpeedUnit = 'kmh',
        string $precipitationUnit = 'mm',
        string $timeFormat = 'iso8601'
    ) {
        $this->temperatureUnit = $temperatureUnit;
        $this->windSpeedUnit = $windSpeedUnit;
        $this->precipitationUnit = $precipitationUnit;
        $this->timeFormat = $timeFormat;
    }

    /**
     * Get current weather for a location
     */
    public function getCurrentWeather(float $latitude, float $longitude): array
    {
        $params = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'current' => [
                'temperature_2m',
                'relative_humidity_2m',
                'apparent_temperature',
                'is_day',
                'precipitation',
                'rain',
                'showers',
                'snowfall',
                'weather_code',
                'cloud_cover',
                'pressure_msl',
                'surface_pressure',
                'wind_speed_10m',
                'wind_direction_10m',
                'wind_gusts_10m',
            ],
            'temperature_unit' => $this->temperatureUnit,
            'wind_speed_unit' => $this->windSpeedUnit,
            'precipitation_unit' => $this->precipitationUnit,
            'timeformat' => $this->timeFormat,
        ];

        return $this->makeRequest($params);
    }

    /**
     * Get coordinates for a city name using the Geocoding API.
     */
    public function getCoordinates(string $name): ?array
    {
        try {
            $response = Http::timeout(10)->get(self::GEOCODING_URL, [
                'name' => $name,
                'count' => 1,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (! empty($data['results'])) {
                    $location = $data['results'][0];

                    return [
                        'latitude' => $location['latitude'],
                        'longitude' => $location['longitude'],
                    ];
                }
            }

            Log::warning('Geocoding request failed or returned no results', [
                'place' => $name,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Geocoding API error', [
                'message' => $e->getMessage(),
                'place' => $name,
            ]);

            return null;
        }
    }

    /**
     * Make the HTTP request to Open-Meteo API
     */
    private function makeRequest(array $params): array
    {
        try {
            $response = Http::timeout(30)->get(self::BASE_URL, $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('OpenMeteo API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'params' => $params,
            ]);

            throw new \Exception('Weather data request failed with status: '.$response->status());
        } catch (\Exception $e) {
            Log::error('OpenMeteo API error', [
                'message' => $e->getMessage(),
                'params' => $params,
            ]);

            throw new \Exception('Unable to fetch weather data: '.$e->getMessage());
        }
    }
}
