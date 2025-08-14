<div class="password-requirements">
    <div class="requirement-item" id="min-length">
        <svg class="requirement-icon" width="14" height="14" viewBox="0 0 16 16" fill="none">
            <circle cx="8" cy="8" r="8" fill="#e5e7eb"/>
            <path d="M6 8l2 2 4-4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Mínimo 8 caracteres</span>
    </div>
    
    <div class="requirement-item" id="uppercase">
        <svg class="requirement-icon" width="14" height="14" viewBox="0 0 16 16" fill="none">
            <circle cx="8" cy="8" r="8" fill="#e5e7eb"/>
            <path d="M6 8l2 2 4-4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>1 letra mayúscula</span>
    </div>
    
    <div class="requirement-item" id="number">
        <svg class="requirement-icon" width="14" height="14" viewBox="0 0 16 16" fill="none">
            <circle cx="8" cy="8" r="8" fill="#e5e7eb"/>
            <path d="M6 8l2 2 4-4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>1 número</span>
    </div>
</div>

<style>
.password-requirements {
    padding: 0;
    margin-top: 0.25rem;
}

.requirement-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    margin-bottom: 0.25rem;
    color: #6b7280;
    font-size: 0.8rem;
}

.requirement-icon {
    flex-shrink: 0;
    width: 14px;
    height: 14px;
}

.requirement-item.valid .requirement-icon circle {
    fill: #10b981;
}

.requirement-item.valid {
    color: #10b981;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function setupPasswordValidation() {
        const passwordInput = document.querySelector('input[wire\\:model="data.password"]');
        
        if (!passwordInput) {
            setTimeout(setupPasswordValidation, 100);
            return;
        }

        function validatePassword() {
            const password = passwordInput.value;
            
            // Validar longitud mínima
            const minLengthItem = document.getElementById('min-length');
            if (password.length >= 8) {
                minLengthItem?.classList.add('valid');
            } else {
                minLengthItem?.classList.remove('valid');
            }
            
            // Validar letra mayúscula
            const uppercaseItem = document.getElementById('uppercase');
            if (/[A-Z]/.test(password)) {
                uppercaseItem?.classList.add('valid');
            } else {
                uppercaseItem?.classList.remove('valid');
            }
            
            // Validar número
            const numberItem = document.getElementById('number');
            if (/\d/.test(password)) {
                numberItem?.classList.add('valid');
            } else {
                numberItem?.classList.remove('valid');
            }
        }

        passwordInput.addEventListener('input', validatePassword);
        passwordInput.addEventListener('keyup', validatePassword);
    }

    setupPasswordValidation();
    
    // Re-inicializar después de actualizaciones de Livewire
    if (window.Livewire) {
        Livewire.hook('morph.updated', () => {
            setTimeout(setupPasswordValidation, 50);
        });
    }
});
</script>