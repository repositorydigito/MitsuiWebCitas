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
    public function mostrarImagenCampana($id)
    {
        try {
            // Buscar la imagen en la base de datos
            $imagen = DB::table('campaign_images')->where('campaign_id', $id)->first();

            if (! $imagen || empty($imagen->image_path)) {
                Log::warning("[ImagenController] No se encontró imagen para la campaña ID: {$id}");

                return $this->imagenPorDefecto();
            }

            $rutaCompleta = $imagen->image_path;
            Log::info("[ImagenController] Ruta completa de la imagen: {$rutaCompleta}");

            // Normalizar la ruta para evitar problemas con separadores
            $rutaNormalizada = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $rutaCompleta);
            Log::info("[ImagenController] Ruta normalizada: {$rutaNormalizada}");

            // Obtener el nombre del archivo
            $nombreArchivo = basename($rutaNormalizada);
            Log::info("[ImagenController] Nombre del archivo: {$nombreArchivo}");

            // Verificar en múltiples ubicaciones posibles
            $rutasAVerificar = [
                // Ruta específica para las imágenes de campañas (con separadores correctos)
                storage_path('app'.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'campanas'.DIRECTORY_SEPARATOR.$nombreArchivo),
                // Ruta completa en storage (con separadores correctos)
                storage_path('app'.DIRECTORY_SEPARATOR.$rutaNormalizada),
                // Ruta en storage/private (con separadores correctos)
                storage_path('app'.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.$rutaNormalizada),
                // Ruta en storage/private/public/images/campanas (con nombre de archivo)
                storage_path('app'.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'campanas'.DIRECTORY_SEPARATOR.$nombreArchivo),
                // Ruta en public
                public_path($rutaNormalizada),
                // Ruta directa
                $rutaNormalizada,
            ];

            foreach ($rutasAVerificar as $ruta) {
                Log::info("[ImagenController] Intentando acceder a la imagen en: {$ruta}");

                if (file_exists($ruta)) {
                    Log::info("[ImagenController] Imagen encontrada en: {$ruta}");

                    return Response::file($ruta);
                }
            }

            // Si llegamos aquí, la imagen no se encontró en ninguna ubicación
            Log::warning('[ImagenController] No se pudo encontrar la imagen en ninguna ubicación');
            Log::warning('[ImagenController] Rutas verificadas: '.json_encode($rutasAVerificar));

            return $this->imagenPorDefecto();

        } catch (\Exception $e) {
            Log::error('[ImagenController] Error al mostrar imagen de campaña: '.$e->getMessage());
            Log::error('[ImagenController] Stack trace: '.$e->getTraceAsString());

            return $this->imagenPorDefecto();
        }
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
