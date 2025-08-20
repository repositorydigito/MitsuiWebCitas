<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmailImageHelper
{
    /**
     * Convierte una imagen a base64 para usar en emails
     * 
     * @param string $imagePath Ruta relativa a la carpeta public o storage
     * @return string|null URL de la imagen en base64 o null si hay error
     */
    public static function imageToBase64($imagePath)
    {
        try {
            // Primero intentamos con la ruta directa
            $fullPath = public_path($imagePath);
            
            // Si no existe, intentamos con storage
            if (!file_exists($fullPath)) {
                $fullPath = storage_path('app/public/' . $imagePath);
                
                if (!file_exists($fullPath)) {
                    Log::warning("La imagen no se encontró en ninguna ruta: {$imagePath}");
                    return null;
                }
            }
            
            $imageData = file_get_contents($fullPath);
            
            if ($imageData === false) {
                throw new \Exception("No se pudo leer el archivo: {$imagePath}");
            }
            
            $mimeType = mime_content_type($fullPath);
            
            if (!$mimeType) {
                $mimeType = self::getMimeTypeFromExtension($fullPath);
            }
            
            return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
            
        } catch (\Exception $e) {
            Log::error('Error en EmailImageHelper: ' . $e->getMessage(), [
                'path' => $imagePath,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    
    /**
     * Obtiene el tipo MIME basado en la extensión del archivo
     */
    protected static function getMimeTypeFromExtension($filename)
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'png' => 'image/png',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
        ];
        
        return $mimeTypes[$ext] ?? 'application/octet-stream';
    }
    
    /**
     * Obtiene la URL de una imagen para usar en correos
     * Primero intenta con base64, si falla usa URL absoluta
     */
    public static function getImageUrl($imagePath, $useBase64 = true)
    {
        if ($useBase64) {
            $base64 = self::imageToBase64($imagePath);
            if ($base64) {
                return $base64;
            }
        }
        
        // Si falla base64 o no se desea usar, devolver URL absoluta
        return asset($imagePath);
    }
}