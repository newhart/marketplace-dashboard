<?php

namespace Tests\Feature\Api;

use App\Models\PromotionalBanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionalBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_retrieve_active_banners()
    {
        // Create active banner
        PromotionalBanner::create([
            'title' => 'Active Banner',
            'subtitle' => 'Subtitle',
            'image' => 'banner.jpg',
            'is_active' => true,
            'display_order' => 1,
        ]);

        // Create inactive banner
        PromotionalBanner::create([
            'title' => 'Inactive Banner',
            'image' => 'banner2.jpg',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/promotional-banners');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Active Banner'])
            ->assertJsonMissing(['title' => 'Inactive Banner']);
    }

    public function test_can_retrieve_banners_within_date_range()
    {
        // Current banner
        PromotionalBanner::create([
            'title' => 'Current Banner',
            'image' => 'current.jpg',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
        ]);

        // Future banner
        PromotionalBanner::create([
            'title' => 'Future Banner',
            'image' => 'future.jpg',
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(5),
            'is_active' => true,
        ]);

        // Past banner
        PromotionalBanner::create([
            'title' => 'Past Banner',
            'image' => 'past.jpg',
            'start_date' => now()->subDays(5),
            'end_date' => now()->subDays(2),
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/promotional-banners');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Current Banner'])
            ->assertJsonMissing(['title' => 'Future Banner'])
            ->assertJsonMissing(['title' => 'Past Banner']);
    }

    public function test_can_retrieve_banner_with_product_id()
    {
        $product = \App\Models\Product::factory()->create();

        PromotionalBanner::create([
            'title' => 'Product Banner',
            'image' => 'product.jpg',
            'is_active' => true,
            'product_id' => $product->id,
        ]);

        $response = $this->getJson('/api/promotional-banners');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'title' => 'Product Banner',
                'product_id' => (string) $product->id,
            ]);
    }
}
