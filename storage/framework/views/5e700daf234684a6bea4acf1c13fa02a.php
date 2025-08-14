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
            background-image: url('<?php echo e(asset('images/portadaMitsui.png')); ?>');
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
            font-size: 1.5rem;
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
            width: 200px;
            height: auto;
            padding-bottom: 2rem;
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

            .brand-logo {
                width: 150px;
                height: auto;
                padding-bottom: 1rem;
                margin: 0 auto;
            }

            .form-title {
                font-size: 1rem;
                font-weight: 700;
                color: #111827;
                margin-bottom: 1rem;
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
                <img src="<?php echo e(asset('images/logomitsui2.svg')); ?>" alt="Mitsui Logo" class="brand-logo">
                <h1 class="form-title text-center">Iniciar Sesión</h1>
                <!-- Formulario -->
                <?php if (isset($component)) { $__componentOriginald09a0ea6d62fc9155b01d885c3fdffb3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald09a0ea6d62fc9155b01d885c3fdffb3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-panels::components.form.index','data' => ['wire:submit' => 'authenticate']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-panels::form'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:submit' => 'authenticate']); ?>
                    <?php echo e($this->form); ?>


                    <div class="checkbox-wrapper flex items-center justify-center mb-6">
                        <a href="<?php echo e(route('password.request')); ?>" class="text-primary-600 hover:underline text-sm">¿Olvidaste tu contraseña?</a>
                    </div>

                    <div class="mt-6 flex gap-2">
                        <button type="submit"
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2"
                                style="background-color: #0075BF;">
                            Entrar
                        </button>
                        <a href="<?php echo e(filament()->getPanel('admin')->getRegistrationUrl()); ?>"
                           class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2"
                           style="background-color: #6c757d; text-align:center; text-decoration:none;">
                            Crear cuenta
                        </a>
                    </div>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald09a0ea6d62fc9155b01d885c3fdffb3)): ?>
<?php $attributes = $__attributesOriginald09a0ea6d62fc9155b01d885c3fdffb3; ?>
<?php unset($__attributesOriginald09a0ea6d62fc9155b01d885c3fdffb3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald09a0ea6d62fc9155b01d885c3fdffb3)): ?>
<?php $component = $__componentOriginald09a0ea6d62fc9155b01d885c3fdffb3; ?>
<?php unset($__componentOriginald09a0ea6d62fc9155b01d885c3fdffb3); ?>
<?php endif; ?>
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
</div><?php /**PATH /var/www/projects/mitsui/resources/views/filament/pages/auth/corporate-login.blade.php ENDPATH**/ ?>