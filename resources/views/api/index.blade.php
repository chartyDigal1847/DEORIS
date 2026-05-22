<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('API Tokens') }}</h2>
        <p>Create and manage Sanctum tokens for secure API access.</p>
    </x-slot>

    <div class="accountPage">
        <div class="accountHero">
            <div>
                <p class="accountHero__eyebrow">Developer Access</p>
                <h1>{{ __('API Tokens') }}</h1>
                <p>Issue limited-access tokens and revoke them when they are no longer needed.</p>
            </div>
        </div>

        <div class="accountStack">
            @livewire('api.api-token-manager')
        </div>
    </div>
</x-app-layout>
