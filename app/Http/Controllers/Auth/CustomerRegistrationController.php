<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class CustomerRegistrationController extends Controller
{
    /**
     * Handle an incoming customer registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Personal information
            'lastname' => ['required', 'string', 'max:255'],
            'firstname' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'before:today'],
            'phone' => ['required', 'string', 'max:20'],
            'postal_address' => ['required', 'string', 'max:255'],
            'geographic_address' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->firstname . ' ' . $request->lastname,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => User::TYPE_CUSTOMER,
            'phone' => $request->phone,
            'postal_address' => $request->postal_address,
            'geographic_address' => $request->geographic_address,
            'birth_date' => $request->birth_date,
            'is_approved' => true, // Customers are automatically approved
        ]);

        // Trigger email verification
        event(new Registered($user));

        // Create token for the user
        $token = $user->createToken($user->name.'-CustomerAuthToken')->plainTextToken;

        return response()->json([
            'message' => 'Account created successfully. Please verify your email address.',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Handle social login/registration
     */
    public function socialRegister(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider' => ['required', 'string', 'in:google,facebook'],
            'provider_id' => ['required', 'string'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user exists with this email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Create new user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make(Str::random(16)), // Random password for social accounts
                'type' => User::TYPE_CUSTOMER,
                'is_approved' => true,
                'email_verified_at' => now(), // Social login users are pre-verified
            ]);
        }

        // Create token for the user
        $token = $user->createToken($user->name.'-SocialAuthToken')->plainTextToken;

        return response()->json([
            'message' => 'Social authentication successful',
            'user' => $user,
            'token' => $token,
        ], 200);
    }
}
