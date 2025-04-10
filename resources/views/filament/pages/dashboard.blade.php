<x-filament::page>
    <div class="space-y-6">
        <div class="p-6 bg-white rounded-lg shadow">
            <h2 class="text-xl font-semibold mb-4">Panel de Administración</h2>
            <p>Bienvenido al panel de administración. Selecciona una opción del menú para gestionar los datos.</p>
            
            <div class="mt-4">
                <a href="{{ url('/vehiculos') }}" class="text-blue-600 hover:underline">Ver listado de vehículos</a>
            </div>
        </div>
    </div>
</x-filament::page> 