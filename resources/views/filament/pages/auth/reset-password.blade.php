<div>
    <style>
        /* Forzar light mode y prevenir dark mode */
        html, body {
            color-scheme: light !important;
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Roboto', sans-serif !important;
        }
        
        .dark {
            display: none !important;
        }
        
        /* Contenedor principal */
        .login-container {
            min-height: 100vh;
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
            min-height: 100vh;
        }

        /* Panel derecho - Formulario */
        .right-panel {
            flex: 1;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            min-height: 100vh;
            overflow-y: auto;
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
            font-size: 1.1rem;
        }
        
        /* Estilos del formulario */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
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

        .password-requirements {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .password-requirements h4 {
            margin: 0 0 0.5rem 0;
            color: #374151;
            font-weight: 600;
        }

        .password-requirements ul {
            margin: 0;
            padding-left: 1.25rem;
            color: #6b7280;
        }

        .password-requirements li {
            margin-bottom: 0.25rem;
        }
        
        /* Responsive para laptops pequeñas */
        @media (max-width: 1366px) and (max-height: 768px) {
            .right-panel {
                padding: 1rem;
            }
            
            .form-container {
                max-width: 350px;
            }
            
            .brand-logo {
                width: 150px;
                padding-bottom: 1rem;
            }
            
            .form-title {
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            .form-subtitle {
                font-size: 0.9rem;
                margin-bottom: 1.5rem;
            }
            
            .password-requirements {
                padding: 0.75rem;
                margin-bottom: 1rem;
                font-size: 0.8rem;
            }
            
            .form-group {
                margin-bottom: 1rem;
            }
            
            .form-input {
                padding: 0.6rem 0.8rem;
                font-size: 0.85rem;
            }
            
            .submit-button {
                padding: 0.6rem 1rem;
                font-size: 0.85rem;
            }
        }

        /* Responsive para tablets y móviles */
        @media (max-width: 1024px) {
            .login-container {
                flex-direction: column;
            }
            
            .left-panel {
                display: none;
            }

            .right-panel {
                min-height: 100vh;
                padding: 2rem 1rem;
            }

            .form-container{
                max-width: 500px;
            }
            
            .brand-logo {
                width: 250px;
                padding-bottom: 1.5rem;
            }

            .form-title {
                font-size: 2rem;
                margin-bottom: 2rem;
            }
            
            .form-subtitle {
                font-size: 1.2rem;
                margin-bottom: 2rem;
            }

            .form-label{
                font-size: 1.1rem;
                margin-bottom: 0.8rem;
            }

            .form-input{
                font-size: 1.1rem;
                padding: 1rem;
            }

            .submit-button {
                font-size: 1.1rem;
                padding: 1rem;
                margin: 1.5rem 0;
            }

            .form-link{
                font-size: 1.1rem;
            }
            
            .password-requirements {
                font-size: 1rem;
                padding: 1.2rem;
            }
        }
        
        /* Responsive para móviles grandes */
        @media (max-width: 768px) {
            .right-panel {
                padding: 1.5rem 1rem;
            }
            
            .form-container {
                max-width: 400px;
            }
            
            .brand-logo {
                width: 200px;
            }
            
            .form-title {
                font-size: 1.8rem;
            }
            
            .form-subtitle {
                font-size: 1.1rem;
            }
        }

        /* Responsive para móviles pequeños */
        @media (max-width: 480px) {
            .right-panel {
                padding: 1rem 0.5rem;
            }
            
            .form-container {
                max-width: 100%;
                padding: 0 1rem;
            }
            
            .brand-logo {
                width: 180px;
            }
            
            .form-title {
                font-size: 1.5rem;
            }
            
            .form-subtitle {
                font-size: 1rem;
            }
            
            .form-input {
                font-size: 1rem;
                padding: 0.8rem;
            }
            
            .submit-button {
                font-size: 1rem;
                padding: 0.8rem;
            }
            
            .form-link {
                font-size: 1rem;
            }
            
            .password-requirements {
                font-size: 0.9rem;
                padding: 1rem;
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
                <h1 class="form-title">Restablecer contraseña</h1>
                <p class="form-subtitle">Ingresa tu nueva contraseña</p>

                @if(session('status'))
                    <div class="alert-message alert-success">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert-message alert-error">
                        @foreach($errors->all() as $error)
                            {{ $error }}<br>
                        @endforeach
                    </div>
                @endif
                
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    
                    <div class="password-requirements">
                        <h4>Requisitos de la contraseña:</h4>
                        <ul>
                            <li>Mínimo 8 caracteres</li>
                            <li>Al menos una letra mayúscula</li>
                            <li>Al menos un número</li>
                        </ul>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Nueva contraseña</label>
                        <input id="password" name="password" type="password" class="form-input" required placeholder="Ingresa tu nueva contraseña">
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="form-input" required placeholder="Confirma tu nueva contraseña">
                    </div>



                    <button type="submit" class="submit-button">Restablecer contraseña</button>
                </form>
                
                <a href="{{ route('login') }}" class="form-link">Volver al login</a>
            </div>
        </div>
    </div>
</div>