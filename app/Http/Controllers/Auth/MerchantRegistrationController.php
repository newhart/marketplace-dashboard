<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;

class MerchantRegistrationController extends Controller
{
    /**
     * Handle an incoming merchant registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // User information
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            
            // Merchant manager information
            'manager_lastname' => ['required', 'string', 'max:255'],
            'manager_firstname' => ['required', 'string', 'max:255'],
            'mobile_phone' => ['required', 'string', 'max:20'],
            'landline_phone' => ['nullable', 'string', 'max:20'],
            
            // Business information
            'business_address' => ['required', 'string', 'max:255'],
            'business_city' => ['required', 'string', 'max:100'],
            'business_postal_code' => ['required', 'string', 'max:20'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'business_description' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->manager_firstname . ' ' . $request->manager_lastname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => User::TYPE_MERCHANT,
            'is_approved' => false,
        ]);

        $merchant = Merchant::create([
            'user_id' => $user->id,
            'manager_lastname' => $request->manager_lastname,
            'manager_firstname' => $request->manager_firstname,
            'mobile_phone' => $request->mobile_phone,
            'landline_phone' => $request->landline_phone,
            'business_address' => $request->business_address,
            'business_city' => $request->business_city,
            'business_postal_code' => $request->business_postal_code,
            'business_type' => $request->business_type,
            'business_description' => $request->business_description,
            'approval_status' => 'pending',
        ]);

        // Trigger email verification
        event(new Registered($user));

        // Create token for the user
        $token = $user->createToken($user->name.'-MerchantAuthToken')->plainTextToken;

        return response()->json([
            'message' => 'Merchant account created successfully. Please verify your email address.',
            'user' => $user,
            'merchant' => $merchant,
            'token' => $token,
        ], 201);
    }

    /**
     * Get merchant account status
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user || !$user->isMerchant()) {
            return response()->json([
                'message' => 'Not a merchant account'
            ], 403);
        }
        
        $merchant = $user->merchant;
        
        if (!$merchant) {
            return response()->json([
                'message' => 'Merchant profile not found'
            ], 404);
        }
        
        return response()->json([
            'approval_status' => $merchant->approval_status,
            'email_verified' => !is_null($user->email_verified_at),
            'rejection_reason' => $merchant->rejection_reason,
        ]);
    }
}
