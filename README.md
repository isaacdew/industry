# Industry

Industry is a composer package that allows you to integrate your Laravel Eloquent Factories with AI to seed your database with realistic string data. This can be useful for product demos and manual QA.

It's built using [Prism](https://prismphp.com/) (you should check it out).

## Installation

You can install Industry via composer:

```bash
composer require isaacdew/industry
```

Then publish Industry's config to set your default provider and model:

```bash
php artisan vendor:publish --tag="industry-config"
```

And publish Prism's config to set provider config:
```bash
php artisan vendor:publish --tag="prism-config"
```

## Usage

To use Industry, use the `WithIndustry` trait on your factory, define a `public $prompt` property with the factory's base prompt and then call `$this->industry->describe` passing a description for each field you need generated text for.

For example:

```php
class MenuItemFactory extends Factory
{
    use WithIndustry;

    protected $prompt = 'Suggest menu items for an italian restaurant.';

    public function definition(): array
    {
        return [
            'name' => $this->industry->describe('The name of the menu item.'),
            'description' => $this->industry->describe('A description of the menu item.'),
            'calories' => random_int(100, 500),
            'price' => $this->faker->randomFloat(2, max: 50)
        ];
    }
}
```

### Running with tests

By default, Industry just returns the field description instead of making a call to your LLM in tests. You can set state specifically for tests by using the `->forTest` method like so:

```php
public function definition(): array
{
    return [
        'name' => $this->industry->describe('The name of the menu item.')
            ->forTest($this->faker->word()),
        'description' => $this->industry->describe('A description of the menu item.')
            ->forTest($this->faker->sentence()),
        'calories' => random_int(100, 500),
        'price' => $this->faker->randomFloat(2, max: 50)
    ];
}
```

### Some notes on prompts & structured output

1. **Be sure to use a model that supports structured output.** Industry depends on structured output from the LLM which not all models are great at.

2. **You may have to adjust the prompt to get only the structured data back.** Sometimes you may run into issues where your model generates the structured data perfectly well but returns some extraneous text like "Here you go:".

### Modifying the Prism request

You can modify the request that Prism makes to your LLM provider by defining a method on your factory called `configurePrism` that takes the Prism request. See [the Prism docs](https://prismphp.com/core-concepts/structured-output.html) for more on what's possible.

```php
use \Prism\Prism\Structured\PendingRequest;

//...

public function configurePrism(PendingRequest $prismRequest)
{
    // Enable OpenAI strict mode
    $prismRequest->withProviderOptions([
        'schema' => [
            'strict' => true
        ]
    ]);
}
```
