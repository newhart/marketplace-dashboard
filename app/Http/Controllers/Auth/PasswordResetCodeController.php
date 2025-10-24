<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;

class PasswordResetCodeController extends Controller
{
    protected $passwordResetCodeService;

    public function __construct(PasswordResetCodeService $passwordResetCodeService)
    {
        $this->passwordResetCodeService = $passwordResetCodeService;
    }

    /**
     * Send password reset code to email
     * POST /api/forgot-password
     */
    public function sendResetCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->passwordResetCodeService->generateAndSendCode(
                $request->email,
                $request->ip()
            );

            $statusCode = $result['success'] ? 200 : 429;
            
            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du code de réinitialisation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify password reset code
     * POST /api/verify-reset-code
     */
    public function verifyResetCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->passwordResetCodeService->verifyCode(
                $request->email,
                $request->code,
                $request->ip()
            );

            $statusCode = $result['success'] ? 200 : 422;
            
            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset password with verified code token
     * POST /api/reset-password-with-code
     */
    public function resetPasswordWithCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reset_token' => ['required', 'string'],
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
            $result = $this->passwordResetCodeService->resetPasswordWithToken(
                $request->reset_token,
                $request->password
            );

            $statusCode = $result['success'] ? 200 : 422;
            
            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation du mot de passe',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
