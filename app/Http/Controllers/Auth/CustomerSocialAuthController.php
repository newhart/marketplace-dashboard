<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CustomerSocialAuthController extends Controller
{
    /**
     * Handle social authentication for customers
     * POST /api/customer/social-auth
     */
    public function socialAuth(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider' => ['required', 'string', 'in:google,facebook'],
            'social_id' => ['required', 'string'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'picture' => ['nullable', 'string', 'url'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Vérifier si un utilisateur existe déjà avec cet email
            $existingUser = User::where('email', $request->email)->first();

            if ($existingUser) {
                // Utilisateur existant - mise à jour des informations sociales si nécessaire
                $updateData = [];
                
                // Mettre à jour les informations sociales si elles n'existent pas
                if (!$existingUser->provider_id || !$existingUser->provider) {
                    $updateData['provider_id'] = $request->social_id;
                    $updateData['provider'] = $request->provider;
                }
                
                // Mettre à jour la photo de profil si fournie
                if ($request->picture && !$existingUser->profile_picture) {
                    $updateData['profile_picture'] = $request->picture;
                }

                if (!empty($updateData)) {
                    $existingUser->update($updateData);
                }

                $user = $existingUser;
                $isNewUser = false;
            } else {
                // Nouvel utilisateur - création du compte
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make(Str::random(32)), // Mot de passe aléatoire sécurisé
                    'type' => User::TYPE_CUSTOMER,
                    'provider' => $request->provider,
                    'provider_id' => $request->social_id,
                    'profile_picture' => $request->picture,
                    'is_approved' => true,
                    'email_verified_at' => now(), // Les comptes sociaux sont pré-vérifiés
                ]);
                
                $isNewUser = true;
            }

            // Vérifier que l'utilisateur est bien un client
            if (!$user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce compte n\'est pas un compte client'
                ], 403);
            }

            // Créer le token d'authentification
            $tokenName = $user->name . '-' . ucfirst($request->provider) . 'AuthToken';
            $token = $user->createToken($tokenName)->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => $isNewUser ? 'Compte créé avec succès via ' . $request->provider : 'Connexion réussie via ' . $request->provider,
                'is_new_user' => $isNewUser,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'type' => $user->type,
                    'provider' => $user->provider,
                    'profile_picture' => $user->profile_picture,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                ],
                'token' => $token,
            ], $isNewUser ? 201 : 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'authentification sociale',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Link social account to existing customer account
     * POST /api/customer/link-social-account
     */
    public function linkSocialAccount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider' => ['required', 'string', 'in:google,facebook'],
            'social_id' => ['required', 'string'],
            'email' => ['required', 'string', 'lowercase', 'email'],
            'name' => ['required', 'string', 'max:255'],
            'picture' => ['nullable', 'string', 'url'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();

            if (!$user || !$user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            // Vérifier que l'email correspond
            if ($user->email !== $request->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'email du compte social ne correspond pas à votre compte'
                ], 422);
            }

            // Vérifier si ce compte social n'est pas déjà lié à un autre utilisateur
            $existingSocialUser = User::where('provider', $request->provider)
                ->where('provider_id', $request->social_id)
                ->where('id', '!=', $user->id)
                ->first();

            if ($existingSocialUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce compte ' . $request->provider . ' est déjà lié à un autre utilisateur'
                ], 422);
            }

            // Lier le compte social
            $user->update([
                'provider' => $request->provider,
                'provider_id' => $request->social_id,
                'profile_picture' => $request->picture ?? $user->profile_picture,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Compte ' . $request->provider . ' lié avec succès',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'provider' => $user->provider,
                    'profile_picture' => $user->profile_picture,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la liaison du compte social',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unlink social account from customer account
     * DELETE /api/customer/unlink-social-account
     */
    public function unlinkSocialAccount(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user || !$user->isCustomer()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            // Vérifier que l'utilisateur a un mot de passe défini avant de délier le compte social
            if (!$user->password) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez définir un mot de passe avant de délier votre compte social'
                ], 422);
            }

            // Délier le compte social
            $user->update([
                'provider' => null,
                'provider_id' => null,
                'profile_picture' => null, // Optionnel : garder ou supprimer la photo
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Compte social délié avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion du compte social',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
