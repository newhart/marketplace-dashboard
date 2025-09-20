<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;

class CustomerProfileController extends Controller
{
    /**
     * Get customer profile information
     *
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            // Charger les relations utiles
            $user->load(['addresses' => function($query) {
                $query->orderBy('is_default', 'desc')->orderBy('created_at', 'desc');
            }]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'birth_date' => $user->birth_date,
                    'postal_address' => $user->postal_address,
                    'geographic_address' => $user->geographic_address,
                    'email_verified_at' => $user->email_verified_at,
                    'type' => $user->type,
                    'is_approved' => $user->is_approved,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'addresses' => $user->addresses,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update customer profile information
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'firstname' => ['sometimes', 'string', 'max:255'],
            'lastname' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . Auth::id()],
            'phone' => ['sometimes', 'string', 'max:20'],
            'birth_date' => ['sometimes', 'date', 'before:today'],
            'postal_address' => ['sometimes', 'string', 'max:255'],
            'geographic_address' => ['sometimes', 'string', 'max:255'],
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
            
            if (!$user || !$user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $validatedData = $validator->validated();
            
            // Mettre à jour le nom complet si firstname ou lastname sont modifiés
            if (isset($validatedData['firstname']) || isset($validatedData['lastname'])) {
                $firstname = $validatedData['firstname'] ?? $user->firstname;
                $lastname = $validatedData['lastname'] ?? $user->lastname;
                $validatedData['name'] = trim($firstname . ' ' . $lastname);
            }

            $user->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'birth_date' => $user->birth_date,
                    'postal_address' => $user->postal_address,
                    'geographic_address' => $user->geographic_address,
                    'email_verified_at' => $user->email_verified_at,
                    'type' => $user->type,
                    'is_approved' => $user->is_approved,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change customer password
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
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
            
            if (!$user || !$user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            // Vérifier le mot de passe actuel
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le mot de passe actuel est incorrect'
                ], 422);
            }

            // Mettre à jour le mot de passe
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            // Optionnel : Révoquer tous les tokens existants pour forcer une reconnexion
            // $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe modifié avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du mot de passe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete customer account
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string'],
            'confirmation' => ['required', 'string', 'in:DELETE'],
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
            
            if (!$user || !$user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            // Vérifier le mot de passe
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mot de passe incorrect'
                ], 422);
            }

            // Révoquer tous les tokens
            $user->tokens()->delete();

            // Supprimer le compte (les adresses seront supprimées automatiquement grâce à onDelete('cascade'))
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Compte supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du compte',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
