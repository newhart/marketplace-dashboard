<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): JsonResponse
    {
        $loginUserData = $request->validate([
            'email'=>'required|string|email',
            'password'=>'required|min:8'
        ]);
        
        $user = User::where('email', $loginUserData['email'])->first();
        
        // Vérifier si les identifiants sont valides
        if (!$user || !Hash::check($loginUserData['password'], $user->password)) {
            return response()->json([
                'message' => 'Identifiants invalides'
            ], 401);
        }
        
        // Vérifier si l'utilisateur est un marchand et s'il est approuvé
        if ($user->type === 'merchant') {
            $merchant = $user->merchant;
            
            // Vérifier si le profil marchand existe
            if (!$merchant) {
                return response()->json([
                    'message' => 'Profil marchand introuvable',
                    'status' => 'error'
                ], 403);
            }
            
            // Vérifier le statut d'approbation
            if ($merchant->approval_status !== 'approved') {
                return response()->json([
                    'message' => 'Votre compte marchand est en attente d\'approbation ou a été rejeté',
                    'status' => $merchant->approval_status,
                    'rejection_reason' => $merchant->rejection_reason
                ], 403);
            }
        }
        
        // Si tout est en ordre, créer le token et retourner la réponse
        $token = $user->createToken($user->name.'-AuthToken')->plainTextToken;
        
        return response()->json([
            'token' => $token,
            'user' => $user,
            'status' => 'success'
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
