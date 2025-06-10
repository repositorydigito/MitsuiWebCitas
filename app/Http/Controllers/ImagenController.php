<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class ImagenController extends Controller
{
    /**
     * Muestra la imagen de una campaña
     */
    public function mostrarImagenCampana($idOrFilename)
    {
        try {
            // Si el parámetro contiene una extensión, es un filename
            if (str_contains($idOrFilename, '.')) {
                return $this->mostrarImagenPorNombre($idOrFilename);
            }

            // Si no, es un ID de campaña
            return $this->mostrarImagenPorId($idOrFilename);

        } catch (\Exception $e) {
            Log::error('[ImagenController] Error al mostrar imagen de campaña: '.$e->getMessage());
            Log::error('[ImagenController] Stack trace: '.$e->getTraceAsString());

            return $this->imagenPorDefecto();
        }
    }

    /**
     * Muestra imagen por ID de campaña
     */
    private function mostrarImagenPorId($id)
    {
        // Buscar la imagen en la base de datos
        $imagen = DB::table('campaign_images')->where('campaign_id', $id)->first();

        if (! $imagen || empty($imagen->route)) {
            Log::warning("[ImagenController] No se encontró imagen para la campaña ID: {$id}");
            return $this->imagenPorDefecto();
        }

        return $this->servirArchivo($imagen->route);
    }

    /**
     * Muestra imagen por nombre de archivo (para imágenes en private)
     */
    private function mostrarImagenPorNombre($filename)
    {
        // Buscar en la carpeta private
        $rutaPrivate = 'private/public/images/campanas/' . $filename;

        if (Storage::exists($rutaPrivate)) {
            Log::info("[ImagenController] Imagen encontrada en private: {$rutaPrivate}");
            return $this->servirArchivo($rutaPrivate);
        }

        // Buscar en la carpeta public
        $rutaPublic = 'public/images/campanas/' . $filename;

        if (Storage::exists($rutaPublic)) {
            Log::info("[ImagenController] Imagen encontrada en public: {$rutaPublic}");
            return $this->servirArchivo($rutaPublic);
        }

        Log::warning("[ImagenController] No se encontró imagen con nombre: {$filename}");
        return $this->imagenPorDefecto();
    }

    /**
     * Sirve un archivo desde storage
     */
    private function servirArchivo($ruta)
    {
        $rutaCompleta = storage_path('app/' . $ruta);
        Log::info("[ImagenController] Sirviendo archivo: {$rutaCompleta}");

        if (file_exists($rutaCompleta)) {
            return Response::file($rutaCompleta);
        }

        Log::warning("[ImagenController] Archivo no existe: {$rutaCompleta}");
        return $this->imagenPorDefecto();
    }

    /**
     * Devuelve una imagen por defecto generada dinámicamente
     */
    private function imagenPorDefecto()
    {
        // Crear una imagen en blanco
        $imagen = imagecreatetruecolor(300, 200);
        $colorFondo = imagecolorallocate($imagen, 240, 240, 240);
        $colorTexto = imagecolorallocate($imagen, 100, 100, 100);
        $colorBorde = imagecolorallocate($imagen, 200, 200, 200);

        // Rellenar el fondo
        imagefill($imagen, 0, 0, $colorFondo);

        // Dibujar un borde
        imagerectangle($imagen, 0, 0, 299, 199, $colorBorde);

        // Escribir texto
        imagestring($imagen, 5, 70, 80, 'Imagen no disponible', $colorTexto);

        // Crear una respuesta con la imagen
        ob_start();
        imagepng($imagen);
        $contenido = ob_get_clean();
        imagedestroy($imagen);

        return Response::make($contenido, 200, ['Content-Type' => 'image/png']);
    }
}
