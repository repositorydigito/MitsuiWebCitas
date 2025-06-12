<x-filament::page>
    <style>
        /* === ESTILOS BASE === */
        .home-container {
            min-height: 70vh;
            padding: 2rem 0;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            color: #64748b;
        }

        .breadcrumb-icon {
            width: 1rem;
            height: 1rem;
            color: #3b82f6;
        }

        .welcome-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            color: #64748b;
            margin-bottom: 3rem;
            font-size: 1rem;
        }

        .services-container {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            justify-content: flex-start;
        }

        .service-card {
            background: white;
            border-radius: 0.25rem;
            padding: 2rem;
            box-shadow: 0 7px 7px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            text-align: center;
            width: 20rem;
            transition: all 0.2s ease-in-out;
        }

        .service-card:hover {
            box-shadow: 0 6px 8px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transform: translateY(-2px);
            cursor: pointer;
        }

        .service-icon {
            width: 4rem;
            height: 4rem;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .service-title {
            color: #0a7bc2;
            font-weight: 700;
            font-size: 1rem;
        }

        .car-icon {
            width: 100%;
            height: 100%;
        }

        /* === RESPONSIVE DESIGN === */

        /* Desktop (1024px y más) */
        @media (min-width: 1024px) {
            .home-container {
                padding: 3rem 0;
            }

            .welcome-title {
                font-size: 2.25rem;
            }

            .welcome-subtitle {
                font-size: 1.125rem;
                margin-bottom: 4rem;
            }

            .services-container {
                gap: 3rem;
                justify-content: flex-start;
            }

            .service-card {
                width: 22rem;
                padding: 2.5rem;
            }

            .service-icon {
                width: 5rem;
                height: 5rem;
                margin-bottom: 1.5rem;
            }

            .service-title {
                font-size: 1.125rem;
            }
        }

        /* Tablet (768px - 1023px) */
        @media (min-width: 768px) and (max-width: 1023px) {
            .home-container {
                padding: 2rem 1rem;
            }

            .services-container {
                justify-content: center;
                gap: 1.5rem;
            }

            .service-card {
                width: 18rem;
            }
        }

        /* Mobile (hasta 767px) */
        @media (max-width: 767px) {
            .home-container {
                padding: 1rem;
                min-height: 60vh;
            }

            .breadcrumb {
                margin-bottom: 1rem;
                font-size: 0.75rem;
            }

            .welcome-title {
                font-size: 1.5rem;
                text-align: center;
                margin-bottom: 0.75rem;
            }

            .welcome-subtitle {
                font-size: 0.875rem;
                text-align: center;
                margin-bottom: 2rem;
                padding: 0 1rem;
            }

            .services-container {
                flex-direction: column;
                gap: 2rem;
                align-items: center;
            }

            .service-card {
                width: 100%;
                max-width: 280px;
                padding: 1.5rem;
                margin: 0 auto;
            }

            .service-icon {
                width: 3rem;
                height: 3rem;
                margin-bottom: 0.75rem;
            }

            .service-title {
                font-size: 0.875rem;
            }
        }

        /* Mobile pequeño (hasta 480px) */
        @media (max-width: 480px) {
            .home-container {
                padding: 0.5rem;
            }

            .welcome-title {
                font-size: 1.25rem;
            }

            .welcome-subtitle {
                font-size: 0.8rem;
            }

            .service-card {
                max-width: 250px;
                padding: 1rem;
            }

            .service-icon {
                width: 2.5rem;
                height: 2.5rem;
            }

            .service-title {
                font-size: 0.8rem;
            }
        }
    </style>

    <div class="home-container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <svg class="breadcrumb-icon" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
            </svg>
            <span>Home</span>
        </div>

        <!-- Welcome Section -->
        <h1 class="welcome-title">Bienvenido, {{ auth()->user()->name ?? 'Usuario' }}</h1>
        <p class="welcome-subtitle">Accede a cualquiera de las opciones que tenemos para ti.</p>

        <!-- Services Container -->
        <div class="services-container">
            <div class="service-card">
                <div class="service-icon">
                    <img src="{{ asset('images/icono1.svg') }}" alt="Car Icon" class="car-icon">
                </div>
                <div class="service-title">Estado de tu compra</div>
            </div>

            <a href="{{ route('filament.admin.pages.vehiculos') }}" class="service-card" style="text-decoration: none; cursor: pointer;">
                <div class="service-icon">
                    <img src="{{ asset('images/icono1.svg') }}" alt="Car Icon" class="car-icon">
                </div>
                <div class="service-title">Agendamiento de citas</div>
            </a>
        </div>
    </div>
</x-filament::page>
