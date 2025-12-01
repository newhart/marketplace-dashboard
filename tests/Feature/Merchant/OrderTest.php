<?php

namespace Tests\Feature\Merchant;

use App\Models\Merchant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private function createMerchant(User $user)
    {
        return Merchant::create([
            'user_id' => $user->id,
            'manager_lastname' => 'Doe',
            'manager_firstname' => 'John',
            'mobile_phone' => '1234567890',
            'landline_phone' => '0987654321',
            'business_address' => '123 Main St',
            'business_city' => 'New York',
            'business_postal_code' => '10001',
            'business_type' => 'Retail',
            'business_description' => 'A test merchant',
            'approval_status' => 'approved',
        ]);
    }

    public function test_merchant_can_view_their_orders()
    {
        // Create a merchant user
        $merchantUser = User::factory()->create();
        $this->createMerchant($merchantUser);

        // Create a product for this merchant
        $product = Product::factory()->create(['user_id' => $merchantUser->id]);

        // Create an order with this product
        $order = Order::factory()->create();
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100,
        ]);

        // Authenticate as merchant
        $this->actingAs($merchantUser);

        // Request orders
        $response = $this->getJson(route('merchant.orders.index'));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'orders.data')
            ->assertJsonPath('orders.data.0.id', $order->id);
    }

    public function test_merchant_cannot_view_others_orders()
    {
        // Create a merchant user
        $merchantUser = User::factory()->create();
        $this->createMerchant($merchantUser);

        // Create another user and product
        $otherUser = User::factory()->create();
        $otherProduct = Product::factory()->create(['user_id' => $otherUser->id]);

        // Create an order with other product
        $order = Order::factory()->create();
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $otherProduct->id,
            'quantity' => 1,
            'price' => 100,
        ]);

        // Authenticate as merchant
        $this->actingAs($merchantUser);

        // Request orders
        $response = $this->getJson(route('merchant.orders.index'));

        $response->assertStatus(200)
            ->assertJsonCount(0, 'orders.data');
    }
}
