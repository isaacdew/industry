<?php

use Prism\Prism\Enums\Provider;

return [
    'provider' => env('INDUSTRY_PROVIDER', Provider::Ollama),

    'model' => env('INDUSTRY_MODEL', 'llama3.2'),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | These options configure the caching behavior for Industry generated data.
    |
    | Supported strategies: "recycle", "lazy_load".
    |
    | 'recycle'  will reuse existing cached entries in a round-robin fashion.
    | 'lazy_load' will always add new entries when the cache doesn't have enough to satisfy
    | the requested amount unless a limit is set. You can set 'lazy_load_until' to lazy load
    | new data from the LLM until that limit is reached.
    |
    */
    'cache' => [
        'enabled' => env('INDUSTRY_CACHE_ENABLED', true),
        'strategy' => env('INDUSTRY_CACHE_STRATEGY', 'recycle'), // recycle, lazy_load
        'lazy_load_until' => (int) env('INDUSTRY_CACHE_LAZY_LOAD_UNTIL', null),
        'database_path' => env('INDUSTRY_CACHE_DATABASE_PATH', database_path('industry_cache.sqlite')),
    ],
];
