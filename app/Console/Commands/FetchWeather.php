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
    protected $signature = 'app:fetch-weather {prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch the weather for a given city';

    private PrismService $prismService;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->prismService = new PrismService(new OpenMeteoService);

        $response = $this->prismService->getWeather($this->argument('prompt'));

        $this->info($response);
    }
}
