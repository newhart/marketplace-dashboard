<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use App\Services\DistanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransporterController extends Controller
{
    protected DistanceService $distanceService;

    public function __construct(DistanceService $distanceService)
    {
        $this->distanceService = $distanceService;
    }

    /**
     * Obtenir les transporteurs disponibles avec leurs distances calculées
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function available(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'delivery_address_id' => 'required|exists:addresses,id',
            'shop_id' => 'nullable|exists:boutiques,id', // Optionnel : ID de la boutique
            'order_id' => 'nullable|exists:orders,id', // Optionnel : ID de la commande pour obtenir le shop
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $deliveryAddress = Address::findOrFail($request->delivery_address_id);
            
            // Déterminer l'adresse du shop
            $shopAddress = $this->getShopAddress($request);

            if (!$shopAddress) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de déterminer l\'adresse du shop. Veuillez fournir shop_id ou order_id.'
                ], 400);
            }

            // Récupérer tous les transporteurs actifs
            $transporters = User::where('type', User::TYPE_TRANSPORTER)
                ->where('is_active', true)
                ->with('transporterPriceSetting')
                ->get();

            $availableTransporters = [];

            foreach ($transporters as $transporter) {
                // Calculer la distance du shop au transporteur
                $distanceFromShop = $this->distanceService->calculateDistance(
                    $shopAddress,
                    $transporter
                );

                // Calculer la distance du transporteur à l'adresse de livraison
                $distanceFromDelivery = $this->distanceService->calculateDistance(
                    $transporter,
                    $deliveryAddress
                );

                // Si les distances ne peuvent pas être calculées, on peut quand même inclure le transporteur
                // mais avec des valeurs null
                if ($distanceFromShop === null && $distanceFromDelivery === null) {
                    // On peut quand même inclure le transporteur mais avec un flag
                    continue; // Ou on peut l'inclure avec available = false
                }

                $totalDistance = ($distanceFromShop ?? 0) + ($distanceFromDelivery ?? 0);

                // Calculer le prix
                $price = $this->calculatePrice($transporter, $totalDistance);

                // Estimer le temps (en minutes) - environ 2 minutes par km en ville
                $estimatedTime = $this->estimateTime($totalDistance);

                // Déterminer si le transporteur est disponible
                $available = $this->isTransporterAvailable($transporter);

                $availableTransporters[] = [
                    'id' => $transporter->id,
                    'name' => $transporter->name,
                    'email' => $transporter->email,
                    'phone' => $transporter->phone_number ?? $transporter->phone ?? null,
                    'company_name' => $transporter->company_name,
                    'vehicle_type' => $transporter->vehicle_type,
                    'distance_from_shop' => $distanceFromShop,
                    'distance_from_delivery' => $distanceFromDelivery,
                    'total_distance' => $totalDistance,
                    'price' => $price,
                    'estimated_time' => $estimatedTime, // en minutes
                    'estimated_time_formatted' => $this->formatTime($estimatedTime),
                    'available' => $available,
                    'rating' => $this->getTransporterRating($transporter),
                ];
            }

            // Trier par distance totale (plus proche en premier)
            usort($availableTransporters, function ($a, $b) {
                return ($a['total_distance'] ?? PHP_INT_MAX) <=> ($b['total_distance'] ?? PHP_INT_MAX);
            });

            return response()->json([
                'success' => true,
                'data' => $availableTransporters,
                'meta' => [
                    'total' => count($availableTransporters),
                    'delivery_address' => [
                        'id' => $deliveryAddress->id,
                        'full_address' => $deliveryAddress->full_address,
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des transporteurs disponibles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir l'adresse du shop
     *
     * @param Request $request
     * @return \App\Models\Boutique|Address|null
     */
    protected function getShopAddress(Request $request)
    {
        // Si shop_id est fourni, utiliser la boutique
        if ($request->has('shop_id')) {
            return \App\Models\Boutique::find($request->shop_id);
        }

        // Si order_id est fourni, obtenir la boutique depuis la commande
        if ($request->has('order_id')) {
            $order = Order::with('items.product.user.merchant.boutiques')->find($request->order_id);
            
            if ($order && $order->items->isNotEmpty()) {
                // Prendre la première boutique du premier marchand
                $firstItem = $order->items->first();
                if ($firstItem->product && $firstItem->product->user && $firstItem->product->user->merchant) {
                    $boutique = $firstItem->product->user->merchant->boutiques()->first();
                    if ($boutique) {
                        return $boutique;
                    }
                }
            }
        }

        // En dernier recours, essayer de trouver une boutique par défaut
        // ou retourner null si impossible
        return null;
    }

    /**
     * Calculer le prix de livraison
     *
     * @param User $transporter
     * @param float $totalDistance
     * @return float
     */
    protected function calculatePrice(User $transporter, float $totalDistance): float
    {
        $priceSetting = $transporter->transporterPriceSetting;

        if (!$priceSetting) {
            // Prix par défaut si pas de configuration
            $pricePerKm = 500; // 500 F par km par défaut
            $minimumAmount = 2000; // 2000 F minimum
        } else {
            $pricePerKm = $priceSetting->price_per_km ?? 500;
            $minimumAmount = $priceSetting->minimum_amount ?? 2000;
        }

        $calculatedPrice = $totalDistance * $pricePerKm;

        return max($calculatedPrice, $minimumAmount);
    }

    /**
     * Estimer le temps de livraison en minutes
     *
     * @param float $distance Distance en kilomètres
     * @return int Temps estimé en minutes
     */
    protected function estimateTime(float $distance): int
    {
        // Estimation : 2 minutes par km en moyenne (en ville)
        // Pour les longues distances, on peut ajuster
        $minutesPerKm = 2;
        
        if ($distance > 50) {
            // Pour les longues distances, vitesse moyenne plus élevée
            $minutesPerKm = 1.5;
        }

        return (int) round($distance * $minutesPerKm);
    }

    /**
     * Formater le temps en format lisible
     *
     * @param int $minutes
     * @return string
     */
    protected function formatTime(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($mins === 0) {
            return $hours . ' h';
        }

        return $hours . ' h ' . $mins . ' min';
    }

    /**
     * Vérifier si le transporteur est disponible
     *
     * @param User $transporter
     * @return bool
     */
    protected function isTransporterAvailable(User $transporter): bool
    {
        // Vérifier si le transporteur est actif
        if (!$transporter->is_active) {
            return false;
        }

        // On peut ajouter d'autres vérifications ici :
        // - Vérifier s'il a des livraisons en cours
        // - Vérifier ses heures de disponibilité
        // - etc.

        return true;
    }

    /**
     * Obtenir la note moyenne du transporteur
     *
     * @param User $transporter
     * @return float|null
     */
    protected function getTransporterRating(User $transporter): ?float
    {
        // Si vous avez un système de notation, l'implémenter ici
        // Pour l'instant, retourner null
        return null;
    }
}
