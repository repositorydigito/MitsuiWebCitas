    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-white flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="text-center">
            <img src="{{ asset('images/logoMitsui.svg') }}" alt="logoMitsui" class="w-28 h-auto mx-auto mb-6 filter drop-shadow-sm">
            <h2 class="text-3xl font-bold text-gray-800 tracking-tight">
                Configurar Contraseña
            </h2>
            <p class="mt-3 text-base text-gray-600">
                Establezca su contraseña para acceder al sistema
            </p>
        </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-6 shadow-lg border border-gray-100 rounded-xl sm:px-10 transition-all duration-300 animate-fadeIn">

            @if($user)
                <div class="mb-6 p-4 rounded-lg bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 shadow-sm">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-white p-2 rounded-full shadow-sm">
                            <svg class="h-6 w-6 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-semibold text-blue-700">
                                {{ $user->name }}
                            </p>
                            <p class="text-xs text-blue-600/80">
                                {{ $user->document_type }}: {{ $user->document_number }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <form wire:submit="create">
                <div class="space-y-5">
                    {{ $this->form }}
                </div>

                <div class="mt-8">
                    <button type="submit" 
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-md text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:-translate-y-0.5 disabled:opacity-50 disabled:hover:translate-y-0"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove>Establecer Contraseña</span>
                        <span wire:loading class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Procesando...
                        </span>
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <a href="{{ route('filament.admin.auth.login') }}" 
                   class="w-full flex justify-center py-2.5 px-4 border border-gray-200 rounded-lg shadow-sm text-sm font-medium text-blue-600 hover:bg-blue-50 hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-300 focus:ring-offset-2 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Volver al Login
                </a>
            </div>
        </div>
    </div>

    <style>
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(10px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</div>
