<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablece tu contraseña</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 20px;
        }
        .title {
            color: #0075BF;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .content {
            margin-bottom: 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            margin-bottom: 20px;
            color: #555;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .reset-button {
            display: inline-block;
            background-color: #0075BF;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .reset-button:hover {
            background-color: #073568;
        }
        .expiry-notice {
            font-size: 14px;
            color: #666;
            text-align: center;
            margin-top: 15px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #666;
            text-align: center;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .alternative-link {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            font-size: 14px;
            color: #666;
        }
        .alternative-link a {
            color: #0075BF;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
        <img src="{{ \App\Helpers\EmailImageHelper::imageToBase64('images/logo_Mitsui_Blanco.png') ?: url('images/logo_Mitsui_Blanco.png') }}" alt="logoMitsui" style="margin-right:20px; width: 12rem; height: auto;">
            <h1 class="title">Restablece tu contraseña</h1>
        </div>
        
        <div class="content">
            <p class="greeting">Hola,</p>
            
            <p class="message">
                Recibimos una solicitud para restablecer tu contraseña asociada al documento <strong>{{ $documentType }}: {{ $documentNumber }}</strong>.
            </p>
            
            <p class="message">
                Haz clic en el siguiente botón para crear una nueva contraseña:
            </p>
            
            <div class="button-container">
                <a href="{{ $resetUrl }}" class="reset-button">Restablecer contraseña</a>
            </div>
            
            <p class="expiry-notice">
                <strong>Este enlace es válido por 30 minutos.</strong>
            </p>
            
            <div class="alternative-link">
                <p><strong>¿No puedes hacer clic en el botón?</strong></p>
                <p>Copia y pega el siguiente enlace en tu navegador:</p>
                <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
            </div>
            
            <div class="warning">
                <strong>Importante:</strong> Si tú no solicitaste este cambio, puedes ignorar este mensaje. Tu contraseña actual permanecerá sin cambios.
            </div>
        </div>
        
        <div class="footer">
            <p>Este es un mensaje automático, por favor no respondas a este correo.</p>
            <p>&copy; {{ date('Y') }} Mitsui. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>