<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use App\Services\DistanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
            'shop_id' => 'nullable|exists:boutiques,id',
            'order_id' => 'nullable|exists:orders,id',
            'limit' => 'nullable|integer|min:1|max:50', // Limite optionnelle pour les tests
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
            
            // Déterminer l'adresse du shop (optionnel)
            $shopAddress = $this->getShopAddress($request);

            // Récupérer les transporteurs actifs
            // Par défaut, limiter à 5 pour les tests, mais peut être modifié via le paramètre limit
            $limit = $request->input('limit', 5);
            
            $transporters = User::where('type', User::TYPE_TRANSPORTER)
                ->where('is_active', true)
                ->with('transporterPriceSetting')
                ->limit($limit)
                ->get();

            $availableTransporters = [];

            foreach ($transporters as $transporter) {
                // Calculer la distance du shop au transporteur (si shop disponible)
                $distanceFromShop = null;
                if ($shopAddress) {
                    $distanceFromShop = $this->distanceService->calculateDistance(
                        $shopAddress,
                        $transporter
                    );
                }

                // Calculer la distance du transporteur à l'adresse de livraison
                $distanceFromDelivery = $this->distanceService->calculateDistance(
                    $transporter,
                    $deliveryAddress
                );

                // Si la distance ne peut pas être calculée, on inclut quand même le transporteur
                // mais avec des valeurs null et un prix par défaut
                if ($distanceFromDelivery === null) {
                    Log::warning("Impossible de calculer la distance pour le transporteur", [
                        'transporter_id' => $transporter->id,
                        'transporter_name' => $transporter->name,
                        'delivery_address_id' => $deliveryAddress->id,
                    ]);
                }

                // Calculer la distance totale
                // Si on n'a pas la distance, on utilise 0 pour permettre quand même l'affichage
                $totalDistance = ($distanceFromShop ?? 0) + ($distanceFromDelivery ?? 0);

                // Calculer le prix (utiliser le minimum si distance = 0)
                $price = $totalDistance > 0 
                    ? $this->calculatePrice($transporter, $totalDistance)
                    : $this->getMinimumPrice($transporter);

                // Estimer le temps (en minutes) - environ 2 minutes par km en ville
                $estimatedTime = $totalDistance > 0 ? $this->estimateTime($totalDistance) : null;

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
                    'total_distance' => $totalDistance > 0 ? $totalDistance : null,
                    'price' => $price,
                    'estimated_time' => $estimatedTime,
                    'estimated_time_formatted' => $estimatedTime ? $this->formatTime($estimatedTime) : 'Non disponible',
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
                    'shop_address' => $shopAddress ? [
                        'id' => $shopAddress->id ?? null,
                        'type' => $shopAddress instanceof \App\Models\Boutique ? 'boutique' : 'address',
                    ] : null,
                    'note' => $shopAddress ? null : 'Distance du shop non calculée. Fournissez shop_id ou order_id pour une estimation complète.',
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
     * Obtenir le prix minimum pour un transporteur
     *
     * @param User $transporter
     * @return float
     */
    protected function getMinimumPrice(User $transporter): float
    {
        $priceSetting = $transporter->transporterPriceSetting;

        if (!$priceSetting) {
            return 2000; // 2000 F minimum par défaut
        }

        return $priceSetting->minimum_amount ?? 2000;
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

    /**
     * Vérifier que l'utilisateur connecté est un transporteur.
     */
    protected function ensureTransporter(): ?JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isTransporter()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux transporteurs.',
            ], 403);
        }
        return null;
    }

    /**
     * Liste des commandes à livrer pour le transporteur connecté.
     * Statut "validated" = prêtes à être livrées (pas encore delivered).
     */
    public function ordersToDeliver(Request $request): JsonResponse
    {
        if ($err = $this->ensureTransporter()) {
            return $err;
        }

        try {
            $orders = Order::query()
                ->where('transporter_id', Auth::id())
                ->where('status', 'validated')
                ->with(['user', 'items.product'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15));

            $items = $orders->getCollection()->map(function (Order $order) {
                $shippingAddress = Address::where('user_id', $order->user_id)
                    ->where('type', Address::TYPE_SHIPPING)
                    ->orderByDesc('is_default')
                    ->orderByDesc('created_at')
                    ->first();

                return [
                    'id' => $order->id,
                    'reference' => 'C-' . str_pad((string) $order->id, 9, '0', STR_PAD_LEFT),
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'created_at' => $order->created_at?->toIso8601String(),
                    'customer' => [
                        'id' => $order->user?->id,
                        'name' => $order->user?->name,
                        'phone' => $order->user?->phone_number ?? $order->user?->phone ?? null,
                    ],
                    'shipping_address' => $shippingAddress ? [
                        'id' => $shippingAddress->id,
                        'full_address' => $shippingAddress->full_address,
                        'phone' => $shippingAddress->phone,
                    ] : null,
                    'items_count' => $order->items->count(),
                ];
            });

            $orders->setCollection($items);

            return response()->json([
                'success' => true,
                'data' => $orders->items(),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('TransporterController::ordersToDeliver', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes à livrer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Détail d'une commande à livrer (sans exposer le code de livraison).
     */
    public function orderDetail(int $id): JsonResponse
    {
        if ($err = $this->ensureTransporter()) {
            return $err;
        }

        $order = Order::where('transporter_id', Auth::id())
            ->where('status', 'validated')
            ->with(['user', 'items.product'])
            ->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée ou non assignée à vous.',
            ], 404);
        }

        $shippingAddress = Address::where('user_id', $order->user_id)
            ->where('type', Address::TYPE_SHIPPING)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'reference' => 'C-' . str_pad((string) $order->id, 9, '0', STR_PAD_LEFT),
                'status' => $order->status,
                'total_amount' => $order->total_amount,
                'created_at' => $order->created_at?->toIso8601String(),
                'customer' => [
                    'id' => $order->user?->id,
                    'name' => $order->user?->name,
                    'phone' => $order->user?->phone_number ?? $order->user?->phone ?? null,
                ],
                'shipping_address' => $shippingAddress ? [
                    'id' => $shippingAddress->id,
                    'full_address' => $shippingAddress->full_address,
                    'phone' => $shippingAddress->phone,
                ] : null,
                'items' => $order->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ]),
            ],
        ]);
    }

    /**
     * Valider la livraison avec le code fourni par le client.
     * Si le code correspond, la commande passe au statut "delivered".
     */
    public function validateDelivery(Request $request, int $id): JsonResponse
    {
        if ($err = $this->ensureTransporter()) {
            return $err;
        }

        $validator = Validator::make($request->all(), [
            'delivery_code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Le code de livraison doit contenir 6 caractères.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::where('transporter_id', Auth::id())
            ->where('status', 'validated')
            ->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée ou non assignée à vous.',
            ], 404);
        }

        if (!$order->delivery_code) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun code de livraison associé à cette commande.',
            ], 400);
        }

        $code = $request->input('delivery_code');
        if (!hash_equals($order->delivery_code, $code)) {
            return response()->json([
                'success' => false,
                'message' => 'Code de livraison incorrect. Demandez le code au client.',
            ], 422);
        }

        $order->status = 'delivered';
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Livraison validée avec succès.',
            'data' => [
                'id' => $order->id,
                'reference' => 'C-' . str_pad((string) $order->id, 9, '0', STR_PAD_LEFT),
                'status' => $order->status,
            ],
        ]);
    }

    /**
     * Profil du transporteur connecté.
     */
    public function profile(): JsonResponse
    {
        if ($err = $this->ensureTransporter()) {
            return $err;
        }

        $user = Auth::user();
        $user->load('transporterPriceSetting');

        return response()->json([
            'success' => true,
            'data' => $this->formatTransporterProfile($user),
        ]);
    }

    /**
     * Mise à jour du profil du transporteur.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        if ($err = $this->ensureTransporter()) {
            return $err;
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'siret' => 'nullable|string|size:14',
            'vehicle_type' => 'nullable|string|max:100',
            'license_number' => 'nullable|string|max:100',
            'insurance_number' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'phone_number' => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $data = $validator->validated();
        if (isset($data['phone_number']) && !isset($data['phone'])) {
            $data['phone'] = $data['phone_number'];
        }
        unset($data['phone_number']);
        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès.',
            'data' => $this->formatTransporterProfile($user->fresh('transporterPriceSetting')),
        ]);
    }

    /**
     * Formater les données du profil transporteur pour l'API.
     */
    protected function formatTransporterProfile(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone_number ?? $user->phone ?? null,
            'company_name' => $user->company_name,
            'siret' => $user->siret,
            'vehicle_type' => $user->vehicle_type,
            'license_number' => $user->license_number,
            'insurance_number' => $user->insurance_number,
            'address' => $user->address,
            'is_active' => $user->is_active ?? true,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];
    }

    // ========== APIs Carrier (format doc final – pas d'inscription) ==========

    /** Référence commande au format CMD-YEAR-ID. */
    protected function carrierOrderReference(Order $order): string
    {
        $year = $order->created_at ? Carbon::parse($order->created_at)->year : date('Y');
        return 'CMD-' . $year . '-' . str_pad((string) $order->id, 3, '0', STR_PAD_LEFT);
    }

    /** Nom du shop (boutique ou marchand) pour une commande. */
    protected function carrierShopNameForOrder(Order $order): ?string
    {
        $first = $order->items->first();
        if (!$first?->product?->user) {
            return null;
        }
        $merchant = $first->product->user->merchant;
        if (!$merchant) {
            return $first->product->user->name;
        }
        $boutique = $merchant->boutiques()->first();
        return $boutique?->name ?? $first->product->user->name;
    }

    /** Statut exposé côté carrier : validated → assigned. */
    protected function carrierStatus(string $status): string
    {
        return $status === 'validated' ? 'assigned' : $status;
    }

    /** GET carrier/profile – Profil transporteur (format doc). */
    public function carrierProfile(): JsonResponse
    {
        if ($err = $this->ensureTransporter()) {
            return $err;
        }
        $user = Auth::user();
        $totalDeliveries = Order::where('transporter_id', $user->id)->where('status', 'delivered')->count();
        $rating = $this->getTransporterRating($user);
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_number ?? $user->phone ?? null,
                'company_name' => $user->company_name,
                'vehicle_type' => $user->vehicle_type,
                'latitude' => isset($user->latitude) ? (float) $user->latitude : null,
                'longitude' => isset($user->longitude) ? (float) $user->longitude : null,
                'is_active' => (bool) ($user->is_active ?? true),
                'rating' => $rating !== null ? (float) $rating : 0,
                'total_deliveries' => $totalDeliveries,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
        ]);
    }

    /** PUT carrier/profile – Mise à jour profil (corps partiel). */
    public function carrierUpdateProfile(Request $request): JsonResponse
    {
        if ($err = $this->ensureTransporter()) {
            return $err;
        }
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:30',
            'company_name' => 'nullable|string|max:255',
            'vehicle_type' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'address_line_1' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors(),
            ], 422);
        }
        $data = $validator->validated();
        if (array_key_exists('address_line_1', $data)) {
            $data['address'] = $data['address_line_1'];
        }
        unset($data['address_line_1'], $data['city'], $data['postal_code']);
        $allowed = ['name', 'phone', 'company_name', 'vehicle_type', 'latitude', 'longitude', 'address'];
        $payload = array_intersect_key($data, array_flip($allowed));
        $user = Auth::user();
        $user->update($payload);
        return $this->carrierProfile();
    }

    /** GET carrier/orders – Liste (query: status, page, per_page). */
    public function carrierOrders(Request $request): JsonResponse
    {
        if ($err = $this->ensureTransporter()) {
            return $err;
        }
        $status = $request->query('status');
        $query = Order::query()
            ->where('transporter_id', Auth::id())
            ->with(['user', 'items.product.user.merchant.boutiques']);

        if ($status !== null && $status !== '') {
            if ($status === 'assigned') {
                $query->whereIn('status', ['validated', 'picked_up', 'in_transit']);
            } elseif ($status === 'pending') {
                $query->where('status', 'validated');
            } else {
                $query->where('status', $status);
            }
        }

        $orders = $query->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 15));

        $items = $orders->getCollection()->map(fn (Order $order) => $this->carrierFormatOrderItem($order));
        $orders->setCollection($items);

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    protected function carrierFormatOrderItem(Order $order): array
    {
        $addr = Address::where('user_id', $order->user_id)
            ->where('type', Address::TYPE_SHIPPING)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->first();

        $deliveryAddress = null;
        if ($addr) {
            $deliveryAddress = [
                'address_line_1' => $addr->address_line_1,
                'city' => $addr->city,
                'postal_code' => $addr->postal_code,
            ];
        }

        return [
            'id' => $order->id,
            'reference' => $this->carrierOrderReference($order),
            'status' => $this->carrierStatus($order->status),
            'total_amount' => (float) $order->total_amount,
            'delivery_address' => $deliveryAddress,
            'customer_name' => $order->user?->name,
            'customer_phone' => $order->user?->phone_number ?? $order->user?->phone ?? null,
            'shop_name' => $this->carrierShopNameForOrder($order),
            'estimated_distance_km' => null,
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }

    /** GET carrier/orders/:id – Détail commande. */
    public function carrierOrderDetail(int $id): JsonResponse
    {
        if ($err = $this->ensureTransporter()) {
            return $err;
        }
        $order = Order::where('transporter_id', Auth::id())
            ->with(['user', 'items.product.user.merchant.boutiques'])
            ->find($id);
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée.',
            ], 404);
        }
        $item = $this->carrierFormatOrderItem($order);
        $addr = Address::where('user_id', $order->user_id)
            ->where('type', Address::TYPE_SHIPPING)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->first();
        $item['delivery_address'] = $addr ? [
            'address_line_1' => $addr->address_line_1,
            'address_line_2' => $addr->address_line_2,
            'city' => $addr->city,
            'postal_code' => $addr->postal_code,
            'country' => $addr->country,
            'phone' => $addr->phone,
        ] : $item['delivery_address'];
        $item['items'] = $order->items->map(fn ($i) => [
            'product_id' => $i->product_id,
            'product_name' => $i->product?->name,
            'quantity' => $i->quantity,
            'price' => (float) $i->price,
        ]);
        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }

    /** POST carrier/orders/:id/accept – Accepter une course (optionnel). */
    public function carrierAcceptOrder(int $id): JsonResponse
    {
        if ($err = $this->ensureTransporter()) {
            return $err;
        }
        $order = Order::where('status', 'validated')->find($id);
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée ou non disponible.',
            ], 404);
        }
        if ($order->transporter_id !== null && $order->transporter_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande est déjà assignée.',
            ], 422);
        }
        $order->transporter_id = Auth::id();
        $order->save();
        return response()->json([
            'success' => true,
            'message' => 'Course acceptée.',
            'data' => $this->carrierFormatOrderItem($order->load(['user', 'items.product.user.merchant.boutiques'])),
        ]);
    }

    /** POST carrier/orders/:id/status – Corps: { "status": "picked_up" | "in_transit" | "delivered" | "cancelled" }. */
    public function carrierOrderStatus(Request $request, int $id): JsonResponse
    {
        if ($err = $this->ensureTransporter()) {
            return $err;
        }
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:picked_up,in_transit,delivered,cancelled',
            'delivery_code' => 'nullable|string|size:6',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors(),
            ], 422);
        }
        $order = Order::where('transporter_id', Auth::id())->find($id);
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée.',
            ], 404);
        }
        $newStatus = $request->input('status');
        if ($newStatus === 'delivered' && $order->delivery_code) {
            $code = $request->input('delivery_code', '');
            if (!hash_equals($order->delivery_code, (string) $code)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code de livraison incorrect ou manquant.',
                ], 422);
            }
        }
        $order->status = $newStatus;
        $order->save();
        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour.',
            'data' => $this->carrierFormatOrderItem($order->load(['user', 'items.product.user.merchant.boutiques'])),
        ]);
    }

    /** GET carrier/dashboard – Statistiques tableau de bord. */
    public function carrierDashboard(): JsonResponse
    {
        if ($err = $this->ensureTransporter()) {
            return $err;
        }
        $userId = Auth::id();
        $now = Carbon::now();
        $startOfDay = $now->copy()->startOfDay();
        $startOfWeek = $now->copy()->startOfWeek();
        $startOfMonth = $now->copy()->startOfMonth();

        $base = Order::where('transporter_id', $userId);

        $ordersToday = (clone $base)->where('status', 'delivered')->where('updated_at', '>=', $startOfDay)->count();
        $ordersWeek = (clone $base)->where('status', 'delivered')->where('updated_at', '>=', $startOfWeek)->count();
        $ordersMonth = (clone $base)->where('status', 'delivered')->where('updated_at', '>=', $startOfMonth)->count();
        $pendingOrders = (clone $base)->whereIn('status', ['validated', 'picked_up', 'in_transit'])->count();

        $earningsToday = (clone $base)->where('status', 'delivered')->where('updated_at', '>=', $startOfDay)->sum('delivery_fee');
        $earningsWeek = (clone $base)->where('status', 'delivered')->where('updated_at', '>=', $startOfWeek)->sum('delivery_fee');
        $earningsMonth = (clone $base)->where('status', 'delivered')->where('updated_at', '>=', $startOfMonth)->sum('delivery_fee');

        $totalDeliveries = (clone $base)->where('status', 'delivered')->count();
        $rating = $this->getTransporterRating(Auth::user()) ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'orders_today' => $ordersToday,
                'orders_week' => $ordersWeek,
                'orders_month' => $ordersMonth,
                'pending_orders' => $pendingOrders,
                'earnings_today' => (float) $earningsToday,
                'earnings_week' => (float) $earningsWeek,
                'earnings_month' => (float) $earningsMonth,
                'rating' => (float) $rating,
                'total_deliveries' => $totalDeliveries,
            ],
        ]);
    }
}
