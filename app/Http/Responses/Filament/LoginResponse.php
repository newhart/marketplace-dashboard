<?php

namespace App\Http\Responses\Filament;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $panel = Filament::getCurrentPanel();
        $panelPath = '/' . ltrim($panel->getPath(), '/');
        $panelUrl = Filament::getUrl() ?? url($panelPath);

        $intended = $request->session()->pull('url.intended');

        if (empty($intended)) {
            return redirect()->to($panelUrl);
        }

        $intendedPath = parse_url($intended, PHP_URL_PATH) ?? '';

        if (str_starts_with($intendedPath, $panelPath)) {
            return redirect()->to($intended);
        }

        return redirect()->to($panelUrl);
    }
}
