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
            'image' => $this->faker->imageUrl(640, 480, 'products', true),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'rating' => $this->faker->randomFloat(1, 0, 5),
            'category_id' => Category::factory(), 
            'user_id' => User::factory()
        ];
    }
}
