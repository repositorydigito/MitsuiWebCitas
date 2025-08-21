/**
 * Progressive Slot Loader para Filament
 * Mobile-First implementation para cargar slots básicos y validar capacidad en background
 */
class FilamentProgressiveSlotLoader {
    constructor() {
        this.slots = [];
        this.selectedTime = null;
        this.isValidating = false;
    }

    /**
     * Inicializar Progressive Loading
     */
    init(centerId, fecha) {
        this.centerId = centerId;
        this.fecha = fecha;
        
        console.log('🚀 [Progressive Loader] Iniciando carga progresiva', {
            centro: centerId,
            fecha: fecha
        });
        
        // Cargar slots básicos inmediatamente
        this.loadBasicSlots();
    }

    /**
     * Paso 1: Cargar slots básicos desde OData (rápido ~500ms)
     */
    async loadBasicSlots() {
        try {
            console.log('📋 [Progressive Loader] Cargando slots básicos...');
            
            // Usar endpoint existente pero solo para datos básicos OData
            const response = await fetch(`/api/availability/basic/${this.centerId}/${this.fecha}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success && data.slots) {
                console.log('✅ [Progressive Loader] Slots básicos cargados', {
                    total_slots: data.slots.length
                });
                
                // Actualizar UI con slots básicos + loading state
                this.updateExistingSlotsWithLoading(data.slots);
                
                // Iniciar validación en background
                this.startBackgroundValidation();
            } else {
                throw new Error('Invalid response format');
            }
            
        } catch (error) {
            console.error('❌ [Progressive Loader] Error loading basic slots:', error);
            // Fallback: continuar con validación normal
            this.startBackgroundValidation();
        }
    }

    /**
     * Actualizar slots existentes con estado de loading
     */
    updateExistingSlotsWithLoading(basicSlots) {
        // Encontrar contenedor de horarios en Filament
        const timeSelector = document.querySelector('[wire\\:model="selectedTime"], [x-model="selectedTime"]');
        if (!timeSelector) {
            console.warn('⚠️ [Progressive Loader] No se encontró selector de tiempo');
            return;
        }

        const slotsContainer = timeSelector.closest('.grid, .flex, [class*="grid"]') || timeSelector.parentElement;
        
        console.log('🔄 [Progressive Loader] Actualizando UI con loading states');
        
        // Agregar clases de loading a botones existentes
        basicSlots.forEach(slot => {
            const timeButton = this.findTimeButton(slotsContainer, slot.start_time_formatted);
            if (timeButton) {
                // Aplicar estado de checking
                timeButton.classList.add('slot-checking');
                timeButton.disabled = false; // Mantener clickeable
                
                // Agregar indicador de verificación si no existe
                if (!timeButton.querySelector('.verification-indicator')) {
                    this.addVerificationIndicator(timeButton, 'checking');
                }
            }
        });
    }

    /**
     * Paso 2: Validar capacidad en background (lento ~7s)
     */
    async startBackgroundValidation() {
        if (this.isValidating) return;
        this.isValidating = true;
        
        try {
            console.log('🔍 [Progressive Loader] Iniciando validación de capacidad en background...');
            
            // Usar endpoint existente de validación completa
            const response = await fetch(`/api/availability/validated/${this.centerId}/${this.fecha}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success && data.slots) {
                console.log('✅ [Progressive Loader] Validación completada', {
                    slots_validados: data.slots.length,
                    slots_disponibles: data.slots.filter(s => s.is_available).length
                });
                
                this.updateValidatedSlots(data.slots);
            } else {
                throw new Error('Invalid validation response');
            }
            
        } catch (error) {
            console.error('❌ [Progressive Loader] Error en validación:', error);
            // Mantener estado optimista si falla
            this.fallbackToOptimisticState();
        } finally {
            this.isValidating = false;
        }
    }

