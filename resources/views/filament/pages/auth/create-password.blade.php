<div>
    <style>
        .form-container {
            max-width: 400px;
            margin: 0 auto;
        }
        .form-title {
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }
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
            background-color: white !important;
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
    </style>
    <div class="form-container">
        <h1 class="form-title text-center">Crear Cuenta</h1>
        <form method="POST" action="#">
            <div class="form-group">
                <label class="form-label" for="tipo_documento">Tipo de Documento</label>
                <select class="form-input" id="tipo_documento" name="tipo_documento" required>
                    <option value="">Seleccione</option>
                    <option value="DNI">DNI</option>
                    <option value="CE">Carnet de Extranjería</option>
                    <option value="RUC">RUC</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="numero_documento">Número de Documento</label>
                <input class="form-input" type="text" id="numero_documento" name="numero_documento" required />
            </div>
            <button type="submit" class="submit-button">Crear cuenta</button>
        </form>
    </div>
</div>
