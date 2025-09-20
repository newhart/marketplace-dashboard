<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    /**
     * Get all addresses for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié'], 401);
            }

            $type = $request->query('type'); // shipping, billing
            
            $query = $user->addresses();
            
            if ($type && in_array($type, ['shipping', 'billing'])) {
                $query->where('type', $type);
            }
            
            $addresses = $query->orderBy('is_default', 'desc')
                              ->orderBy('created_at', 'desc')
                              ->get();

            return response()->json([
                'success' => true,
                'data' => $addresses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des adresses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new address
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', 'string', 'in:shipping,billing'],
            'title' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié'], 401);
            }

            $addressData = $validator->validated();
            $addressData['user_id'] = $user->id;
            $addressData['country'] = $addressData['country'] ?? 'Madagascar';

            $address = Address::create($addressData);

            // Si c'est marqué comme adresse par défaut, la définir comme telle
            if ($request->boolean('is_default')) {
                $address->setAsDefault();
                $address->refresh();
            }

            return response()->json([
                'success' => true,
                'message' => 'Adresse créée avec succès',
                'data' => $address
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'adresse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific address
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié'], 401);
            }

            $address = $user->addresses()->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $address
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Adresse non trouvée',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update an address
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => ['sometimes', 'string', 'in:shipping,billing'],
            'title' => ['nullable', 'string', 'max:255'],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'address_line_1' => ['sometimes', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['sometimes', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié'], 401);
            }

            $address = $user->addresses()->findOrFail($id);
            
            $address->update($validator->validated());

            // Si c'est marqué comme adresse par défaut, la définir comme telle
            if ($request->boolean('is_default')) {
                $address->setAsDefault();
                $address->refresh();
            }

            return response()->json([
                'success' => true,
                'message' => 'Adresse mise à jour avec succès',
                'data' => $address
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'adresse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an address
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié'], 401);
            }

            $address = $user->addresses()->findOrFail($id);
            
            $address->delete();

            return response()->json([
                'success' => true,
                'message' => 'Adresse supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'adresse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set an address as default
     *
     * @param int $id
     * @return JsonResponse
     */
    public function setDefault(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié'], 401);
            }

            $address = $user->addresses()->findOrFail($id);
            
            $address->setAsDefault();

            return response()->json([
                'success' => true,
                'message' => 'Adresse définie comme adresse par défaut',
                'data' => $address->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la définition de l\'adresse par défaut',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
