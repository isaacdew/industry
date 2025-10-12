<?php

namespace Isaacdew\Industry;

use Illuminate\Database\Eloquent\Factories\Factory;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;

class Industry
{
    protected array $schema = [];

    protected array $requiredProperties = [];

    protected $data = null;

    protected $stateIndex = 0;

    public $forceGeneration = false;

    public function __construct(protected Factory $factory) {}

    public function buildSchema($attributes)
    {
        if (! empty($this->schema)) {
            return $this;
        }

        foreach ($attributes as $attribute => $definition) {
            if (! $definition instanceof IndustryDefinition) {
                continue;
            }

            $this->schema[] = $definition->toPrismSchema($attribute);

            if ($definition->isRequired()) {
                $this->requiredProperties[] = $attribute;
            }
        }

        return $this;
    }

    public function getSchema()
    {
        return $this->schema;
    }

    public function getState()
    {
        if (! $this->data) {
            $this->generate();
        }

        // Loop back to the beginning of the array if we run out
        if (! isset($this->data[$this->stateIndex])) {
            $this->stateIndex = 0;
        }

        $data = $this->data[$this->stateIndex];

        $this->stateIndex++;

        return $data;
    }

    public function generate($count = 1)
    {   
        if ($this->data) {
            return $this->data;
        }

        $table = $this->factory->modelName()::make()->getTable();

        $prompt = $this->factory->getPrompt();

        $schema = new ArraySchema(
            name: $table,
            description: $prompt,
            items: new ObjectSchema(
                name: str()->singular($table),
                description: $prompt,
                properties: $this->schema,
                requiredFields: $this->requiredProperties
            )
        );

        $prismRequest = Prism::structured()
            ->using(config('industry.provider'), config('industry.model'));
        if (method_exists($this->factory, 'configurePrism')) {
            $this->factory->configurePrism($prismRequest);
        }

        $response = $prismRequest
            ->withSchema($schema)
            ->withPrompt($prompt)
            ->asStructured();

        $this->data = $response->structured;

        return $this->data;
    }

    public function describe($description, $required = true)
    {
        return new IndustryDefinition($description, $required);
    }
}
