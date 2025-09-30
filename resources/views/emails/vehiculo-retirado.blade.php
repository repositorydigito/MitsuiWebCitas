<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehículo Retirado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 0;
            -webkit-font-smoothing: antialiased;
            -webkit-text-size-adjust: none;
        }
        .header {
            background-color: #ff6b35;
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .logo-container {
            margin-bottom: 20px;
        }
        .logo {
            max-width: 200px;
            height: auto;
            display: block;
            margin: 0 auto 15px;
            width: auto;
            border: 0;
            outline: none;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            line-height: 1.5;
        }
        .info-section {
            background-color: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
            border-left: 4px solid #ff6b35;
        }
        .info-label {
            font-weight: bold;
            color: #ff6b35;
            margin-bottom: 5px;
            display: block;
        }
        .info-value {
            color: #333;
            margin-bottom: 15px;
            display: block;
        }
        .highlight {
            background-color: #fff3cd;
            padding: 20px;
            border-radius: 6px;
            margin: 25px 0;
            border: 1px solid #ffeaa7;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
            line-height: 1.5;
        }
        .alert-icon {
            color: #ffc107;
            font-size: 24px;
            margin-right: 10px;
            vertical-align: middle;
        }
        @media only screen and (max-width: 600px) {
            .content {
                padding: 20px 15px;
            }
            .header {
                padding: 20px 15px;
            }
            .logo {
                max-width: 180px;
            }
            .highlight, .info-section {
                padding: 15px;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">
    <div class="header">
        <h2 style="margin: 0; font-size: 24px; line-height: 1.3;">
            <span class="alert-icon" style="color: #ffc107; font-size: 24px; vertical-align: middle;">🚗</span> 
            Alerta: Vehículo Retirado
        </h2>
    </div>
    
    <div class="content">
        <p><strong>Estimado equipo TSM-Kaizen,</strong></p>
        <p>Se ha retirado un vehículo del sistema. A continuación se detallan los datos del cliente y del vehículo eliminado:</p>

        <div class="highlight">
            <div class="info-label">📅 Fecha y Hora de Retiro:</div>
            <div class="info-value" style="font-size: 18px; font-weight: bold;">
                {{ $fechaRetiro }}
            </div>
        </div>
        
        <div class="info-section">
            <h3 style="color: #ff6b35; margin-top: 0;">👤 DATOS DEL CLIENTE</h3>
            
            <div class="info-label">📝 Nombre Completo:</div>
            <div class="info-value">{{ $cliente->name }}</div>

            <div class="info-label">📧 Email:</div>
            <div class="info-value">{{ $cliente->email }}</div>

            <div class="info-label">📱 Teléfono:</div>
            <div class="info-value">{{ $cliente->phone ?? 'No especificado' }}</div>

            <div class="info-label">🆔 Documento:</div>
            <div class="info-value">{{ $cliente->document_number ?? 'No especificado' }}</div>
        </div>

        <div class="info-section">
            <h3 style="color: #ff6b35; margin-top: 0;">🚗 DATOS DEL VEHÍCULO RETIRADO</h3>
            
            <div class="info-label">🏷️ Placa:</div>
            <div class="info-value" style="font-size: 16px; font-weight: bold;">{{ $vehiculo->license_plate }}</div>

            <div class="info-label">🚙 Marca:</div>
            <div class="info-value">{{ $vehiculo->brand_name }}</div>

            <div class="info-label">🚗 Modelo:</div>
            <div class="info-value">{{ $vehiculo->model }}</div>

            <div class="info-label">📅 Año:</div>
            <div class="info-value">{{ $vehiculo->year }}</div>

            @if($vehiculo->color)
            <div class="info-label">🎨 Color:</div>
            <div class="info-value">{{ $vehiculo->color }}</div>
            @endif

            @if($vehiculo->mileage)
            <div class="info-label">🛣️ Kilometraje:</div>
            <div class="info-value">{{ number_format($vehiculo->mileage) }} km</div>
            @endif
        </div>
        
        <div class="highlight">
            <p><strong>⚠️ Importante:</strong></p>
            <p>Este vehículo ha sido retirado del sistema por el cliente. Se recomienda verificar si existen citas pendientes o servicios programados que deban ser cancelados.</p>
            <p>Por favor, tomar las acciones correspondientes según los procedimientos establecidos.</p>
        </div>
    </div>
    
    <div class="footer">
        <p>Este es un correo automático generado por el sistema de gestión de vehículos de Mitsui Automotriz.</p>
        <p>Fecha de generación: {{ now()->subHours(5)->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>