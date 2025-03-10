<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->count(10)->create();

        $categories = Category::factory()->count(15)->create();

        foreach (User::where('role', 'merchant')->get() as $merchant) {
            Product::factory()
                ->count(35)
                ->for($merchant, 'user')
                ->for($categories->random(), 'category')
                ->create();
        }
    }
}
