<div>
    <style>
        /* Forzar light mode y prevenir dark mode */
        html, body {
            color-scheme: light !important;
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Inter', sans-serif !important;
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
            background-image: url('{{ asset('images/portadaMitsui.png') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .brand-title {
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .brand-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.25rem;
            max-width: 400px;
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

        .brand-logo {
            width: 200px;
            height: auto;
            padding-bottom: 2rem;
            margin: 0 auto;
            display: block;
        }

        .form-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-subtitle {
            color: #6b7280;
            margin-bottom: 2rem;
            text-align: center;
            font-size: 0.5rem;
        }
        
        /* Estilos del formulario */
        .form-group {
            margin-bottom: 2rem;
        }
        
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.375rem;
            font-size: 0.9rem;
            transition: all 0.2s;
            background-color: white !important;
            letter-spacing: 0.5px;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #0075BF;
            box-shadow: 0 0 0 3px rgba(0, 117, 191, 0.1);
        }
        
        .submit-button {
            width: 100%;
            padding: 0.75rem 1.5rem;
            background-color: #0075BF;
            color: white;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .submit-button:hover {
            background-color: #073568;
        }
        
        .form-link {
            display: block;
            margin-top: 1.5rem;
            text-align: center;
            color: #0075BF;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .form-link:hover {
            text-decoration: underline;
        }
        
        .alert-message {
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .alert-success {
            color: #16a34a;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
        }
        
        .alert-error {
            color: #dc2626;
            background: #fef2f2;
            border: 1px solid #fecaca;
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
            
            .fi-btn {
                background-color: #0075BF !important;
                border-radius: 0.375rem !important;
                font-weight: 500 !important;
                transition: all 0.2s !important;
            }
            
            .fi-btn:hover {
                background-color: #073568 !important;
            }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .left-panel {
                display: none;
            }

            .form-container{
                max-width: 50rem;
            }

            .form-group {
                margin-bottom: 4rem;
            }
            
            .brand-logo {
                width: 350px;
                height: auto;
                padding-bottom: 1rem;
                margin: 0 auto;
            }

            .form-title {
                font-size: 3.5rem;
                font-weight: 700;
                color: #111827;
                margin-bottom: 5rem;
            }

            .form-label{
                font-size: 2.3rem;
                margin-bottom: 2rem;
            }

            .form-input{
                font-size: 2.3rem;
                padding: 2rem 1rem;
            }

            .submit-button {
                font-size: 2.5rem;
                padding: 2rem 1rem;
                margin: 2rem 0;
            }

            .form-link{
                font-size: 2.5rem;
            }

            select.form-input option {
                font-size: 16px;
            }
        }
        
    </style>

    <script>
        // Forzar light mode
        localStorage.setItem('theme', 'light');
        localStorage.removeItem('filament-theme');
        document.documentElement.classList.remove('dark');
        
        // Observer para mantener light mode
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    if (document.documentElement.classList.contains('dark')) {
                        localStorage.setItem('theme', 'light');
                        localStorage.removeItem('filament-theme');
                        document.documentElement.classList.remove('dark');
                    }
                }
            });
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });
    </script>

    <div class="login-container">
        <!-- Panel izquierdo - Branding -->
        <div class="left-panel">
        </div>

        <!-- Panel derecho - Formulario -->
        <div class="right-panel">
            <div class="form-container">
                <img src="{{ asset('images/logomitsui2.svg') }}" alt="Mitsui Logo" class="brand-logo">
                <h1 class="form-title">Recuperar contraseña</h1>
                
                @if(session('status'))
                    <div class="alert-message alert-success">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert-message alert-error">{{ $errors->first() }}</div>
                @endif
                
                <form method="POST" action="{{ route('password.send-reset-link') }}">
                    @csrf
                    <div class="form-group">
                        <label for="tipo_documento" class="form-label">Tipo de documento</label>
                        <select id="tipo_documento" name="tipo_documento" class="form-input" required>
                            <option value="">Selecciona una opción</option>
                            <option value="DNI" {{ old('tipo_documento') == 'DNI' ? 'selected' : '' }}>DNI</option>
                            <option value="CE" {{ old('tipo_documento') == 'CE' ? 'selected' : '' }}>Carné de Extranjería</option>
                            <option value="RUC" {{ old('tipo_documento') == 'RUC' ? 'selected' : '' }}>RUC</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="numero_documento" class="form-label">Número de documento</label>
                        <input id="numero_documento" name="numero_documento" type="text" class="form-input" required placeholder="Ingresa tu número de documento" value="{{ old('numero_documento') }}">
                    </div>
                    <button type="submit" class="submit-button">Recuperar</button>
                </form>
                
                <a href="{{ route('login') }}" class="form-link">Volver al login</a>
            </div>
        </div>
    </div>
</div>
