<style>
    /* Estilos para el footer */
    .footer {
        background-color: #073568 !important;
        color: #ffffff !important;
        padding: 5px 15px !important;
        z-index: 10 !important;
        position: relative !important;
        width: 100% !important;
    }

    /* Estilos para el sidebar */
    .fi-sidebar {
        z-index: 50 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        height: 100vh !important;
        width: 16rem !important;
        background-color: #f8f9fa !important;
    }
</style>

<footer class="footer bottom-0 left-0 z-10 w-full p-4 bg-gray-800 text-white hidden md:flex flex-col">
    <div class="container mx-auto flex flex-col md:flex-row justify-between items-center">
        <div class="p-6 flex flex-row items-center w-full justify-start gap-x-4">
            <!-- Logo -->
            <div class="flex items-center">
                <img src="{{ asset('images/logo_Mitsui_Blanco.png') }}" alt="logoMitsui" style="margin-right:20px; width: 12rem; height: auto;">
            </div>

            <!-- Derechos Reservados -->
            <div class="text-right" style="font-size: 12px;">
                <p>© Copyright 2024. All Rights Reserved</p>
            </div>
        </div>
    </div>
</footer>
