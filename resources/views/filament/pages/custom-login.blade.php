<x-filament-panels::page.simple>
    <x-slot name="subheading">
        {{ $this->registerAction }}
    </x-slot>

    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()" />
    </x-filament-panels::form>

    <style>
        body {
            background-image: url('{{ asset('images/bg-login.jpeg') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        /* Overlay to ensure readability */
        .fi-simple-main {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 1rem;
        }
    </style>
</x-filament-panels::page.simple>