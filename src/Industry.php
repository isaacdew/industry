<?php

namespace Isaacdew\Industry;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Isaacdew\Industry\Concerns\InteractsWithCache;
use Prism\Prism\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Structured\PendingRequest;
use Prism\Prism\Structured\Response;

class Industry
{
    use InteractsWithCache;

    protected ?PendingRequest $prismRequest = null;

    protected array $properties = [];

    protected array $requiredProperties = [];

    protected array $beforeRequest = [];

    protected $data = null;

    protected int $stateIndex = 0;

    protected ?int $count = null;

    protected bool $forceGeneration = false;

    public function __construct(protected Factory $factory, protected array $config, $count = null)
    {
        $this->count = $count ?? 1;

        $this->factory->configureIndustry($this);
    }

    public function buildSchema(array $attributes): static
    {
        if (! empty($this->properties)) {
            return $this;
        }

        foreach ($attributes as $attribute => $definition) {
            if (! $definition instanceof IndustryDefinition) {
                continue;
            }

            $this->properties[] = $definition->toPrismSchema($attribute);

            if ($definition->isRequired()) {
                $this->requiredProperties[] = $attribute;
            }
        }

        return $this;
    }

    public function getState(): mixed
    {
        if (! $this->data) {
            $this->data = $this->generate();
        }

        // Loop back to the beginning of the array if we run out
        if (! isset($this->data[$this->stateIndex])) {
            $this->stateIndex = 0;
        }

        $data = $this->data[$this->stateIndex];

        $this->stateIndex++;

        return $data;
    }

    protected function generate(): array
    {
        if ($this->data) {
            return $this->data;
        }

        $table = $this->factory->modelName()::make()->getTable();

        $prompt = $this->factory->getPrompt();

        $singularTable = str()->singular($table);

        $objectSchema = new ObjectSchema(
            name: $singularTable,
            description: $prompt,
            properties: $this->properties,
            requiredFields: $this->requiredProperties
        );

        if ($this->config['cache']['enabled']) {
            $objectSignature = $this->getCache()->objectSignature($singularTable, $prompt, $objectSchema);

            return $this->getCache()->get(
                get_class($this->factory),
                $objectSignature,
                $this->count,
                function ($needed) use ($objectSchema, $prompt, $table) {
                    $this->buildPrismRequest($prompt);

                    return $this->executePrismRequest($objectSchema, $table, $needed)->structured;
                }
            );
        }

        $this->buildPrismRequest($prompt);

        return $this->executePrismRequest($objectSchema, $table)->structured;
    }

    public function describe($description, $required = true): IndustryDefinition
    {
        return new IndustryDefinition($description, $required);
    }

    public function forceGeneration($bool = true)
    {
        $this->forceGeneration = $bool;

        return $this;
    }

    public function getForceGeneration(): bool
    {
        return $this->forceGeneration;
    }

    public function beforeRequest(callable $callback): static
    {
        $this->beforeRequest[] = $callback;

        return $this;
    }

    public function setConfig(array|string $config, $value = null): static
    {
        if (is_string($config)) {
            Arr::set($this->config, $config, $value);

            return $this;
        }

        $this->config = array_merge($this->config, $config);

        return $this;
    }

    protected function buildPrismRequest(string $prompt): PendingRequest
    {
        if ($this->prismRequest) {
            return $this->prismRequest;
        }

        $prismRequest = Prism::structured()
            ->using($this->config['provider'], $this->config['model']);

        // Process before request callbacks
        if (! empty($this->beforeRequest)) {
            foreach ($this->beforeRequest as $callback) {
                $callback($prismRequest);
            }
        }

        $this->prismRequest = $prismRequest
            ->withPrompt($prompt);

        return $this->prismRequest;
    }

    protected function executePrismRequest(ObjectSchema $schema, string $table, ?int $count = null): Response
    {
        $count ??= $this->count;

        return $this->prismRequest
            ->withSchema(new ArraySchema(
                name: $table,
                description: 'An array of '.$count.' '.str($table)->plural($count),
                items: $schema
            ))
            ->asStructured();
    }
}
