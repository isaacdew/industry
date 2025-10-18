<?php

namespace Isaacdew\Industry;

use Prism\Prism\Schema\StringSchema;

class IndustryDefinition
{
    protected $testValue;

    public function __construct(protected string $description, protected bool $required = true) {}

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function toPrismSchema($name): StringSchema
    {
        return new StringSchema($name, $this->description);
    }

    public function fallback($value): static
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

    public function getDescription(): string
    {
        return $this->description;
    }
}
