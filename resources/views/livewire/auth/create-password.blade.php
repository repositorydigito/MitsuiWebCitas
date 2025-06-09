<div class="min-h-screen bg-white flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="text-center">
            <img src="{{ asset('images/logoMitsui.svg') }}" alt="logoMitsui" class="w-24 h-auto mx-auto mb-6">
            <h2 class="text-3xl font-bold" style="color: #0075BF;">
                Configurar Contraseña
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Establezca su contraseña para acceder al sistema
            </p>
        </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow border rounded-lg sm:px-10">

            @if($user)
                <div class="mb-6 p-4 rounded-md bg-blue-50 border border-blue-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6" style="color: #0075BF;" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium" style="color: #0075BF;">
                                {{ $user->name }}
                            </p>
                            <p class="text-xs text-gray-600">
                                {{ $user->document_type }}: {{ $user->document_number }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <form wire:submit="create">
                {{ $this->form }}
                
                <div class="mt-6">
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50"
                            style="background-color: #0075BF; focus:ring-color: #0075BF;"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove>Establecer Contraseña</span>
                        <span wire:loading>Procesando...</span>
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <a href="{{ route('filament.admin.auth.login') }}" 
                   class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2"
                   style="color: #0075BF; border-color: #0075BF;">
                    Volver al Login
                </a>
            </div>
        </div>
    </div>
</div>
