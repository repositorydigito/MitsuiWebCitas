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
            // Normalizar la ruta eliminando barras iniciales y asegurando el formato correcto
            $imagePath = ltrim(str_replace('\\', '/', $imagePath), '/');
            
            // 1. Intentar con la ruta directa en public
            $fullPath = public_path($imagePath);
            
            // 2. Verificar si el archivo existe y es legible
            if (file_exists($fullPath) && is_file($fullPath) && is_readable($fullPath)) {
                Log::debug("Imagen encontrada en: {$fullPath}");
                $imageData = file_get_contents($fullPath);
                if ($imageData === false) {
                    throw new \Exception("No se pudo leer el archivo: {$fullPath}");
                }
                
                // Obtener el tipo MIME
                $mimeType = mime_content_type($fullPath);
                if (!$mimeType) {
                    $mimeType = self::getMimeTypeFromExtension($fullPath);
                    Log::debug("Tipo MIME determinado por extensión: {$mimeType} para {$fullPath}");
                }
                
                $base64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                
                if (empty($base64)) {
                    throw new \Exception("Error al codificar la imagen a base64");
                }
                
                return $base64;
            }
            
            // 3. Si no se encontró en public, intentar con storage
            $storagePath = 'public/' . ltrim($imagePath, '/');
            if (Storage::exists($storagePath)) {
                $fullPath = storage_path('app/' . $storagePath);
                Log::debug("Imagen encontrada en storage: {$fullPath}");
                
                $imageData = file_get_contents($fullPath);
                if ($imageData === false) {
                    throw new \Exception("No se pudo leer el archivo: {$fullPath}");
                }
                
                $mimeType = Storage::mimeType($storagePath);
                if (!$mimeType) {
                    $mimeType = self::getMimeTypeFromExtension($fullPath);
                }
                
                return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
            }
            
            // Si no se encontró en ninguna ubicación
            Log::warning("La imagen no se encontró en ninguna ruta: {$imagePath}", [
                'public_path' => $fullPath,
                'storage_path' => storage_path('app/' . $storagePath),
                'cwd' => getcwd()
            ]);
            
            return null;
            
            $imageData = file_get_contents($fullPath);
            
            if ($imageData === false) {
                throw new \Exception("No se pudo leer el contenido del archivo: {$fullPath}");
            }
            
            // Obtener el tipo MIME
            $mimeType = mime_content_type($fullPath);
            
            // Si no se pudo determinar, intentar por extensión
            if (!$mimeType) {
                $mimeType = self::getMimeTypeFromExtension($fullPath);
                Log::debug("Tipo MIME determinado por extensión: {$mimeType} para {$fullPath}");
            }
            
            $base64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
            
            // Validar que la cadena base64 no esté vacía
            if (empty($base64) || strpos($base64, 'base64,') === false) {
                throw new \Exception("Error al codificar la imagen a base64");
            }
            
            return $base64;
            
        } catch (\Exception $e) {
            Log::error('Error en EmailImageHelper: ' . $e->getMessage(), [
                'path' => $imagePath,
                'full_path' => $fullPath ?? 'no definido',
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
            'ico' => 'image/x-icon',
        ];
        
        $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
        
        Log::debug("Tipo MIME para extensión .{$ext}: {$mime}");
        
        return $mime;
    }
    
    /**
     * Obtiene la URL de una imagen para usar en correos
     * Primero intenta con base64, si falla usa URL absoluta
     */
    public static function getImageUrl($imagePath, $useBase64 = true)
    {
        // Normalizar la ruta
        $imagePath = ltrim($imagePath, '/');
        
        // Si se solicita base64, intentar primero con esa opción
        if ($useBase64) {
            $base64 = self::imageToBase64($imagePath);
            if ($base64) {
                Log::debug("Imagen codificada en base64 exitosamente", [
                    'path' => $imagePath,
                    'base64_length' => strlen($base64)
                ]);
                return $base64;
            }
            
            Log::warning("No se pudo codificar la imagen en base64, usando URL absoluta", [
                'path' => $imagePath
            ]);
        }
        
        // Generar URL absoluta
        $absoluteUrl = asset($imagePath);
        
        // Verificar si la URL es accesible (solo en entorno local para no ralentizar)
        if (app()->environment('local')) {
            $headers = @get_headers($absoluteUrl);
            $isAccessible = $headers && strpos($headers[0], '200') !== false;
            
            if (!$isAccessible) {
                Log::warning("La URL de la imagen no es accesible: {$absoluteUrl}");
            }
        }
        
        return $absoluteUrl;
    }
}