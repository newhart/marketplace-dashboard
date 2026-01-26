<?php

namespace Tests\Feature\Notification;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderValidatedForTransporterNotification;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderValidatedForTransporterNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = app(OrderService::class);
    }

    /**
     * Test que le transporteur assigné reçoit une notification push et un email
     * quand tous les items sont validés et que le statut change en "validated"
     */
    public function test_transporter_receives_push_and_email_when_all_items_validated()
    {
        Notification::fake();

        // Créer un transporteur actif avec un token FCM
        $transporter = User::factory()->create([
            'type' => User::TYPE_TRANSPORTER,
            'is_active' => true,
            'fcm_token' => 'test-fcm-token-123',
        ]);

        // Créer un client
        $customer = User::factory()->create([
            'type' => User::TYPE_CUSTOMER,
        ]);

        // Créer un commerçant
        $merchant = User::factory()->create([
            'type' => User::TYPE_MERCHANT,
        ]);

        // Créer un produit pour le commerçant
        $product = Product::factory()->create([
            'user_id' => $merchant->id,
        ]);

        // Créer une commande avec un transporteur assigné
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'transporter_id' => $transporter->id,
            'status' => 'pending',
            'total_amount' => 10000,
        ]);

        // Créer un item de commande
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 10000,
            'validated_at' => null,
        ]);

        // Valider l'item de commande (cela devrait déclencher la vérification du statut)
        $this->orderService->validateOrderItem($orderItem->id, $merchant);

        // Recharger la commande pour obtenir le statut à jour
        $order->refresh();

        // Vérifier que le statut de la commande est "validated"
        $this->assertEquals('validated', $order->status);

        // Vérifier que la notification a été envoyée au transporteur avec les bons canaux
        Notification::assertSentTo(
            $transporter,
            OrderValidatedForTransporterNotification::class,
            function ($notification, $channels) {
                // Vérifier que les canaux sont corrects (push et email)
                $this->assertContains('firebase', $channels);
                $this->assertContains('mail', $channels);

                return true;
            }
        );

        // Vérifier qu'aucune notification n'a été envoyée à d'autres transporteurs
        $otherTransporters = User::where('type', User::TYPE_TRANSPORTER)
            ->where('id', '!=', $transporter->id)
            ->get();

        foreach ($otherTransporters as $otherTransporter) {
            Notification::assertNotSentTo(
                $otherTransporter,
                OrderValidatedForTransporterNotification::class
            );
        }
    }

    /**
     * Test que la notification n'est pas envoyée si le transporteur n'est pas actif
     */
    public function test_notification_not_sent_to_inactive_transporter()
    {
        Notification::fake();

        // Créer un transporteur inactif
        $transporter = User::factory()->create([
            'type' => User::TYPE_TRANSPORTER,
            'is_active' => false,
            'fcm_token' => 'test-fcm-token-123',
        ]);

        // Créer un client
        $customer = User::factory()->create([
            'type' => User::TYPE_CUSTOMER,
        ]);

        // Créer un commerçant
        $merchant = User::factory()->create([
            'type' => User::TYPE_MERCHANT,
        ]);

        // Créer un produit pour le commerçant
        $product = Product::factory()->create([
            'user_id' => $merchant->id,
        ]);

        // Créer une commande avec un transporteur inactif assigné
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'transporter_id' => $transporter->id,
            'status' => 'pending',
            'total_amount' => 10000,
        ]);

        // Créer un item de commande
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 10000,
            'validated_at' => null,
        ]);

        // Valider l'item de commande
        $this->orderService->validateOrderItem($orderItem->id, $merchant);

        // Vérifier que la notification n'a PAS été envoyée au transporteur inactif
        Notification::assertNotSentTo(
            $transporter,
            OrderValidatedForTransporterNotification::class
        );
    }

    /**
     * Test que la notification n'est pas envoyée si aucun transporteur n'est assigné
     */
    public function test_notification_not_sent_when_no_transporter_assigned()
    {
        Notification::fake();

        // Créer un client
        $customer = User::factory()->create([
            'type' => User::TYPE_CUSTOMER,
        ]);

        // Créer un commerçant
        $merchant = User::factory()->create([
            'type' => User::TYPE_MERCHANT,
        ]);

        // Créer un produit pour le commerçant
        $product = Product::factory()->create([
            'user_id' => $merchant->id,
        ]);

        // Créer une commande SANS transporteur assigné
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'transporter_id' => null,
            'status' => 'pending',
            'total_amount' => 10000,
        ]);

        // Créer un item de commande
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 10000,
            'validated_at' => null,
        ]);

        // Valider l'item de commande
        $this->orderService->validateOrderItem($orderItem->id, $merchant);

        // Vérifier qu'aucune notification n'a été envoyée aux transporteurs
        Notification::assertNothingSent();
    }

    /**
     * Test que le contenu de l'email est correct
     */
    public function test_email_content_is_correct()
    {
        // Créer un transporteur actif
        $transporter = User::factory()->create([
            'type' => User::TYPE_TRANSPORTER,
            'is_active' => true,
            'name' => 'Jean Transporteur',
            'fcm_token' => 'test-fcm-token-123',
        ]);

        // Créer un client
        $customer = User::factory()->create([
            'type' => User::TYPE_CUSTOMER,
        ]);

        // Créer une commande
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'transporter_id' => $transporter->id,
            'status' => 'validated',
            'total_amount' => 15000,
        ]);

        // Créer la notification
        $notification = new OrderValidatedForTransporterNotification($order);

        // Obtenir le message email
        $mailMessage = $notification->toMail($transporter);

        // Vérifier le contenu de l'email
        $this->assertEquals('Commande validée - Prête pour la livraison', $mailMessage->subject);
        $this->assertStringContainsString('Bonjour Jean Transporteur!', $mailMessage->greeting);
        $this->assertStringContainsString('Commande #' . $order->id, $mailMessage->introLines[0]);
        $this->assertStringContainsString('15000 F', $mailMessage->introLines[1]);
    }
}
