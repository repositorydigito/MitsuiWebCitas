@php
    use Illuminate\Support\Facades\Log;
@endphp
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
            padding: 0;
            background-color: #f4f4f4;
            -webkit-font-smoothing: antialiased;
            -webkit-text-size-adjust: none;
        }
        .container {
            background-color: #ffffff;
            padding: 0;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #0075BF;
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
            margin: 0 auto 20px;
            display: block;
            width: auto;
            border: 0;
            outline: none;
        }
        .title {
            color: white;
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            line-height: 1.5;
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
            background-color: #0056b3;
            text-decoration: none;
            color: white;
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
            line-height: 1.5;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .alternative-link {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            font-size: 14px;
            color: #666;
            word-break: break-all;
        }
        .alternative-link a {
            color: #0075BF;
            text-decoration: none;
        }
        .alternative-link a:hover {
            text-decoration: underline;
        }
        @media only screen and (max-width: 600px) {
            .container {
                padding: 20px;
            }
            .title {
                font-size: 20px;
            }
            .reset-button {
                padding: 12px 24px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 20px; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">
    <div class="container">
        <div class="header">
            <div class="logo-container">
                @php
                    // Intentar con EmailImageHelper primero
                    $logoPath = 'images/logo_Mitsui_Blanco.png';
                    $logoUrl = \App\Helpers\EmailImageHelper::getImageUrl($logoPath, true);
                    
                    // Si falla, usar URL directa como fallback
                    if (empty($logoUrl)) {
                        $logoUrl = url('images/logo_Mitsui_Blanco.png');
                        Log::warning('EmailImageHelper falló, usando URL directa para el logo');
                    }
                    
                    $style = "display: block; margin: 0 auto 15px; width: 200px; height: auto;";
                    
                    // Log para depuración
                    Log::info("URL de la imagen (password reset): " . substr($logoUrl, 0, 100) . '...');
                @endphp
                
                <img src="{{ $logoUrl }}" 
                     alt="Mitsui Automotriz" 
                     class="logo"
                     style="{{ $style }}"
                     onerror="console.error('Error al cargar la imagen:', this.src); this.onerror=null; this.src='{{ url('images/logo_Mitsui_Blanco.png') }}'">
            </div>
            <h1 class="title" style="margin: 0; font-size: 24px; line-height: 1.3; color: white;">Restablece tu contraseña</h1>
        </div>
        
        <div class="content">
            <p class="greeting">¡Hola!</p>
            
            <p class="message">Recibiste este correo porque solicitaste restablecer tu contraseña en el sistema de agendamiento de citas de Mitsui Automotriz.</p>
            
            <div class="button-container">
                <a href="{{ $resetUrl }}" class="reset-button" target="_blank" style="color: white; text-decoration: none;">
                    Restablecer contraseña
                </a>
            </div>
            
            <p class="expiry-notice">Este enlace expirará en {{ $expiresInMinutes }} minutos.</p>
            
            <div class="warning">
                <strong>Importante:</strong> Si no solicitaste este restablecimiento, por favor ignora este correo o contacta con nuestro equipo de soporte si tienes alguna pregunta.
            </div>
            
            <div class="alternative-link">
                Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                <a href="{{ $resetUrl }}" target="_blank">{{ $resetUrl }}</a>
            </div>
            
            <!-- Logo adicional al pie del correo -->
            <div style="text-align: center; margin: 30px 0 20px;">
                @php
                    $logo2Path = 'images/logomitsuifooter.png';
                    $logo2Url = \App\Helpers\EmailImageHelper::getImageUrl($logo2Path, true);
                    
                    // Si falla, usar URL directa como fallback
                    if (empty($logo2Url)) {
                        $logo2Url = asset('images/logomitsuifooter.png');
                        $logo2Url = str_replace('http://', 'https://', $logo2Url);
                        Log::warning('EmailImageHelper falló para footer, usando URL directa');
                    }
                @endphp
                <img src="{{ $logo2Url }}" 
                     alt="Mitsui Automotriz" 
                     style="max-width: 200px; height: auto; margin: 0 auto; display: block;"
                     onerror="this.onerror=null; this.src='{{ asset('images/logomitsuifooter.png') }}'">
            </div>
        </div>
        
        <div class="footer">
            <p>Este es un correo automático. Por favor, no respondas a este mensaje.</p>
            <p>Si necesitas ayuda, contáctanos a <a href="mailto:soporte@mitsuiautomotriz.com" style="color: #0075BF; text-decoration: none;">soporte@mitsuiautomotriz.com</a></p>
            
            <div style="margin-top: 20px; font-size: 12px; color: #999;">
                &copy; {{ date('Y') }} Mitsui Automotriz. Todos los derechos reservados.
            </div>
            
            <div style="margin-top: 15px; font-size: 12px; color: #999;">
                <p>Por motivos de seguridad, nunca compartas este enlace con nadie. Mitsui Automotriz nunca te pedirá tu contraseña por correo electrónico.</p>
            </div>
        </div>
    </div>
</body>
</html>