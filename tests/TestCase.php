<?php

namespace Isaacdew\Industry\Tests;

class TestCase extends \Orchestra\Testbench\TestCase {

    protected function defineEnvironment($app)
    {
        $app['config']->set([
            'prism.providers.ollama' => [
                'api_key' => '',
                'url' => 'http://127.0.0.1:11434'
            ]
        ]);
    }

    protected function getPackageProviders($app) 
    {
        return [
            'Isaacdew\Industry\IndustryServiceProvider',
        ];
    }
}
