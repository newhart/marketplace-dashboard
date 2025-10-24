<?php

namespace App\Services;

use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;

class PasswordResetCodeService
{
    const CODE_EXPIRY_MINUTES = 15; // Code expire après 15 minutes
    const MAX_ATTEMPTS_PER_CODE = 5; // Maximum 5 tentatives par code
    const MAX_CODES_PER_HOUR = 3; // Maximum 3 codes par email par heure
    const RATE_LIMIT_KEY_PREFIX = 'password_reset_code:';

    /**
     * Generate and send a password reset code
     */
    public function generateAndSendCode(string $email, string $ipAddress): array
    {
        // Vérifier si l'utilisateur existe
        $user = User::where('email', $email)->first();
        if (!$user) {
            // Pour la sécurité, on ne révèle pas si l'email existe ou non
            return [
                'success' => true,
                'message' => 'Si votre email existe dans notre système, vous recevrez un code de vérification.'
            ];
        }

        // Vérifier le rate limiting
        $rateLimitKey = self::RATE_LIMIT_KEY_PREFIX . $email;
        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_CODES_PER_HOUR)) {
            return [
                'success' => false,
                'message' => 'Trop de demandes de codes. Veuillez réessayer plus tard.',
                'retry_after' => RateLimiter::availableIn($rateLimitKey)
            ];
        }

        // Invalider tous les codes précédents pour cet email
        PasswordResetCode::forEmail($email)
            ->valid()
            ->update(['is_used' => true, 'used_at' => now()]);

        // Générer un nouveau code à 6 chiffres
        $code = $this->generateSecureCode();

        // Créer l'enregistrement du code
        $resetCode = PasswordResetCode::create([
            'email' => $email,
            'code' => $code,
            'expires_at' => now()->addMinutes(self::CODE_EXPIRY_MINUTES),
            'ip_address' => $ipAddress,
        ]);

        // Envoyer l'email
        try {
            $this->sendCodeByEmail($user, $code);
            
            // Incrémenter le rate limiter
            RateLimiter::hit($rateLimitKey, 3600); // 1 heure

            return [
                'success' => true,
                'message' => 'Un code de vérification a été envoyé à votre adresse email.',
                'expires_in_minutes' => self::CODE_EXPIRY_MINUTES
            ];
        } catch (\Exception $e) {
            // Supprimer le code si l'email n'a pas pu être envoyé
            $resetCode->delete();
            
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify a password reset code
     */
    public function verifyCode(string $email, string $code, string $ipAddress): array
    {
        // Rate limiting pour les tentatives de vérification
        $verifyRateLimitKey = 'verify_code:' . $email . ':' . $ipAddress;
        if (RateLimiter::tooManyAttempts($verifyRateLimitKey, 10)) { // 10 tentatives par heure
            return [
                'success' => false,
                'message' => 'Trop de tentatives de vérification. Veuillez réessayer plus tard.',
                'retry_after' => RateLimiter::availableIn($verifyRateLimitKey)
            ];
        }

        // Chercher le code valide
        $resetCode = PasswordResetCode::forEmail($email)
            ->where('code', $code)
            ->valid()
            ->first();

        if (!$resetCode) {
            // Incrémenter le rate limiter pour les tentatives échouées
            RateLimiter::hit($verifyRateLimitKey, 3600);
            
            return [
                'success' => false,
                'message' => 'Code invalide ou expiré.'
            ];
        }

        // Vérifier le nombre de tentatives pour ce code
        if ($resetCode->attempts >= self::MAX_ATTEMPTS_PER_CODE) {
            $resetCode->markAsUsed();
            return [
                'success' => false,
                'message' => 'Ce code a été utilisé trop de fois. Demandez un nouveau code.'
            ];
        }

        // Incrémenter les tentatives
        $resetCode->incrementAttempts();

        return [
            'success' => true,
            'message' => 'Code vérifié avec succès.',
            'reset_token' => $this->generateResetToken($resetCode)
        ];
    }

    /**
     * Reset password with verified code
     */
    public function resetPasswordWithToken(string $resetToken, string $newPassword): array
    {
        // Décoder et vérifier le token
        $tokenData = $this->verifyResetToken($resetToken);
        
        if (!$tokenData) {
            return [
                'success' => false,
                'message' => 'Token de réinitialisation invalide ou expiré.'
            ];
        }

        // Vérifier que le code existe encore et n'a pas été utilisé pour la réinitialisation
        $resetCode = PasswordResetCode::find($tokenData['code_id']);
        
        if (!$resetCode) {
            return [
                'success' => false,
                'message' => 'Code de réinitialisation introuvable.'
            ];
        }

        if ($resetCode->is_used) {
            return [
                'success' => false,
                'message' => 'Ce code a déjà été utilisé pour réinitialiser un mot de passe.'
            ];
        }

        // Trouver l'utilisateur
        $user = User::where('email', $resetCode->email)->first();
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Utilisateur introuvable.'
            ];
        }

        try {
            // Réinitialiser le mot de passe
            $user->update([
                'password' => bcrypt($newPassword)
            ]);

            // Marquer le code comme utilisé
            $resetCode->markAsUsed();

            // Révoquer tous les tokens existants pour forcer une reconnexion
            $user->tokens()->delete();

            return [
                'success' => true,
                'message' => 'Mot de passe réinitialisé avec succès.'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation du mot de passe.'
            ];
        }
    }

    /**
     * Generate a secure 6-digit code
     */
    private function generateSecureCode(): string
    {
        return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Send code by email
     */
    private function sendCodeByEmail(User $user, string $code): void
    {
        Mail::send('emails.password-reset-code', [
            'user' => $user,
            'code' => $code,
            'expires_in_minutes' => self::CODE_EXPIRY_MINUTES
        ], function ($message) use ($user) {
            $message->to($user->email, $user->name)
                   ->subject('Code de réinitialisation de mot de passe');
        });
    }

    /**
     * Generate a temporary reset token after code verification
     */
    private function generateResetToken(PasswordResetCode $resetCode): string
    {
        $payload = [
            'code_id' => $resetCode->id,
            'email' => $resetCode->email,
            'expires_at' => now()->addMinutes(30)->timestamp, // Token valide 30 minutes (plus long)
            'created_at' => now()->timestamp // Ajout du timestamp de création
        ];

        $jsonPayload = json_encode($payload);
        $signature = hash_hmac('sha256', $jsonPayload, config('app.key'));
        
        // Utiliser un séparateur différent pour éviter les conflits avec le JSON
        return base64_encode($jsonPayload . '|SIGNATURE|' . $signature);
    }

    /**
     * Verify and decode reset token
     */
    private function verifyResetToken(string $token): ?array
    {
        try {
            $decoded = base64_decode($token);
            
            // Nouveau format avec séparateur spécifique
            $parts = explode('|SIGNATURE|', $decoded);
            
            if (count($parts) !== 2) {
                return null;
            }

            $payload = json_decode($parts[0], true);
            $signature = $parts[1];

            // Vérifier que le payload est valide
            if (!$payload || !isset($payload['code_id'], $payload['email'], $payload['expires_at'])) {
                return null;
            }

            // Vérifier la signature
            $expectedSignature = hash_hmac('sha256', $parts[0], config('app.key'));
            if (!hash_equals($expectedSignature, $signature)) {
                return null;
            }

            // Vérifier l'expiration
            $currentTimestamp = now()->timestamp;
            $expiresAt = $payload['expires_at'];

            if ($expiresAt < $currentTimestamp) {
                return null;
            }

            return $payload;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Clean up expired codes (à appeler via un job/cron)
     */
    public function cleanupExpiredCodes(): int
    {
        return PasswordResetCode::cleanExpired();
    }
}
