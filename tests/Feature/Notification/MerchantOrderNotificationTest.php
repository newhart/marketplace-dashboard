<?php

namespace Tests\Feature\Notification;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\MerchantOrderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MerchantOrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_receives_notification_when_order_is_created()
    {
        Notification::fake();

        // Create a merchant user
        $merchantUser = User::factory()->create();

        // Create a product for this merchant
        $product = Product::factory()->create(['user_id' => $merchantUser->id]);

        // Create a customer
        $customer = User::factory()->create();

        // Create an order
        $order = Order::factory()->create(['user_id' => $customer->id]);

        // Simulate order item creation and notification
        $merchantUser->notify(new MerchantOrderNotification($order, $product));

        // Assert notification was sent
        Notification::assertSentTo(
            [$merchantUser],
            MerchantOrderNotification::class,
            function ($notification, $channels) use ($order, $product) {
                // Check channels
                $this->assertContains('mail', $channels);
                $this->assertContains('database', $channels);
                $this->assertContains('broadcast', $channels);

                // Check notification data
                $broadcastData = $notification->toBroadcast($notification);
                $this->assertEquals($order->id, $broadcastData['order_id']);
                $this->assertEquals($product->id, $broadcastData['product_id']);
                $this->assertEquals('merchant_order', $broadcastData['type']);

                return true;
            }
        );
    }

    public function test_notification_contains_correct_broadcast_data()
    {
        // Create a merchant user
        $merchantUser = User::factory()->create(['name' => 'Test Merchant']);

        // Create a product
        $product = Product::factory()->create([
            'user_id' => $merchantUser->id,
            'name' => 'Test Product'
        ]);

        // Create a customer
        $customer = User::factory()->create(['name' => 'Test Customer']);

        // Create an order
        $order = Order::factory()->create(['user_id' => $customer->id]);

        // Create notification
        $notification = new MerchantOrderNotification($order, $product);

        // Get broadcast data
        $broadcastData = $notification->toBroadcast($merchantUser);

        // Assert broadcast data structure
        $this->assertArrayHasKey('order_id', $broadcastData);
        $this->assertArrayHasKey('product_id', $broadcastData);
        $this->assertArrayHasKey('product_name', $broadcastData);
        $this->assertArrayHasKey('message', $broadcastData);
        $this->assertArrayHasKey('type', $broadcastData);

        $this->assertEquals($order->id, $broadcastData['order_id']);
        $this->assertEquals($product->id, $broadcastData['product_id']);
        $this->assertEquals('Test Product', $broadcastData['product_name']);
        $this->assertEquals('merchant_order', $broadcastData['type']);
    }
}
