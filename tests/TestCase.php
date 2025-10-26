<?php

namespace Isaacdew\Industry\Tests;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache before each test
        if (file_exists(database_path('industry_cache.sqlite'))) {
            unlink(database_path('industry_cache.sqlite'));
        }
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set([
            'prism.providers.ollama' => [
                'api_key' => '',
                'url' => 'http://127.0.0.1:11434',
            ],
            'industry.cache.enabled' => false,
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            'Isaacdew\Industry\IndustryServiceProvider',
        ];
    }
}
