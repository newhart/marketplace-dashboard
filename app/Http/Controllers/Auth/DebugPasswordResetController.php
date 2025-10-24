<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetCode;
use App\Services\PasswordResetCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebugPasswordResetController extends Controller
{
    protected $passwordResetCodeService;

    public function __construct(PasswordResetCodeService $passwordResetCodeService)
    {
        $this->passwordResetCodeService = $passwordResetCodeService;
    }

    /**
     * Debug endpoint to check reset codes
     */
    public function debugCodes(Request $request): JsonResponse
    {
        $email = $request->get('email');
        
        if (!$email) {
            return response()->json([
                'error' => 'Email requis'
            ], 400);
        }

        $codes = PasswordResetCode::where('email', $email)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($code) {
                return [
                    'id' => $code->id,
                    'code' => $code->code,
                    'expires_at' => $code->expires_at->toISOString(),
                    'is_used' => $code->is_used,
                    'attempts' => $code->attempts,
                    'created_at' => $code->created_at->toISOString(),
                    'is_expired' => $code->isExpired(),
                    'is_valid' => $code->isValid(),
                ];
            });

        return response()->json([
            'email' => $email,
            'codes' => $codes,
            'current_time' => now()->toISOString(),
            'current_timestamp' => now()->timestamp
        ]);
    }

    /**
     * Debug endpoint to test token verification
     */
    public function debugToken(Request $request): JsonResponse
    {
        $token = $request->get('token');
        
        if (!$token) {
            return response()->json([
                'error' => 'Token requis'
            ], 400);
        }

        try {
            // Utiliser reflection pour accéder à la méthode privée
            $reflection = new \ReflectionClass($this->passwordResetCodeService);
            $method = $reflection->getMethod('verifyResetToken');
            $method->setAccessible(true);
            
            $result = $method->invoke($this->passwordResetCodeService, $token);
            
            return response()->json([
                'token' => $token,
                'is_valid' => $result !== null,
                'decoded_data' => $result,
                'current_time' => now()->toISOString(),
                'current_timestamp' => now()->timestamp
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'token' => $token,
                'error' => $e->getMessage(),
                'current_time' => now()->toISOString(),
                'current_timestamp' => now()->timestamp
            ]);
        }
    }

    /**
     * Generate a fresh code for testing
     */
    public function generateTestCode(Request $request): JsonResponse
    {
        $email = $request->get('email', 'testuser@test.com');
        
        $result = $this->passwordResetCodeService->generateAndSendCode($email, $request->ip());
        
        // Récupérer le code généré pour le debug
        $latestCode = PasswordResetCode::where('email', $email)
            ->latest()
            ->first();
            
        return response()->json([
            'result' => $result,
            'latest_code' => $latestCode ? [
                'id' => $latestCode->id,
                'code' => $latestCode->code,
                'expires_at' => $latestCode->expires_at->toISOString(),
                'created_at' => $latestCode->created_at->toISOString()
            ] : null,
            'current_time' => now()->toISOString()
        ]);
    }
}
