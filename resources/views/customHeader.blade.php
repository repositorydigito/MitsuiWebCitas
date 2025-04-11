<style>
    /* Estilos para la barra de navegación superior de Filament */
    .fi-topbar {
        height: 80px !important;
        gap: 1rem !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    /* Contenedor para el logo y las pestañas */
    .header-content {
        display: flex;
        align-items: center;
        gap: 10rem;
        width: 100%;
        margin-right: auto;
    }

    /* Estilos para el logo */
    .header-logo {
        display: flex;
        align-items: center;
    }

    .header-logo img {
        height: 48px !important;
        width: auto !important;
        transition: transform 0.3s ease;
    }

    /* Estilos para las pestañas */
    .header-tabs {
        display: flex;
        gap: 2rem;
        flex: 1;
        justify-content: center;
        margin-right: 40rem;
    }

    .tab {
        color: #0075BF !important;
        text-decoration: none !important;
        font-size: 16px !important;
        font-weight: 500 !important;
        padding: 0.5rem 1rem !important;
        border-radius: 8px !important;
        transition: all 0.3s ease !important;
    }

    .tab:hover {
        background-color: rgba(0, 117, 191, 0.1) !important;
    }

    .tab.active {
        background-color: #0075BF !important;
        color: white !important;
    }

    /* Estilos generales para el header */
    header.fi-sidebar-header {
        background-color: rgb(255, 255, 255) !important;
        color: #0075BF !important;
        height: 80px !important;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
        z-index: 30 !important;
    }

    /* Contenedor principal */
    .header-main {
        display: flex;
        align-items: center;
        height: 100%;
        padding-left: 4rem;
        padding-right: 1rem;
    }

    /* Estilos para el menú de usuario */
    .header-menu {
        margin-left: auto;
    }

    /* Estilos para el dropdown del usuario */
    .fi-dropdown-list {
        background-color: white !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 12px !important;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1) !important;
    }

    .fi-dropdown-list-item-label {
        color: #000000 !important;
        font-weight: 500 !important;
        overflow: visible;
    }

    .fi-dropdown-list-item-icon {
        color: #000000 !important;
        overflow: visible;
    }

    .fi-dropdown-list-item:hover {
        background-color: rgba(0, 0, 0, 0.05) !important;
    }

    .fi-dropdown-list-item button {
        color: #000000 !important;
        font-weight: 500 !important;
    }

    .fi-dropdown-header-label {
        color: #000000 !important;
        font-weight: 500 !important;
    }

    /* Estilos para el botón de salir */
    .fi-dropdown-list-item button.fi-dropdown-list-item-button {
        color: #0075BF !important;
        font-weight: 500 !important;
        background-color: transparent !important;
        border: none !important;
        padding: 8px 16px !important;
        width: 100% !important;
        text-align: left !important;
    }

    .fi-dropdown-list-item button.fi-dropdown-list-item-button:hover {
        background-color: rgba(0, 0, 0, 0.05) !important;
        color: #0075BF !important;
    }

    .dark .fi-dropdown-list-item .fi-dropdown-list-item-label {
        color: #0075BF !important;
    }

    .fi-icon-btn {
        margin-left: 20px;
    }

    .fi-icon-btn:hover {
        background-color: rgba(255, 255, 255, 0.2) !important;
        transform: scale(1.1) !important;
    }

    .fi-icon-btn-icon {
        width: 36px !important;
        height: 36px !important;
        color: #0075BF !important;
        transition: transform 0.3s ease !important;
    }
</style>

<div class="header-content">
    <div class="header-logo">
        <img src="{{ asset('images/logoMitsui.svg') }}" alt="Logo">
    </div>
    <div class="header-tabs">
        <a href="?activeTab=Z01" class="tab {{ request()->query('activeTab') === 'Z01' ? 'active' : '' }}">TOYOTA</a>
        <a href="?activeTab=Z02" class="tab {{ request()->query('activeTab') === 'Z02' ? 'active' : '' }}">LEXUS</a>
        <a href="?activeTab=Z03" class="tab {{ request()->query('activeTab') === 'Z03' ? 'active' : '' }}">HINO</a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.tab');
        const currentTab = new URLSearchParams(window.location.search).get('activeTab') || 'Z01';

        tabs.forEach(tab => {
            if (tab.getAttribute('href').includes(currentTab)) {
                tab.classList.add('active');
            }
        });

        // Añadir evento click a los tabs
        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                const tabValue = this.getAttribute('href').split('=')[1];
                window.location.href = window.location.pathname + '?activeTab=' + tabValue;
            });
        });
    });
</script>
