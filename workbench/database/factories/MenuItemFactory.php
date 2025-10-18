<?php

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Isaacdew\Industry\WithIndustry;
use Workbench\App\Models\MenuItem;

/**
 * @template TModel of \Workbench\App\Models\MenuItem
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class MenuItemFactory extends Factory
{
    use WithIndustry;

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = MenuItem::class;

    public $prompt = 'Suggest menu items for a pirate-themed restaurant. Return only the array of menu item objects.';

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->industry->describe('The name of the menu item.')
                ->forTest($this->faker->word()),
            'description' => $this->industry->describe('A description of the menu item.')
                ->forTest($this->faker->sentence()),
            'calories' => random_int(100, 500),
            'price' => $this->faker->randomFloat(2, max: 50),
        ];
    }
}
