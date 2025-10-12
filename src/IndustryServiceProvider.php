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
