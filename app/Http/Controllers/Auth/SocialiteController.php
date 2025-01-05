<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\SocialiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialiteController extends Controller
{
     public function  redirectToProvider(string $provider , SocialiteService $socialiteService)  : JsonResponse
     {
        return response()->json($socialiteService->redirectToProvider($provider)); 
     }

     public function handleProviderCallback(string $provider , SocialiteService $socialiteService) 
     {
        return response()->json($socialiteService->handleProviderCallback($provider)); 
     }
}
