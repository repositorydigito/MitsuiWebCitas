<style>

    body{
        background-color: #eaf2f9 !important;
    }
    /* Estilos generales para el header */
    header.fi-sidebar-header {
        background-color: #0075BF !important;
        color: #ffffff !important;
        height: 80px !important;
        /* Aumentamos la altura para un aspecto más moderno */
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
        /* Sombra más pronunciada */
        padding: 0 24px !important;
        /* Más espacio interno */
        z-index: 30 !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* Estilos para el logo */
    header.fi-sidebar-header img {
        filter: brightness(0) invert(1) !important;
        height: 48px !important;
        /* Logo más grande */
        width: auto !important;
        transition: transform 0.3s ease;
        /* Efecto de transición suave */
    }

    header.fi-sidebar-header img:hover {
        transform: scale(1.1);
        /* Efecto de escala al pasar el cursor */
    }

    /* Estilos para la barra de navegación */
    nav.flex.h-16.items-center {
        background-color: #0075BF !important;
        color: #ffffff !important;
        height: 80px !important;
        /* Aumentamos la altura */
        padding: 0 24px !important;
        /* Más espacio interno */
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
        border-bottom: 2px solid #005bb5 !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* Estilos para el logo en el header */
    .header-logo {
        height: 48px !important;
        width: auto !important;
        filter: brightness(0) invert(1) !important;
        transition: transform 0.3s ease !important;
        position: absolute !important;
        left: 50% !important;
        transform: translateX(-50%) !important;
        z-index: 10 !important;
    }

    .header-logo:hover {
        transform: translateX(-50%) scale(1.05) !important;
    }

    /* Asegurar que el topbar tenga espacio para el logo */
    .fi-topbar {
        width: 100% !important;
        position: relative !important;
    }

    /* Ajustar el contenedor del logo para centrarlo */
    .fi-topbar > div:first-child {
        position: relative !important;
        display: flex !important;
        justify-content: center !important;
        width: 100% !important;
    }

    /* Estilos para los enlaces de navegación */
    nav.flex.h-16.items-center a {
        color: #ffffff !important;
        text-decoration: none !important;
        font-size: 16px !important;
        /* Tamaño de fuente más grande */
        font-weight: 500 !important;
        /* Fuente más gruesa */
        transition: color 0.3s ease;
        /* Transición suave para el color */
    }

    nav.flex.h-16.items-center a:hover {
        transform: scale(1.01) !important;
    }

    /* Estilos para los botones */
    nav.flex.h-16.items-center button {
        background-color: transparent !important;
        color: #ffffff !important;
        border: none !important;
        height: 40px !important;
        width: 40px !important;
        border-radius: 8px !important;
        /* Bordes más redondeados */
        transition: background-color 0.3s ease, transform 0.3s ease;
        /* Transiciones suaves */
    }

    nav.flex.h-16.items-center button:hover {
        transform: scale(1.1);
        /* Efecto de escala al pasar el cursor */
    }

    /* Estilos para el dropdown del usuario */
    .fi-dropdown-list {
        background-color: white !important;
        border-radius: 0 0 12px 12px !important;
        /* Bordes más redondeados */
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1) !important;
        /* Sombra más pronunciada */
        padding: 8px 0 !important;
    }

    /* Header del dropdown (nombre del usuario) */
    .fi-dropdown-header {
        padding: 12px 16px !important;
        margin-bottom: 4px !important;
    }

    .fi-dropdown-header-label {
        color: #000000 !important;
        font-weight: 600 !important;
        font-size: 14px !important;
    }

    /* Elementos del dropdown */
    .fi-dropdown-list-item {
        padding: 0 !important;
        margin: 0 !important;
        border-radius: 0 !important;
    }

    /* Enlaces y botones dentro del dropdown - Estilos más específicos */
    .fi-dropdown-list-item a,
    .fi-dropdown-list-item button,
    .fi-dropdown-list-item .fi-dropdown-list-item-button {
        display: flex !important;
        align-items: center !important;
        width: 100% !important;
        padding: 12px 16px !important;
        color: #000000 !important;
        font-weight: 500 !important;
        font-size: 14px !important;
        text-decoration: none !important;
        background-color: transparent !important;
        border: none !important;
        border-radius: 0 !important;
        text-align: left !important;
        transition: background-color 0.2s ease !important;
        min-height: 44px !important;
        box-sizing: border-box !important;
    }

    /* Íconos dentro del dropdown */
    .fi-dropdown-list-item-icon,
    .fi-dropdown-list-item svg {
        color: #6b7280 !important;
        width: 16px !important;
        height: 16px !important;
        margin-right: 12px !important;
        flex-shrink: 0 !important;
        display: inline-block !important;
    }

    /* Texto dentro del dropdown */
    .fi-dropdown-list-item-label,
    .fi-dropdown-list-item span {
        color: #000000 !important;
        font-weight: 500 !important;
        font-size: 14px !important;
        overflow: visible !important;
        line-height: 1.4 !important;
    }

    /* Forzar alineación específica para todos los elementos */
    .fi-dropdown-list-item > *,
    .fi-dropdown-list-item a > *,
    .fi-dropdown-list-item button > * {
        display: flex !important;
        align-items: center !important;
        width: 100% !important;
    }

    /* Efecto hover en los elementos del dropdown */
    .fi-dropdown-list-item:hover a,
    .fi-dropdown-list-item:hover button,
    .fi-dropdown-list-item a:hover,
    .fi-dropdown-list-item button:hover {
        background-color: #f9fafb !important;
        color: #000000 !important;
    }

    .fi-dropdown-list-item:hover .fi-dropdown-list-item-icon {
        color: #374151 !important;
    }

    .fi-topbar .fi-input-wrp input {
        color: white !important;
    }

    .fi-topbar .fi-input-wrp input::placeholder {
        color: rgba(255, 255, 255, 0.7) !important;
    }

    /* Iconos en Topbar */
    .fi-topbar .fi-input-wrp-icon {
        color: rgba(255, 255, 255, 0.7) !important;
    }

    /* Forzar estilos en dark mode */
    .dark .fi-dropdown-list-item .fi-dropdown-list-item-label {
        color: #000000 !important;
    }

    .dark .fi-dropdown-list-item a,
    .dark .fi-dropdown-list-item button {
        color: #000000 !important;
    }

    .dark .fi-dropdown-header-label {
        color: #000000 !important;
    }

    /* Estilos específicos para forzar alineación perfecta */
    .fi-user-menu .fi-dropdown-list-item {
        border-bottom: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .fi-user-menu .fi-dropdown-list-item a,
    .fi-user-menu .fi-dropdown-list-item button {
        padding: 12px 16px !important;
        margin: 0 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: flex-start !important;
        gap: 12px !important;
        min-height: 44px !important;
        border: none !important;
        background: transparent !important;
        width: 100% !important;
        text-align: left !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        color: #000000 !important;
    }

    .fi-user-menu .fi-dropdown-list-item:hover a,
    .fi-user-menu .fi-dropdown-list-item:hover button {
        background-color: #f9fafb !important;
    }

    /* Forzar que todos los iconos tengan el mismo tamaño y posición */
    .fi-user-menu .fi-dropdown-list-item svg,
    .fi-user-menu .fi-dropdown-list-item .fi-dropdown-list-item-icon {
        width: 16px !important;
        height: 16px !important;
        flex-shrink: 0 !important;
        margin-left: 17px !important;
        color: #6b7280 !important;
    }

    /* Efecto hover para el botón */
    .fi-icon-btn:hover {
        background-color: rgba(255, 255, 255, 0.2) !important;
        /* Fondo más claro al pasar el cursor */
        transform: scale(1.1) !important;
        /* Efecto de escala al pasar el cursor */
    }

    /* Estilos para el ícono dentro del botón */
    .fi-icon-btn-icon {
        width: 36px !important;
        /* Tamaño del ícono */
        height: 36px !important;
        /* Tamaño del ícono */
        color: #ffffff !important;
        /* Color del ícono */
        transition: transform 0.3s ease !important;
        /* Transición suave para el ícono */
    }

    /* Ocultar sidebar para usuarios no admin */
    @php
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole('super_admin');
    @endphp

    @if(!$isAdmin)
    .fi-sidebar {
        display: none !important;
    }

    .fi-sidebar-nav {
        display: none !important;
    }

    /* Ajustar el contenido principal cuando no hay sidebar - FORZADO */
    .fi-main {
        margin-left: 0 !important;
        width: 100% !important;
        display: flex !important;
        justify-content: center !important;
        padding-left: 2rem !important;
        padding-right: 2rem !important;
        box-sizing: border-box !important;
    }

    /* Contenedor centrado para el contenido - FORZADO */
    .fi-main > div,
    .fi-main .fi-main-content,
    .fi-main .fi-page,
    .fi-main section,
    .fi-main main {
        width: 100% !important;
        max-width: 1200px !important;
        margin: 0 auto !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        box-sizing: border-box !important;
    }

    /* Selectores ultra específicos para forzar centrado */
    body .fi-main {
        margin-left: 0 !important;
        padding-left: 2rem !important;
        padding-right: 2rem !important;
        display: flex !important;
        justify-content: center !important;
        align-items: flex-start !important;
    }

    body .fi-main > * {
        width: 100% !important;
        max-width: 1200px !important;
        margin-left: auto !important;
        margin-right: auto !important;
        flex-shrink: 0 !important;
    }

    /* Forzar en el layout principal */
    .fi-layout {
        display: flex !important;
        justify-content: center !important;
    }

    .fi-layout .fi-main {
        flex: 1 !important;
        max-width: none !important;
        display: flex !important;
        justify-content: center !important;
    }

    /* Ocultar solo el icono del menú hamburguesa */
    .fi-topbar-open-sidebar-btn {
        display: none !important;
    }

    /* Ajustar el topbar cuando no hay sidebar */
    .fi-topbar {
        left: 0 !important;
        width: 100% !important;
    }
    @endif
</style>
