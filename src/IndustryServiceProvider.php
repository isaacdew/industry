<?php

namespace Isaacdew\Industry;

use Illuminate\Support\ServiceProvider;
use Prism\Prism\PrismServiceProvider;

class IndustryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/industry.php' => config_path('industry.php')
        ], 'industry-config');


        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ClearCacheCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->app->register(PrismServiceProvider::class);

        $this->mergeConfigFrom(
            __DIR__.'/../config/industry.php',
            'industry'
        );
    }
}
