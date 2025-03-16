<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'description' => $this->faker->paragraph,
            'image' => asset('images/products/' . rand(1, 17) . '.png'),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'unity' => $this->faker->randomElement(['kg', 'lt', 'un']),
            'short_description' => $this->faker->sentence,
            'rating' => $this->faker->randomFloat(1, 0, 5),
            'category_id' => Category::factory(), 
            'user_id' => User::factory()
        ];
    }
}
