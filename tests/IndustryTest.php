<?php

namespace Isaacdew\Industry\Tests;

use Isaacdew\Industry\Industry;
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
    public function test_lazy_load_calls_llm_after_cache_exhausted()
    {
        $fakeResponse1 = StructuredResponseFake::make()
            ->withText(json_encode([
                [
                    'name' => 'Test Menu Item 1',
                    'description' => 'Test description 1',
                ],
            ], JSON_THROW_ON_ERROR))
            ->withStructured([
                [
                    'name' => 'Test Menu Item 1',
                    'description' => 'Test description 1',
                ],
            ])
            ->withFinishReason(FinishReason::Stop);

        $fakeResponse2 = StructuredResponseFake::make()
            ->withText(json_encode([
                [
                    'name' => 'Test Menu Item 2',
                    'description' => 'Test description 2',
                ],
            ], JSON_THROW_ON_ERROR))
            ->withStructured([
                [
                    'name' => 'Test Menu Item 2',
                    'description' => 'Test description 2',
                ],
            ])
            ->withFinishReason(FinishReason::Stop);

        $prism = Prism::fake([$fakeResponse1, $fakeResponse2]);

        $menuItem1 = MenuItem::factory()
            ->tapIndustry(fn ($industry) => $industry->useCache()->forceGeneration())
            ->make();

        $menuItems = MenuItem::factory(2)
            ->tapIndustry(
                fn ($industry) => $industry
                    ->useCache()
                    ->forceGeneration()
                    ->setConfig('cache.strategy', 'lazy_load')
            )
            ->make();

        $prism->assertCallCount(2);

        // Assert that the first item is from cache 
        $this->assertEquals($menuItem1->name, $menuItems[0]->name);
        $this->assertEquals($menuItem1->description, $menuItems[0]->description);
        
        // ...and the second from the new LLM call
        $this->assertEquals('Test Menu Item 2', $menuItems[1]->name);
        $this->assertEquals('Test description 2', $menuItems[1]->description);
    }

    public function test_lazy_load_until_calls_llm_after_cache_exhausted_until_max_is_reached()
    {
        $fakeResponse1 = StructuredResponseFake::make()
            ->withText(json_encode([
                [
                    'name' => 'Test Menu Item 1',
                    'description' => 'Test description 1',
                ],
            ], JSON_THROW_ON_ERROR))
            ->withStructured([
                [
                    'name' => 'Test Menu Item 1',
                    'description' => 'Test description 1',
                ],
            ])
            ->withFinishReason(FinishReason::Stop);

        $fakeResponse2 = StructuredResponseFake::make()
            ->withText(json_encode([
                [
                    'name' => 'Test Menu Item 2',
                    'description' => 'Test description 2',
                ],
            ], JSON_THROW_ON_ERROR))
            ->withStructured([
                [
                    'name' => 'Test Menu Item 2',
                    'description' => 'Test description 2',
                ],
            ])
            ->withFinishReason(FinishReason::Stop);

        $prism = Prism::fake([$fakeResponse1, $fakeResponse2]);

        $menuItem1 = MenuItem::factory()
            ->tapIndustry(fn ($industry) => $industry->useCache()->forceGeneration())
            ->make();

        // Ask for 3 items, but only have 1 in cache and lazy_load_until set to 2
        $menuItems = MenuItem::factory(3)
            ->tapIndustry(
                fn ($industry) => $industry
                    ->useCache()
                    ->forceGeneration()
                    ->setConfig('cache.strategy', 'lazy_load')
                    ->setConfig('cache.lazy_load_until', 2)
            )
            ->make();

        // We expect 2 calls: one to get the first cached item, and one to get the second item from LLM
        $prism->assertCallCount(2);

        // Assert that the first item is from cache 
        $this->assertEquals($menuItem1->name, $menuItems[0]->name);
        $this->assertEquals($menuItem1->description, $menuItems[0]->description);
        
        // ...and the second from the new LLM call
        $this->assertEquals('Test Menu Item 2', $menuItems[1]->name);
        $this->assertEquals('Test description 2', $menuItems[1]->description);
    }

    public function test_repeat_calls_use_cached_data()
    {
        $fakeResponse1 = StructuredResponseFake::make()
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

        $fakeResponse2 = StructuredResponseFake::make()
            ->withText(json_encode([
                [
                    'name' => 'Another Menu Item',
                    'description' => 'Another description',
                ],
            ], JSON_THROW_ON_ERROR))
            ->withStructured([
                [
                    'name' => 'Another Menu Item',
                    'description' => 'Another description',
                ],
            ])
            ->withFinishReason(FinishReason::Stop);

        Prism::fake([$fakeResponse1, $fakeResponse2]);

        $menuItem1 = MenuItem::factory()
            ->tapIndustry(fn ($industry) => $industry->useCache()->forceGeneration())
            ->make();

        $menuItem2 = MenuItem::factory()
            ->tapIndustry(fn ($industry) => $industry->useCache()->forceGeneration())
            ->make();

        $this->assertEquals($menuItem1->name, $menuItem2->name);
        $this->assertEquals($menuItem1->description, $menuItem2->description);
    }

    public function test_can_override_industry_attributes()
    {
        $menuItem = MenuItem::factory()
            ->tapIndustry(fn ($industry) => $industry->forceGeneration())
            ->make([
                'name' => 'Custom Name',
                'description' => 'Custom Description',
            ]);

        $this->assertEquals('Custom Name', $menuItem->name);
        $this->assertEquals('Custom Description', $menuItem->description);
    }

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
            ->tapIndustry(function (Industry $industry) {
                $industry->forceGeneration();
            });

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
            ->tapIndustry(function (Industry $industry) {
                $industry
                    ->forceGeneration();
            })
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
            public function configureIndustry(Industry $industry): static
            {
                $industry->beforeRequest(function (PendingRequest $prismRequest) {
                    // Enable OpenAI strict mode
                    $prismRequest->using(Provider::OpenAI, 'fake-model');
                });

                return $this;
            }
        };

        $factory
            ->new()
            ->tapIndustry(function (Industry $industry) {
                $industry->forceGeneration();
            })
            ->make();

        $fake->assertRequest(function ($requests) {
            $this->assertEquals(Provider::OpenAI->value, $requests[0]->provider());
            $this->assertEquals('fake-model', $requests[0]->model());
        });
    }

    public function test_clear_cache_command_clears_all_cache()
    {
        $cacheManager = new \Isaacdew\Industry\CacheManager();

        $cacheManager->getConnection()
            ->table('cache_meta')
            ->insert([
                'factory' => MenuItemFactory::class,
                'object_signature' => 'test_signature',
            ]);

        $this->artisan('industry:clear-cache --all')
            ->expectsOutput('All cached data has been cleared.')
            ->assertExitCode(0);

        $this->assertFalse(
            $cacheManager->getConnection()
                ->table('cache_meta')
                ->exists()
        );
        
        $this->assertFalse(
            $cacheManager->getConnection()
                ->table('cache_data')
                ->exists()
        );
    }

    public function test_clear_cache_command_clears_specific_factory_cache()
    {
        $cacheManager = new \Isaacdew\Industry\CacheManager();

        $cacheManager->getConnection()
            ->table('cache_meta')
            ->insert([
                [
                    'factory' => MenuItemFactory::class,
                    'object_signature' => 'test_signature',
                ],
                [
                    'factory' => 'AnotherFactory',
                    'object_signature' => 'another_signature',
                ]
            ]);

        $cacheManager->getConnection()
            ->table('cache_data')
            ->insert([
                [
                    'meta_id' => 1,
                    'content' => '',
                ],
                [
                    'meta_id' => 2,
                    'content' => '',
                ],
            ]);

        $this->artisan('industry:clear-cache')
            ->expectsQuestion(
                'Select the factory whose cache you want to clear',
                MenuItemFactory::class
            )
            ->expectsOutput('Cache for factory \''.MenuItemFactory::class.'\' has been cleared.')
            ->assertExitCode(0);

        $this->assertFalse(
            $cacheManager->getConnection()
                ->table('cache_meta')
                ->where('factory', MenuItemFactory::class)
                ->exists()
        );

        $this->assertFalse(
            $cacheManager->getConnection()
                ->table('cache_data')
                ->where('meta_id', 1)
                ->exists()
        );

        $this->assertTrue(
            $cacheManager->getConnection()
                ->table('cache_data')
                ->where('id', 2)
                ->exists()
        );
    }
}
