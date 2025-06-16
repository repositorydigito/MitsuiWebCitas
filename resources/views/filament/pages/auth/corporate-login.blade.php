<div>
    <style>
        /* Forzar light mode y prevenir dark mode */
        html, body {
            color-scheme: light !important;
            margin: 0;
            padding: 0;
            height: 100%;
        }
        
        .dark {
            display: none !important;
        }
        
        /* Contenedor principal */
        .login-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            flex-direction: row;
        }
        
        /* Panel izquierdo - Branding */
        .left-panel {
            flex: 1;
            background-image: url('{{ asset('images/cover.jpg') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Panel derecho - Formulario */
        .right-panel {
            flex: 1;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .form-container {
            width: 100%;
            max-width: 400px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 3rem;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: #374151;
        }

        .form-title {
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .form-subtitle {
            color: #6b7280;
            margin-bottom: 2rem;
        }
        
        .right-panel-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(0, 117, 191, 0.9), rgba(7, 53, 104, 0.95));
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .brand-logo {
            width: 300px;
            height: auto;
            padding-bottom: 3rem;
            margin: 0 auto;
        }
        
        .brand-title {
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .brand-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.25rem;
            text-align: center;
            max-width: 400px;
        }
        
        /* Estilos del formulario */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            font-size: 1rem;
            transition: all 0.2s;
            background-color: white !important;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #0075BF;
            box-shadow: 0 0 0 3px rgba(0, 117, 191, 0.1);
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 0.25rem;
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .checkbox-input {
            width: 1rem;
            height: 1rem;
            border-radius: 0.25rem;
            border: 1px solid #d1d5db;
        }
        
        .checkbox-label {
            font-size: 0.875rem;
            color: #4b5563;
        }
        
        .submit-button {
            width: 100%;
            padding: 0.75rem 1.5rem;
            background-color: #0075BF;
            color: white;
            border: none;
            border-radius: 0.375rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .submit-button:hover {
            background-color: #073568;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .left-panel {
                display: none;
            }

            .right-panel {
                background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
            }
        }
        
        /* Sobrescribir estilos de Filament */
        .fi-simple-layout {
            background: transparent !important;
        }
        
        .fi-simple-main {
            background: transparent !important;
            padding: 0 !important;
        }
        
        /* Personalizar inputs de Filament */
        .fi-input {
            background-color: white !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 0.375rem !important;
        }
        
        .fi-input:focus {
            border-color: #0075BF !important;
            box-shadow: 0 0 0 3px rgba(0, 117, 191, 0.1) !important;
        }
        
        .fi-btn {
            background-color: #0075BF !important;
            border-radius: 0.375rem !important;
            font-weight: 500 !important;
            transition: all 0.2s !important;
        }
        
        .fi-btn:hover {
            background-color: #073568 !important;
        }
    </style>

    <script>
        // Forzar light mode
        localStorage.setItem('theme', 'light');
        localStorage.removeItem('filament-theme');
        document.documentElement.classList.remove('dark');
    </script>

    <div class="login-container">
        <!-- Panel izquierdo - Branding -->
        <div class="left-panel">
        </div>

        <!-- Panel derecho - Formulario -->
        <div class="right-panel">
            <div class="form-container">
                <!-- Título -->
                <img src="{{ asset('images/logomitsui2.svg') }}" alt="Mitsui Logo" class="brand-logo">
                <h1 class="form-title text-center">Iniciar Sesión</h1>
                <p class="form-subtitle text-center">Ingresa tus credenciales para acceder al sistema</p>

                <!-- Formulario -->
                <x-filament-panels::form wire:submit="authenticate">
                    {{ $this->form }}

                    <div class="mt-6">
                        <x-filament-panels::form.actions
                            :actions="$this->getCachedFormActions()"
                            :full-width="$this->hasFullWidthFormActions()"
                        />
                    </div>
                </x-filament-panels::form>

                <!-- Botón Crear Cuenta -->
                <div class="mt-4">
                    <a href="{{ route('auth.create-password') }}"
                       class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2"
                       style="color: #0075BF; border-color: #0075BF;">
                        Crear Cuenta
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mejorar la experiencia del formulario
        document.addEventListener('DOMContentLoaded', function() {
            // Forzar light mode
            const forceLight = () => {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                localStorage.removeItem('filament-theme');
            };
            
            forceLight();
            
            // Observer para mantener light mode
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        if (document.documentElement.classList.contains('dark')) {
                            forceLight();
                        }
                    }
                });
            });

            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class']
            });
        });
    </script>
</div> 