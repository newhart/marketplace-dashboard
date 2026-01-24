<?php

namespace App\Filament\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Pages\Auth\Login;
use Illuminate\Validation\ValidationException;

class CustomLogin extends Login
{
    public function getTitle(): string
    {
        return 'Connexion à OnboardFlow';
    }

    protected ?string $heading = 'Connexion à OnboardFlow';

    protected static string $view = 'filament.pages.custom-login';

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        if (($user instanceof FilamentUser) && ! $user->canAccessPanel(Filament::getCurrentPanel())) {
            Filament::auth()->logout();

            throw ValidationException::withMessages([
                'data.email' => 'Vous n’avez pas accès à cet espace. Utilisez l’URL de connexion correspondant à votre profil (admin, marchand ou transporteur).',
            ]);
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }
}
