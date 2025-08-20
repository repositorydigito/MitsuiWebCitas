<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmailImageHelper
{
    /**
     * Obtiene la URL de una imagen para usar en correos
     * 
     * @param string $imagePath Ruta relativa a la carpeta public
     * @param bool $useBase64 Si es true, intenta devolver la imagen en base64
     * @return string URL de la imagen (base64 o URL absoluta)
     */
    public static function getImageUrl($imagePath, $useBase64 = true)
    {
        // 1. Normalizar la ruta (eliminar / inicial si existe)
        $imagePath = ltrim($imagePath, '/');
        
        // 2. Si no se requiere base64, devolver URL absoluta directamente
        if (!$useBase64) {
            return asset($imagePath);
        }
        
        // 3. Intentar con base64
        try {
            // Ruta completa al archivo
            $fullPath = public_path($imagePath);
            
            // Verificar si el archivo existe
            if (!file_exists($fullPath) || !is_file($fullPath)) {
                throw new \Exception("El archivo no existe: {$fullPath}");
            }
            
            // Leer el contenido del archivo
            $imageData = file_get_contents($fullPath);
            if ($imageData === false) {
                throw new \Exception("No se pudo leer el archivo: {$fullPath}");
            }
            
            // Obtener el tipo MIME
            $mimeType = mime_content_type($fullPath);
            if (!$mimeType) {
                $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                $mimeTypes = [
                    'png' => 'image/png',
                    'jpeg' => 'image/jpeg',
                    'jpg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'svg' => 'image/svg+xml',
                    'ico' => 'image/x-icon'
                ];
                $mimeType = $mimeTypes[$ext] ?? 'image/png';
            }
            
            // Codificar a base64
            $base64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
            
            // Verificar que la codificación fue exitosa
            if (empty($base64)) {
                throw new \Exception("Error al codificar la imagen a base64");
            }
            
            return $base64;
            
        } catch (\Exception $e) {
            // En caso de error, devolver URL absoluta como respaldo
            Log::warning('Error al procesar imagen: ' . $e->getMessage(), [
                'path' => $imagePath,
                'full_path' => $fullPath ?? 'no definido'
            ]);
            
            return asset($imagePath);
        }
    }
}
}