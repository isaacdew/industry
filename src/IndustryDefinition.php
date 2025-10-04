<?php

namespace Isaacdew\Industry;

use Prism\Prism\Schema\StringSchema;

class IndustryDefinition
{
    public function __construct(protected string $description, protected bool $required = true) {}

    public function isRequired()
    {
        return $this->required;
    }

    public function toPrismSchema($name)
    {
        return new StringSchema($name, $this->description);
    }
}
