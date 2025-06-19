<?php

use App\Services\OpenMeteoService;
use App\Services\PrismService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('can run the fetch weather command with history option', function () {
    $this->mock(OpenMeteoService::class);
    $this->mock(PrismService::class, function ($mock) {
        $mock->shouldReceive('getConversationHistory')
            ->with('default')
            ->andReturn([]);
    });

    $exitCode = Artisan::call('app:fetch-weather', ['--history' => true]);

    expect($exitCode)->toBe(0);
    expect(Artisan::output())->toContain('No conversation history found for session: default');
});

it('can run the fetch weather command with custom session history', function () {
    $this->mock(OpenMeteoService::class);
    $this->mock(PrismService::class, function ($mock) {
        $mock->shouldReceive('getConversationHistory')
            ->with('test-session')
            ->andReturn([]);
    });

    $exitCode = Artisan::call('app:fetch-weather', [
        '--session' => 'test-session',
        '--history' => true,
    ]);

    expect($exitCode)->toBe(0);
    expect(Artisan::output())->toContain('No conversation history found for session: test-session');
});

it('shows conversation history when history exists', function () {
    \App\Models\Conversation::factory()
        ->user()
        ->forSession('default')
        ->create();

    \App\Models\Conversation::factory()
        ->assistant()
        ->forSession('default')
        ->create();

    $exitCode = Artisan::call('app:fetch-weather', ['--history' => true]);
    $output = Artisan::output();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('Conversation History');
});
