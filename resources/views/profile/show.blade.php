<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('Profile') }}</h2>
        <p>Manage your identity, password, sessions, and optional two-factor authentication.</p>
    </x-slot>

    <div class="accountPage">
        <div class="accountHero">
            <div>
                <p class="accountHero__eyebrow">Account Center</p>
                <h1>{{ __('Profile Settings') }}</h1>
                <p>Keep your DEORIS account secure and up to date.</p>
            </div>
        </div>

        <div class="accountStack">
            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                @livewire('profile.update-profile-information-form')

                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                <div class="mt-10 sm:mt-0">
                    @livewire('profile.update-password-form')
                </div>

                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <div class="mt-10 sm:mt-0">
                    @livewire('profile.two-factor-authentication-form')
                </div>

                <x-section-border />
            @endif

            <div class="mt-10 sm:mt-0">
                @livewire('profile.logout-other-browser-sessions-form')
            </div>

            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                <x-section-border />

                <div class="mt-10 sm:mt-0">
                    @livewire('profile.delete-user-form')
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