    /**
     * Actualizar slots con resultados de validación
     */
    updateValidatedSlots(validatedSlots) {
        const slotsContainer = document.querySelector('[wire\\:model="selectedTime"], [x-model="selectedTime"]')?.closest('.grid, .flex, [class*="grid"]');
        if (!slotsContainer) return;

        console.log('🎯 [Progressive Loader] Actualizando slots validados');

        validatedSlots.forEach(slot => {
            const timeButton = this.findTimeButton(slotsContainer, slot.start_time_formatted);
            if (!timeButton) return;

            // Remover loading state
            timeButton.classList.remove('slot-checking');
            
            // Aplicar estado final
            if (slot.is_available) {
                timeButton.classList.add('slot-available');
                timeButton.disabled = false;
                
                // Actualizar indicador con información de capacidad
                this.updateVerificationIndicator(timeButton, 'available', slot.capacity_validation);
                
            } else {
                timeButton.classList.add('slot-unavailable');
                timeButton.disabled = true;
                
                // Actualizar indicador con razón de no disponibilidad
                this.updateVerificationIndicator(timeButton, 'unavailable', slot.capacity_validation);
            }

            // Animación sutil de actualización
            this.animateSlotUpdate(timeButton);
        });
    }

    /**
     * Encontrar botón de tiempo específico
     */
    findTimeButton(container, time) {
        // Buscar por value, data-time, o contenido de texto
        return container.querySelector(`[value="${time}"], [data-time="${time}"]`) ||
               Array.from(container.querySelectorAll('button, [role="button"]'))
                    .find(btn => btn.textContent.includes(time));
    }

    /**
     * Agregar indicador de verificación
     */
    addVerificationIndicator(button, state) {
        const indicator = document.createElement('div');
        indicator.className = 'verification-indicator';
        
        if (state === 'checking') {
            indicator.innerHTML = '<span class="shimmer"></span>Verificando...';
        }
        
        button.appendChild(indicator);
    }

    /**
     * Actualizar indicador de verificación
     */
    updateVerificationIndicator(button, state, capacityValidation) {
        const indicator = button.querySelector('.verification-indicator');
        if (!indicator) return;

        if (state === 'available') {
            const remaining = capacityValidation?.remaining_capacity || 'N/A';
            indicator.innerHTML = `<span class="available-icon">✅</span>${remaining} disponibles`;
            indicator.className = 'capacity-info available';
            
        } else if (state === 'unavailable') {
            const reason = capacityValidation?.reason || 'No disponible';
            indicator.innerHTML = `<span class="unavailable-icon">❌</span>Ocupado`;
            indicator.className = 'capacity-info unavailable';
        }
    }

    /**
     * Animación de actualización
     */
    animateSlotUpdate(button) {
        button.style.transform = 'scale(1.02)';
        button.style.transition = 'transform 0.2s ease';
        
        setTimeout(() => {
            button.style.transform = '';
        }, 200);
    }

    /**
     * Fallback a estado optimista si falla validación
     */
    fallbackToOptimisticState() {
        console.log('🔄 [Progressive Loader] Aplicando estado optimista como fallback');
        
        const slotsContainer = document.querySelector('[wire\\:model="selectedTime"], [x-model="selectedTime"]')?.closest('.grid, .flex, [class*="grid"]');
        if (!slotsContainer) return;

        // Remover loading states y mantener disponible
        slotsContainer.querySelectorAll('.slot-checking').forEach(button => {
            button.classList.remove('slot-checking');
            button.classList.add('slot-available');
            button.disabled = false;
            
            const indicator = button.querySelector('.verification-indicator');
            if (indicator) {
                indicator.innerHTML = '<span class="available-icon">✅</span>Disponible';
                indicator.className = 'capacity-info available';
            }
        });
    }
}

// Auto-inicializar cuando Filament carga
document.addEventListener('DOMContentLoaded', function() {
    console.log('📱 [Progressive Loader] DOM cargado, inicializando...');
    window.FilamentSlotLoader = new FilamentProgressiveSlotLoader();
});

// Hook para Livewire
document.addEventListener('livewire:load', function() {
    console.log('⚡ [Progressive Loader] Livewire cargado, inicializando...');
    window.FilamentSlotLoader = new FilamentProgressiveSlotLoader();
});

// Hook para navegación Livewire
document.addEventListener('livewire:navigated', function() {
    console.log('🔄 [Progressive Loader] Livewire navegado, reinicializando...');
    window.FilamentSlotLoader = new FilamentProgressiveSlotLoader();
});