<?php

namespace Isaacdew\Industry;

use Prism\Prism\Schema\StringSchema;

class IndustryDefinition
{
    protected $testValue;

    public function __construct(protected string $description, protected bool $required = true) {}

    public function isRequired()
    {
        return $this->required;
    }

    public function toPrismSchema($name)
    {
        return new StringSchema($name, $this->description);
    }

    public function forTest($value)
    {
        $this->testValue = $value;

        return $this;
    }

    public function getTestValue()
    {
        if (! $this->testValue) {
            return $this->description;
        }

        return $this->testValue;
    }
}
