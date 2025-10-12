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

    protected int $stateIndex = 0;

    protected ?int $count = null;

    public bool $forceGeneration = false;

    public function __construct(protected Factory $factory, $count = null)
    {
        $this->count = $count ?? 1;
    }

    public function buildSchema($attributes): static
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

    public function getSchema(): array
    {
        return $this->schema;
    }

    public function getState(): mixed
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

    public function generate(): array
    {   
        if ($this->data) {
            return $this->data;
        }

        $table = $this->factory->modelName()::make()->getTable();

        $prompt = $this->factory->getPrompt();

        $singularTable = str()->singular($table);

        $schema = new ArraySchema(
            name: $table,
            description: 'An array of '.$this->count.' '.str($singularTable)->plural($this->count),
            items: new ObjectSchema(
                name: $singularTable,
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

    public function describe($description, $required = true): IndustryDefinition
    {
        return new IndustryDefinition($description, $required);
    }
}
