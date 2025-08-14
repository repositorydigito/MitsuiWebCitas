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
        
        /* Responsive */
        @media (max-width: 1024px) {
            .left-panel {
                display: none;
            }

            .form-container{
                max-width: 50rem;
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
                font-size: 2.5rem;
                margin-bottom: 2rem;
            }

            .form-input{
                font-size: 2.5rem;
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
                            <li>Al menos una letra minúscula</li>
                            <li>Al menos un número</li>
                            <li>Al menos un carácter especial (!@#$%^&*)</li>
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