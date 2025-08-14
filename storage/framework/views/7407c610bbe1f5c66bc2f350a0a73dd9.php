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
            align-items: flex-start;
            justify-content: center;
            padding: 1rem;
            overflow-y: auto;
            min-height: 100vh;
        }
        
        .form-container {
            width: 100%;
            max-width: 400px;
            padding-top: 1rem;
        }
        
        .form-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .form-subtitle {
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
        
        .brand-logo {
            width: 150px;
            height: auto;
            padding-bottom: 1rem;
            margin: 0 auto;
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
        
        .login-link {
            display: block;
            text-align: center;
            margin-top: 0.75rem;
            color: #6b7280;
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s;
        }
        
        .login-link a {
            color: #0075BF;
            text-decoration: none;
            font-weight: 500;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .left-panel {
                display: none;
            }

            .brand-logo {
                width: 100px;
                height: auto;
                padding-bottom: 0.5rem;
                margin: 0 auto;
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
        
        /* Estilos compactos para el formulario */
        
        .fi-input {
            padding: 0.5rem 0.75rem !important;
            font-size: 0.875rem !important;
        }
        
        .fi-fo-field-wrp-label {
            font-size: 0.875rem !important;
        }
        
        /* Estilos para checkboxes */
        .fi-fo-checkbox {
            margin-bottom: 0.25rem !important;
        }
        
        .fi-fo-checkbox .fi-fo-field-wrp-label {
            font-size: 0.4rem !important;
            color: #374151 !important;
            line-height: 1.2 !important;
            margin-bottom: 0 !important;
        }
        
        .fi-checkbox {
            margin-right: 0.25rem !important;
        }
        
        /* Estilos para enlaces en checkboxes */
        .fi-fo-checkbox .fi-fo-field-wrp-label a {
            color: #0075BF !important;
            text-decoration: underline !important;
            font-weight: 500 !important;
            transition: color 0.2s ease !important;
        }
        
        .fi-fo-checkbox .fi-fo-field-wrp-label a:hover {
            color: #073568 !important;
            text-decoration: underline !important;
        }
        
        .fi-fo-checkbox .fi-fo-field-wrp-label a:visited {
            color: #0075BF !important;
        }
        
        /* Estilos para la sección de requisitos */
        .fi-section {
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
            margin: 0.25rem 0 !important;
        }
        
        .fi-section-content {
            padding: 0 !important;
        }
        
        .fi-placeholder {
            margin: 0 !important;
        }
        
        .fi-placeholder-label {
            font-weight: 500 !important;
            color: #374151 !important;
            margin-bottom: 0.25rem !important;
            font-size: 0.875rem !important;
        }
        
        /* Botón más compacto */
        .fi-btn {
            padding: 0.5rem 1rem !important;
            font-size: 0.875rem !important;
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
        <div class="left-panel"></div>

        <!-- Panel derecho - Formulario -->
        <div class="right-panel">
            <div class="form-container">
                <img src="<?php echo e(asset('images/logomitsui2.svg')); ?>" alt="Mitsui Logo" class="brand-logo">
                <h1 class="form-title text-center">Crear Cuenta</h1>
                <!-- Formulario -->
                <?php if (isset($component)) { $__componentOriginald09a0ea6d62fc9155b01d885c3fdffb3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald09a0ea6d62fc9155b01d885c3fdffb3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-panels::components.form.index','data' => ['wire:submit' => 'register']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-panels::form'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:submit' => 'register']); ?>
                    <?php echo e($this->form); ?>


                    <div class="mt-6">
                        <?php if (isset($component)) { $__componentOriginal742ef35d02cb00943edd9ad8ebf61966 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal742ef35d02cb00943edd9ad8ebf61966 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-panels::components.form.actions','data' => ['actions' => $this->getCachedFormActions(),'fullWidth' => $this->hasFullWidthFormActions()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-panels::form.actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['actions' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($this->getCachedFormActions()),'full-width' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($this->hasFullWidthFormActions())]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal742ef35d02cb00943edd9ad8ebf61966)): ?>
<?php $attributes = $__attributesOriginal742ef35d02cb00943edd9ad8ebf61966; ?>
<?php unset($__attributesOriginal742ef35d02cb00943edd9ad8ebf61966); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal742ef35d02cb00943edd9ad8ebf61966)): ?>
<?php $component = $__componentOriginal742ef35d02cb00943edd9ad8ebf61966; ?>
<?php unset($__componentOriginal742ef35d02cb00943edd9ad8ebf61966); ?>
<?php endif; ?>
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

                <!-- Enlace para ir al login -->
                <p class="login-link">
                    ¿Ya tienes cuenta? <a href="<?php echo e(filament()->getLoginUrl()); ?>">Inicia sesión aquí</a>
                </p>
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

            // Función para limitar la longitud del input según el tipo de documento
            function setupDocumentValidation() {
                const documentTypeSelect = document.querySelector('select[wire\\:model="data.document_type"]');
                const documentNumberInput = document.querySelector('input[wire\\:model="data.document_number"]');
                
                if (!documentTypeSelect || !documentNumberInput) {
                    // Si no se encuentran los elementos, intentar de nuevo en 100ms
                    setTimeout(setupDocumentValidation, 100);
                    return;
                }

                function updateInputConstraints() {
                    const documentType = documentTypeSelect.value;
                    let maxLength = 11; // Default
                    
                    switch(documentType) {
                        case 'DNI':
                            maxLength = 8;
                            break;
                        case 'RUC':
                            maxLength = 11;
                            break;
                        case 'CE':
                            maxLength = 9;
                            break;
                    }
                    
                    documentNumberInput.setAttribute('maxlength', maxLength);
                    
                    // Si el valor actual excede la nueva longitud, cortarlo
                    if (documentNumberInput.value.length > maxLength) {
                        documentNumberInput.value = documentNumberInput.value.substring(0, maxLength);
                        // Disparar evento para actualizar Livewire
                        documentNumberInput.dispatchEvent(new Event('input'));
                    }
                }

                // Configurar validación inicial
                updateInputConstraints();
                
                // Escuchar cambios en el tipo de documento
                documentTypeSelect.addEventListener('change', updateInputConstraints);
                
                // Validar entrada en tiempo real
                documentNumberInput.addEventListener('input', function(e) {
                    // Solo permitir números
                    let value = e.target.value.replace(/[^0-9]/g, '');
                    
                    // Aplicar límite de longitud según el tipo
                    const documentType = documentTypeSelect.value;
                    let maxLength = 11;
                    
                    switch(documentType) {
                        case 'DNI':
                            maxLength = 8;
                            break;
                        case 'RUC':
                            maxLength = 11;
                            break;
                        case 'CE':
                            maxLength = 9;
                            break;
                    }
                    
                    if (value.length > maxLength) {
                        value = value.substring(0, maxLength);
                    }
                    
                    if (e.target.value !== value) {
                        e.target.value = value;
                        // Disparar evento para actualizar Livewire
                        e.target.dispatchEvent(new Event('input'));
                    }
                });
            }

            // Inicializar validación de documento
            setupDocumentValidation();
            
            // Re-inicializar después de actualizaciones de Livewire
            document.addEventListener('livewire:navigated', setupDocumentValidation);
            document.addEventListener('livewire:load', setupDocumentValidation);
            
            // Para Livewire v3
            if (window.Livewire) {
                Livewire.hook('morph.updated', () => {
                    setTimeout(setupDocumentValidation, 50);
                });
            }
        });
    </script>
</div>
<?php /**PATH /var/www/projects/mitsui/resources/views/filament/pages/auth/corporate-register.blade.php ENDPATH**/ ?>