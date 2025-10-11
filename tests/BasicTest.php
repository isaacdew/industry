<?php

namespace Isaacdew\Industry\Tests;

use Workbench\App\Models\MenuItem;

class BasicTest extends TestCase
{
    public function test_ollama_structured_output()
    {
        dd(MenuItem::factory(10)->forceGeneration()->make()->toArray());
    }
}
