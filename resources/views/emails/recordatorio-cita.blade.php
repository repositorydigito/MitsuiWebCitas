<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorio de Cita</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #17a2b8;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .info-section {
            background-color: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 6px;
            border-left: 4px solid #17a2b8;
        }
        .info-label {
            font-weight: bold;
            color: #17a2b8;
            margin-bottom: 5px;
        }
        .info-value {
            color: #555;
            margin-bottom: 10px;
        }
        .highlight {
            background-color: #d1ecf1;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border: 1px solid #17a2b8;
        }
        .urgent {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border: 1px solid #ffc107;
            color: #856404;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
        }
        .reminder-icon {
            color: #17a2b8;
            font-size: 24px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><span class="reminder-icon">🔔</span> Recordatorio de Cita</h1>
        <p>{{ $appointment->appointment_number }}</p>
    </div>
    
    <div class="content">
        <p>Estimado/a <strong>{{ $datosCliente['nombres'] }} {{ $datosCliente['apellidos'] }}</strong>,</p>
        
        <p>Te recordamos que tienes una cita agendada para <strong>mañana</strong>.</p>
        
        <div class="urgent">
            <div class="info-label">⏰ Tu cita es MAÑANA:</div>
            <div class="info-value" style="font-size: 20px; font-weight: bold;">
                {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('d/m/Y') }} a las {{ \Carbon\Carbon::parse($appointment->appointment_time)->format('H:i') }}
            </div>
        </div>
        
        <div class="info-section">
            <div class="info-label">🏢 Local:</div>
            <div class="info-value">{{ $appointment->premise->name ?? 'No especificado' }}</div>
            
            <div class="info-label">🚗 Vehículo:</div>
            <div class="info-value">{{ $datosVehiculo['marca'] ?? '' }} {{ $datosVehiculo['modelo'] ?? '' }} - Placa: {{ $datosVehiculo['placa'] ?? '' }}</div>
            
            <div class="info-label">🔧 Servicio:</div>
            <div class="info-value">{{ $appointment->service_type ?? 'Servicio general' }}</div>
            
            @if($appointment->maintenance_type)
            <div class="info-label">⚙️ Tipo de Mantenimiento:</div>
            <div class="info-value">{{ $appointment->maintenance_type }}</div>
            @endif
            
            @if($appointment->comments)
            <div class="info-label">💬 Comentarios:</div>
            <div class="info-value">{{ $appointment->comments }}</div>
            @endif
        </div>
        
        <div class="info-section">
            <div class="info-label">📞 Datos de Contacto:</div>
            <div class="info-value">
                <strong>Teléfono:</strong> {{ $datosCliente['celular'] }}<br>
                <strong>Email:</strong> {{ $datosCliente['email'] }}
            </div>
        </div>
        
        <div class="highlight">
            <p><strong>📋 Checklist para mañana:</strong></p>
            <ul>
                <li>✅ Llega 10 minutos antes de tu cita</li>
                <li>✅ Trae tu DNI original</li>
                <li>✅ Trae la tarjeta de propiedad del vehículo</li>
                <li>✅ Si tienes algún problema, llámanos con anticipación</li>
                <li>✅ Revisa que tengas combustible suficiente para llegar</li>
            </ul>
        </div>
        
        <div class="urgent">
            <p><strong>⚠️ ¿Necesitas reprogramar?</strong></p>
            <p>Si no puedes asistir mañana, por favor contáctanos lo antes posible para reprogramar tu cita.</p>
        </div>
        
        <p>¡Te esperamos mañana!</p>
        
        <p>Saludos cordiales,<br>
        <strong>Equipo de Servicio - Mitsui</strong></p>
    </div>
    
    <div class="footer">
        <p>Este es un recordatorio automático del sistema de agendamiento de citas.</p>
        <p>Fecha y hora: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>