<x-app-layout>
    <x-slot name="header">Perfil</x-slot>

    <div class="panel form-container" style="margin-bottom: 16px;">
        @include('profile.partials.update-profile-information-form')
    </div>

    <div class="panel form-container" style="margin-bottom: 16px;">
        @include('profile.partials.update-password-form')
    </div>

    <div class="panel form-container" style="margin-bottom: 16px;">
        @include('profile.partials.delete-user-form')
    </div>
</x-app-layout>
