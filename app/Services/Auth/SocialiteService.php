<?php

namespace App\Services\Auth;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

class SocialiteService
{

    public function redirectToProvider(string $provider) : array 
    {
        $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();
       return [
        'auth_url' => $url
       ]; 
    }

    public function handleProviderCallback(string $provider)  : array 
    {
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();

            $user = User::firstOrCreate(
                ['email' => $socialUser->getEmail()],
                [
                    'name' => $socialUser->getName(),
                    'provider_id' => $socialUser->getId(),
                    'provider' => $provider,
                ]
            );
            $token = $user->createToken($user->name.'-AuthToken')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];

        } catch (\Exception $e) {
            return response()->json(['error' => 'Authentication failed'], 400);
        }
    }
}
