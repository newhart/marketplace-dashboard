<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Services\FirebaseNotificationService;

class NotificationController extends Controller
{
    protected FirebaseNotificationService $firebaseService;

    public function __construct(FirebaseNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Send notification to a specific user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'userId' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'data' => 'nullable|array',
        ]);

        try {
            $user = User::findOrFail($validated['userId']);

            if (empty($user->fcm_token)) {
                return response()->json([
                    'message' => 'User does not have an FCM token',
                    'success' => false
                ], 400);
            }

            $success = $this->firebaseService->sendToUser(
                $user,
                $validated['title'],
                $validated['body'],
                $validated['data'] ?? []
            );

            if ($success) {
                return response()->json([
                    'message' => 'Notification sent successfully',
                    'success' => true,
                    'data' => [
                        'userId' => $user->id,
                        'title' => $validated['title'],
                        'body' => $validated['body'],
                    ]
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Failed to send notification',
                    'success' => false
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error sending notification',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Send notification to multiple users
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'userIds' => 'required|array|min:1',
            'userIds.*' => 'exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'data' => 'nullable|array',
        ]);

        try {
            $users = User::whereIn('id', $validated['userIds'])
                ->whereNotNull('fcm_token')
                ->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'message' => 'No users with valid FCM tokens found',
                    'success' => false,
                    'data' => ['sent' => 0]
                ], 400);
            }

            $successCount = $this->firebaseService->sendToUsers(
                $users->toArray(),
                $validated['title'],
                $validated['body'],
                $validated['data'] ?? []
            );

            return response()->json([
                'message' => 'Bulk notifications sent',
                'success' => true,
                'data' => [
                    'total' => count($validated['userIds']),
                    'sent' => $successCount,
                    'failed' => count($validated['userIds']) - $successCount,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error sending bulk notifications',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
