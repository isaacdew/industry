<?php

namespace Isaacdew\Industry\Tests;

use Isaacdew\Industry\IndustryDefinition;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Structured\PendingRequest;
use Prism\Prism\Testing\StructuredResponseFake;
use Workbench\App\Models\MenuItem;
use Workbench\Database\Factories\MenuItemFactory;

class IndustryTest extends TestCase
{
    public function test_schema_is_correctly_formed()
    {
        $fakeResponse = StructuredResponseFake::make()
            ->withText(json_encode([
                [
                    'name' => 'Test Menu Item',
                    'description' => 'Test description',
                ],
            ], JSON_THROW_ON_ERROR))
            ->withStructured([
                [
                    'name' => 'Test Menu Item',
                    'description' => 'Test description',
                ],
            ])
            ->withFinishReason(FinishReason::Stop);

        $fake = Prism::fake([$fakeResponse]);

        $factory = MenuItem::factory()
            ->forceGeneration();

        $factory->make();

        $fake->assertRequest(function ($requests) use ($factory) {
            $schema = $requests[0]->schema();

            $this->assertInstanceOf(ArraySchema::class, $schema);
            $this->assertInstanceOf(ObjectSchema::class, $schema->items);
            $this->assertEquals($factory->prompt, $schema->items->description);

            $industryDefinitions = array_filter($factory->definition(), fn ($value) => $value instanceof IndustryDefinition);

            $this->assertCount(count($industryDefinitions), $schema->items->properties);

            foreach ($schema->items->properties as $property) {
                $this->assertInstanceOf(StringSchema::class, $property);
                $this->assertEquals($property->description, $industryDefinitions[$property->name]->getDescription());
            }
        });
    }

    public function test_it_doesnt_call_llm_during_tests()
    {
        $fake = Prism::fake();

        MenuItem::factory()
            ->make();

        $fake->assertCallCount(0);
    }

    public function test_force_generation_calls_llm_during_tests()
    {
        $fakeResponse = StructuredResponseFake::make()
            ->withText(json_encode([
                [
                    'name' => 'Test Menu Item',
                    'description' => 'Test description',
                ],
            ], JSON_THROW_ON_ERROR))
            ->withStructured([
                [
                    'name' => 'Test Menu Item',
                    'description' => 'Test description',
                ],
            ])
            ->withFinishReason(FinishReason::Stop);

        $fake = Prism::fake([$fakeResponse]);

        $menuItem = MenuItem::factory()
            ->forceGeneration()
            ->make();

        $this->assertEquals('Test Menu Item', $menuItem->name);
        $this->assertEquals('Test description', $menuItem->description);

        $fake->assertCallCount(1);
    }

    public function test_prism_request_can_be_configured()
    {
        $fakeResponse = StructuredResponseFake::make()
            ->withText(json_encode([
                [
                    'name' => 'Test Menu Item',
                    'description' => 'Test description',
                ],
            ], JSON_THROW_ON_ERROR))
            ->withStructured([
                [
                    'name' => 'Test Menu Item',
                    'description' => 'Test description',
                ],
            ])
            ->withFinishReason(FinishReason::Stop);

        $fake = Prism::fake([$fakeResponse]);

        $factory = new class extends MenuItemFactory
        {
            public function configurePrism(PendingRequest $pendingRequest)
            {
                // Override the provider and such
                $pendingRequest
                    ->using(Provider::OpenAI, 'fake-model');
            }
        };

        $factory
            ->forceGeneration()
            ->make();

        $fake->assertRequest(function ($requests) {
            $this->assertEquals(Provider::OpenAI->value, $requests[0]->provider());
            $this->assertEquals('fake-model', $requests[0]->model());
        });
    }
}
