<?php

use Prism\Prism\Enums\Provider;

return [
    'provider' => env('INDUSTRY_PROVIDER', Provider::Ollama),
    'model' => env('INDUSTRY_MODEL', 'llama3.2')
];
