<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;

class FCMTokenController extends Controller
{
    /**
     * Save FCM token for push notifications
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveFCMToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'userId' => 'required|exists:users,id',
        ]);

        try {
            $user = User::findOrFail($validated['userId']);
            $user->update(['fcm_token' => $validated['token']]);

            return response()->json([
                'message' => 'FCM token saved successfully',
                'data' => [
                    'userId' => $user->id,
                    'token' => $user->fcm_token,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to save FCM token',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
