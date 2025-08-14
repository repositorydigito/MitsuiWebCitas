<?php

namespace App\Helpers;

class EmailImageHelper
{
    /**
     * Convierte una imagen a base64 para usar en emails
     */
    public static function imageToBase64($imagePath)
    {
        $fullPath = public_path($imagePath);
        
        if (!file_exists($fullPath)) {
            return null;
        }
        
        $imageData = file_get_contents($fullPath);
        $mimeType = mime_content_type($fullPath);
        
        return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
    }
    
    /**
     * Obtiene la URL completa de una imagen para emails
     */
    public static function getImageUrl($imagePath)
    {
        return url($imagePath);
    }
}