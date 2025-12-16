<?php

namespace App\Filament\Pages;

use Filament\Pages\Auth\Login;

class CustomLogin extends Login
{
    public function getTitle(): string
    {
        return 'Connexion à OnboardFlow';
    }

    protected ?string $heading = 'Connexion à OnboardFlow';

    protected static string $view = 'filament.pages.custom-login';
}
