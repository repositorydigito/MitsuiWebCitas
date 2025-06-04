@props([
    'heading' => null,
    'subheading' => null,
])

<div class="flex min-h-screen w-full dark:bg-gray-900">
    <!-- Contenedor izquierdo (imagen) -->
    <div class="hidden lg:block lg:w-1/2 h-screen">
        <img
            src="{{ asset('images/imgLogin.jpg') }}"
            alt="imgLogin"
            class="w-full h-full object-cover"
        >
    </div>
    <!-- Contenedor derecho (formulario) -->
    <div class="w-full lg:w-1/2 min-h-screen bg-gray-100 dark:bg-gray-900 flex items-center justify-center">

        <div class="max-w-md w-full p-6">

            <div class="flex items-center justify-center mb-8">
                <img src="{{ asset('images/logoMitsui.svg') }}" alt="logoMitsui"
                    style="margin-bottom:20px; width: 15rem; height: auto;"
                    class="dark:brightness-0 dark:invert">
            </div>

            <section class="grid gap-y-6">
                <x-filament-panels::header.simple
                    :heading="$heading ??= $this->getHeading()"
                    :logo="!request()->routeIs('filament.admin.auth.login') && $this->hasLogo()"
                    :subheading="$subheading ??= $this->getSubHeading()"
                />

                @if (filament()->hasRegistration())
                    <x-slot name="subheading">
                        {{ __('filament-panels::pages/auth/login.actions.register.before') }}
                        {{ $this->registerAction }}
                    </x-slot>
                @endif

                <x-filament-panels::form wire:submit="authenticate">
                    {{ $this->form }}

                    <x-filament-panels::form.actions
                        :actions="$this->getCachedFormActions()"
                        :full-width="$this->hasFullWidthFormActions()"
                    />
                </x-filament-panels::form>
            </section>
        </div>
    </div>

    <!-- Scripts y estilos dentro del elemento raíz -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
    /* Estilos personalizados para modo dark */
    .dark .fi-simple-main {
        background-color: rgb(17 24 39) !important; /* dark:bg-gray-900 */
    }

    .dark .fi-fo-field-wrp-label {
        color: rgb(243 244 246) !important; /* dark:text-gray-100 */
    }

    .dark .fi-input {
        background-color: rgb(55 65 81) !important; /* dark:bg-gray-700 */
        border-color: rgb(75 85 99) !important; /* dark:border-gray-600 */
        color: rgb(243 244 246) !important; /* dark:text-gray-100 */
    }

    .dark .fi-input:focus {
        border-color: rgb(59 130 246) !important; /* dark:border-blue-500 */
        ring-color: rgb(59 130 246) !important; /* dark:ring-blue-500 */
    }

    .dark .fi-input::placeholder {
        color: rgb(156 163 175) !important; /* dark:text-gray-400 */
    }

    .dark label {
        color: rgb(243 244 246) !important; /* dark:text-gray-100 */
    }

    .dark .fi-btn-color-primary {
        background-color: rgb(37 99 235) !important; /* dark:bg-blue-600 */
    }

    .dark .fi-btn-color-primary:hover {
        background-color: rgb(29 78 216) !important; /* dark:hover:bg-blue-700 */
    }

    .dark .fi-fo-field-wrp-error-message {
        color: rgb(248 113 113) !important; /* dark:text-red-400 */
    }

    .dark .fi-fo-field-wrp-hint {
        color: rgb(156 163 175) !important; /* dark:text-gray-400 */
    }

    .dark h1, .dark h2, .dark h3, .dark .fi-header-heading {
        color: rgb(243 244 246) !important; /* dark:text-gray-100 */
    }

    .dark .fi-header-subheading {
        color: rgb(156 163 175) !important; /* dark:text-gray-400 */
    }
    </style>
</div>