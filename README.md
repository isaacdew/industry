# Industry

> Industry is still a work in progress and the API may change. If you're interested in this project, give it a try and feel free to provide some suggestions or open a PR!

Industry is a composer package that allows you to integrate your Laravel Eloquent Factories with AI to seed your database with realistic string data. This can be useful for product demos and manual QA.

It's built using [Prism](https://prismphp.com/) (you should check it out).

## Installation

You can install Industry via composer:

```bash
composer require isaacdew/industry --dev
```

## Quick Start

Update your `.env` file to set your provider, model and any related variables for your provider:

```dotenv
INDUSTRY_PROVIDER=ollama # options: anthropic, deepseek, gemini, openai (see https://prismphp.com/ for more options)
INDUSTRY_MODEL=llama3.2

# anthropic
ANTHROPIC_API_KEY=
ANTHROPIC_API_VERSION=

# deepseek
DEEPSEEK_API_KEY=
DEEPSEEK_URL=

# gemini
GEMINI_API_KEY=

# openai
OPENAI_API_KEY=
OPENAI_ORGANIZATION= # optional
```

Add the `WithIndustry` trait to your factory class, define a `protected $prompt` with your factory's base prompt and then call `$this->industry->describe` passing a description for each field you need generated text for. See [Usage](#usage) for an example.

Then call your factory as you normally would!

```php
MenuItem::factory(5)->create();
```

## Configuration

You can set Industry defaults in your `.env` file:

```dotenv
INDUSTRY_PROVIDER=ollama
INDUSTRY_MODEL=llama3.2
INDUSTRY_CACHE_ENABLED=true
INDUSTRY_CACHE_STRATEGY=recycle
```

If you need to, you can publish Industry's config:

```bash
php artisan vendor:publish --tag="industry-config"
```

And publish Prism's config to set provider config:
```bash
php artisan vendor:publish --tag="prism-config"
```

### Overriding defaults for specific factories

You can override your config for specifc factories by defining a `configureIndustry` method on your factory that takes an `Isaacdew\Industry\Industry` instance. Then, inside of that method, calling the `->setConfig()` method on the instance. Here's an example:

```php
class RecipeFactory extends Factory {
    // ...

    public function configureIndustry(Industry $industry)
    {
        $industry->setConfig([
            'provider' => Provider::OpenAI,
            'model' => 'gpt-5'
        ]);
    }
}
```

You can also override the default config for specifc factory calls using the `->tapIndustry()` method.

```php
Recipe::factory()
    ->tapIndustry(
        fn (Industry $industry) => $industry->setConfig([
            'provider' => Provider::OpenAI,
            'model' => 'gpt-5'
        ])
    )
    ->create();
```

## Usage

To use Industry, use the `WithIndustry` trait on your factory, define a `$prompt` property with the factory's base prompt and then call `$this->industry->describe` passing a description for each field you need generated text for.

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

Industry will never make calls to your LLM during tests. By default, the field description is used as its value. You can set state specifically for tests by using the `->fallback()` method like so:

```php
public function definition(): array
{
    return [
        'name' => $this->industry->describe('The name of the menu item.')
            ->fallback($this->faker->words()),
        'description' => $this->industry->describe('A description of the menu item.')
            ->fallback($this->faker->sentence()),
        'calories' => random_int(100, 500),
        'price' => $this->faker->randomFloat(2, max: 50)
    ];
}
```

### Caching

By default, Industry caches the data returned from your provider to avoid repeat calls with each re-seed. The cache for your factory is invalidated automatically when changes are made to its prompt and/or field descriptions.

**Not recommended:** If you want to disable caching by default, you can set the `INDUSTRY_CACHE_ENABLED` env variable to false. You can also disable/enable it in certain situations using the `->tapIndustry()` method like so:

```php
use Isaacdew\Industry\Industry;

MenuItem::factory()
    ->tapIndustry(fn (Industry $industry) => $industry->useCache(false))
    ->make();

```

#### Strategies

There are two caching strategies that Industry supports: `recycle` and `lazy_load`. You can set your default strategy with the `INDUSTRY_CACHE_STRATEGY` env variable.

##### recycle

This is the default and the safest option. It takes whatever your LLM provides and will reuse existing cached entries in a round-robin fashion.

For example, if there are 10 menu items in the cache and you call `MenuItem::factory(20)->create()`, another call will not be made to the LLM for the missing 10. Instead, the second 10 items will be a repeat of the first.

##### lazy_load

This strategy will always ask your LLM for more data if there's not enough in the cache to satisfy the call unless you specify a limit. You can set a global limit via the `INDUSTRY_CACHE_LAZY_LOAD_UNTIL` in your env file. You can also set it with the `->setConfig()` method on the `Industry` instance:

```php
$industry->setConfig('cache.lazy_load_until', 50);
```

#### Clearing the cache

You can clear the cache for a factory by running the `industry:clear-cache` command. It will ask which factory the cache should be cleared for. To clear all caches, use the `--all` flag.

### Some notes on prompts & structured output

1. **Be sure to use a model that supports structured output.** Industry depends on structured output from the LLM which not all models are great at.

2. **You may have to adjust the prompt to get only the structured data back.** Sometimes you may run into issues where your model generates the structured data perfectly well but returns some extraneous text like "Here you go:".

### Modifying the Prism request

You can modify the request that Prism makes to your LLM provider using the `beforeRequest` method on the industry instance. See [the Prism docs](https://prismphp.com/core-concepts/structured-output.html) for more on what's possible.

```php
use \Prism\Prism\Structured\PendingRequest;

//...

public function configureIndustry(Industry $industry)
{
    $industry->beforeRequest(function (PendingRequest $prismRequest) {
        // Enable OpenAI strict mode
        $prismRequest->withProviderOptions([
            'schema' => [
                'strict' => true
            ]
        ]);
    });

    return $this;
}
```

## FAQ

### Does Industry make calls to my LLM for tests?

**No.** See [Running with tests](#running-with-tests).

### Does Industry make a call to my LLM for each model created by the factory?

**No.** Industry will make a request to your LLM for an array of the values it needs to generate all the models in a single call. For example, if you call `MenuItem::factory(10)->create()`, Industry will build a request to your LLM asking for an array of 10 objects containing the fields you defined on your factory with `$this->industry->describe()`.

### Can I use Industry to generate non-string data in my factory?

**No.** Industry doesn't support anything other than string data. Industry tries to make your LLM do as little work as possible while still getting realistic data. For any non-strings (and even some types of strings), `faker` can provide data just as realistic, much more efficiently.

### Does Industry cache results from the LLM?

**Yes.** Caching is enabled by default. See [Caching](#caching).

## Roadmap

Industry is still in the early stages of dev but there are a few more things I hope to add in the coming weeks (10/12/2025):

- [x] A suite of passing tests with > 90% coverage (of course!)
- [x] The ability to cache generated data to avoid provider calls for every reseed (*in progress*)
- [x] A way to set Industry defaults on a factory and override them later
- [ ] A beta release

## License
The MIT License (MIT)
