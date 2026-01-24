<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Livewire\Livewire;
use App\Filament\Pages\CustomLogin;
use App\Notifications\Channels\FirebaseChannel;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();
        Livewire::component('app.filament.pages.custom-login', CustomLogin::class);

        $this->app->bind(
            \Filament\Http\Responses\Auth\Contracts\LoginResponse::class,
            \App\Http\Responses\Filament\LoginResponse::class
        );

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url') . "/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        // Enregistrer le canal Firebase pour les notifications
        $this->app->make('Illuminate\Notifications\ChannelManager')->extend('firebase', function () {
            return new FirebaseChannel($this->app->make(\App\Services\FirebaseNotificationService::class));
        });
    }
}
