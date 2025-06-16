<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Información</title>
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
            background-color: #0075BF;
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
            border-left: 4px solid #0075BF;
        }
        .info-label {
            font-weight: bold;
            color: #0075BF;
            margin-bottom: 5px;
        }
        .info-value {
            color: #555;
            margin-bottom: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Solicitud de Información</h1>
        <p>{{ $nombrePopup }}</p>
    </div>
    
    <div class="content">
        <p>Estimado equipo,</p>
        
        <p>Se ha recibido una nueva solicitud de información sobre <strong>{{ $nombrePopup }}</strong> con los siguientes datos del cliente:</p>
        
        <div class="info-section">
            <div class="info-label">Nombre Completo:</div>
            <div class="info-value">{{ $datosUsuario['nombres'] ?? 'No proporcionado' }} {{ $datosUsuario['apellidos'] ?? '' }}</div>
            
            <div class="info-label">Número de Celular:</div>
            <div class="info-value">{{ $datosUsuario['celular'] ?? 'No proporcionado' }}</div>
            
            <div class="info-label">Correo Electrónico:</div>
            <div class="info-value">{{ $datosUsuario['email'] ?? 'No proporcionado' }}</div>
            
            @if(isset($datosUsuario['dni']) && !empty($datosUsuario['dni']))
            <div class="info-label">DNI:</div>
            <div class="info-value">{{ $datosUsuario['dni'] }}</div>
            @endif
            
            @if(isset($datosUsuario['placa']) && !empty($datosUsuario['placa']))
            <div class="info-label">Placa del Vehículo:</div>
            <div class="info-value">{{ $datosUsuario['placa'] }}</div>
            @endif
        </div>
        
        <p><strong>Mensaje:</strong> Solicito información sobre {{ $nombrePopup }}. Por favor, contactarme para brindarme más detalles sobre este servicio.</p>
        
        <p>Por favor, proceder a contactar al cliente a la brevedad posible.</p>
        
        <p>Saludos cordiales,<br>
        Sistema de Gestión de Citas - Mitsui</p>
    </div>
    
    <div class="footer">
        <p>Este correo fue generado automáticamente por el sistema de agendamiento de citas.</p>
        <p>Fecha y hora: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
