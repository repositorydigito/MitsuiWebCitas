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
        .create-password-container {
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

        /* Contenedor del formulario */
        .form-container {
            width: 100%;
            max-width: 450px;
            padding: 2rem;
        }

        /* Logo */
        .brand-logo {
            width: 250px;
            height: auto;
            margin: 0 auto 2rem auto;
            display: block;
        }

        /* Título */
        .form-title {
            font-size: 2rem;
            font-weight: 700;
            color: #0075BF;
            margin-bottom: 0.5rem;
        }

        /* Subtítulo */
        .form-subtitle {
            color: #6B7280;
            margin-bottom: 2rem;
            font-size: 0.875rem;
        }

        /* Información del usuario */
        .user-info {
            margin-bottom: 1.5rem;
            padding: 1rem;
            border-radius: 0.5rem;
            background-color: #EFF6FF;
            border: 1px solid #DBEAFE;
        }

        .user-info-content {
            display: flex;
            align-items: center;
        }

        .user-info-icon {
            flex-shrink: 0;
            margin-right: 0.75rem;
        }

        .user-info-icon svg {
            height: 1.5rem;
            width: 1.5rem;
            color: #0075BF;
        }

        .user-name {
            font-size: 0.875rem;
            font-weight: 500;
            color: #0075BF;
            margin: 0;
        }

        .user-document {
            font-size: 0.75rem;
            color: #6B7280;
            margin: 0;
        }

        /* Botones */
        .btn-primary {
            width: 100%;
            display: flex;
            justify-content: center;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            font-size: 0.875rem;
            font-weight: 500;
            color: white;
            background-color: #0075BF;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
        }

        .btn-primary:hover {
            background-color: #005a99;
        }

        .btn-primary:focus {
            outline: none;
            box-shadow: 0 0 0 2px #0075BF;
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-secondary {
            width: 100%;
            display: flex;
            justify-content: center;
            padding: 0.5rem 1rem;
            border: 1px solid #0075BF;
            border-radius: 0.375rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            font-size: 0.875rem;
            font-weight: 500;
            color: #0075BF;
            background-color: white;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
        }

        .btn-secondary:hover {
            background-color: #F9FAFB;
        }

        .btn-secondary:focus {
            outline: none;
            box-shadow: 0 0 0 2px #0075BF;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .create-password-container {
                flex-direction: column;
            }

            .left-panel {
                flex: 0 0 200px;
            }

            .right-panel {
                flex: 1;
                padding: 1rem;
            }

            .form-container {
                padding: 1rem;
            }

            .brand-logo {
                width: 150px;
                margin-bottom: 1rem;
            }

            .form-title {
                font-size: 1.5rem;
            }
        }
    </style>

    <div class="create-password-container">
        <!-- Panel izquierdo - Branding -->
        <div class="left-panel">
        </div>

        <!-- Panel derecho - Formulario -->
        <div class="right-panel">
            <div class="form-container">
                <!-- Logo -->
                <img src="{{ asset('images/logomitsui2.svg') }}" alt="Mitsui Logo" class="brand-logo">

                <!-- Título -->
                <h1 class="form-title text-center">Configurar Contraseña</h1>
                <p class="form-subtitle text-center">Establezca su contraseña para acceder al sistema</p>

                <!-- Información del usuario -->
                @if($user)
                    <div class="user-info">
                        <div class="user-info-content">
                            <div class="user-info-icon">
                                <svg viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <p class="user-name">{{ $user->name }}</p>
                                <p class="user-document">{{ $user->document_type }}: {{ $user->document_number }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Formulario -->
                <form wire:submit="create">
                    {{ $this->form }}

                    <div class="mt-6">
                        <button type="submit"
                                class="btn-primary"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove>Establecer Contraseña</span>
                            <span wire:loading>Procesando...</span>
                        </button>
                    </div>
                </form>

                <!-- Botón Volver -->
                <div class="mt-4">
                    <a href="{{ route('filament.admin.auth.login') }}" class="btn-secondary">
                        Volver
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
