<?php

use App\Services\OpenMeteoService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new OpenMeteoService;
});

it('can get current weather for valid coordinates', function () {
    Http::fake([
        'api.open-meteo.com/*' => Http::response([
            'current' => [
                'temperature_2m' => 25.5,
                'relative_humidity_2m' => 65,
                'apparent_temperature' => 27.2,
                'is_day' => 1,
                'precipitation' => 0,
                'rain' => 0,
                'showers' => 0,
                'snowfall' => 0,
                'weather_code' => 1,
                'cloud_cover' => 25,
                'pressure_msl' => 1013.2,
                'surface_pressure' => 1008.5,
                'wind_speed_10m' => 12.5,
                'wind_direction_10m' => 180,
                'wind_gusts_10m' => 18.0,
            ],
            'current_units' => [
                'temperature_2m' => '°C',
                'wind_speed_10m' => 'km/h',
            ],
        ], 200),
    ]);

    $result = $this->service->getCurrentWeather(52.5244, 13.4105); // Berlin coordinates

    expect($result)
        ->toBeArray()
        ->toHaveKey('current')
        ->and($result['current']['temperature_2m'])->toBe(25.5)
        ->and($result['current']['wind_speed_10m'])->toBe(12.5);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.open-meteo.com/v1/forecast') &&
            $request['latitude'] == 52.5244 &&
            $request['longitude'] == 13.4105;
    });
});

it('can get coordinates for a valid city name', function () {
    Http::fake([
        'geocoding-api.open-meteo.com/*' => Http::response([
            'results' => [
                [
                    'latitude' => 52.5244,
                    'longitude' => 13.4105,
                    'name' => 'Berlin',
                    'country' => 'Germany',
                ],
            ],
        ], 200),
    ]);

    $result = $this->service->getCoordinates('Berlin');

    expect($result)
        ->toBeArray()
        ->toHaveKey('latitude')
        ->toHaveKey('longitude')
        ->and($result['latitude'])->toBe(52.5244)
        ->and($result['longitude'])->toBe(13.4105);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'geocoding-api.open-meteo.com') &&
            $request['name'] == 'Berlin' &&
            $request['count'] == 1;
    });
});

it('returns null when geocoding finds no results', function () {
    Http::fake([
        'geocoding-api.open-meteo.com/*' => Http::response([
            'results' => [],
        ], 200),
    ]);

    $result = $this->service->getCoordinates('NonExistentCity');

    expect($result)->toBeNull();
});

it('handles weather API failures gracefully', function () {
    Http::fake([
        'api.open-meteo.com/*' => Http::response([], 500),
    ]);

    expect(fn () => $this->service->getCurrentWeather(52.5244, 13.4105))
        ->toThrow(Exception::class, 'Weather data request failed with status: 500');
});

it('handles geocoding API failures gracefully', function () {
    Http::fake([
        'geocoding-api.open-meteo.com/*' => Http::response([], 500),
    ]);

    $result = $this->service->getCoordinates('Berlin');

    expect($result)->toBeNull();
});

it('can be configured with different units', function () {
    $service = new OpenMeteoService(
        temperatureUnit: 'fahrenheit',
        windSpeedUnit: 'mph',
        precipitationUnit: 'inch'
    );

    Http::fake([
        'api.open-meteo.com/*' => Http::response([
            'current' => ['temperature_2m' => 77.9],
            'current_units' => ['temperature_2m' => '°F'],
        ], 200),
    ]);

    $result = $service->getCurrentWeather(52.5244, 13.4105);

    expect($result)->toBeArray();

    Http::assertSent(function ($request) {
        return $request['temperature_unit'] == 'fahrenheit' &&
            $request['wind_speed_unit'] == 'mph' &&
            $request['precipitation_unit'] == 'inch';
    });
});
