<?php

namespace Minimarcket\Core\Helpers;

use finfo;

class UploadHelper
{
    // Tipos permitidos estrictos
    private static $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];

    /**
     * Procesa la subida de una imagen de forma segura.
     * @param array $file El array $_FILES['input_name']
     * @param string $destinationFolder Carpeta destino (con / al final)
     * @return string|false Ruta relativa del archivo guardado o false si falla.
     */
    public static function uploadImage($file, $destinationFolder = '../uploads/product_images/')
    {
        // 1. Verificar errores de subida
        if (!isset($file['error']) || is_array($file['error'])) {
            return false; // Error malformado
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        // 2. Verificar tamaño (Ej: Max 5MB)
        if ($file['size'] > 5000000) {
            return false;
        }

        // 3. Verificar MIME TYPE real (finfo)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!array_key_exists($mime, self::$allowedMimeTypes)) {
            // Log intento fallido de subida (posible ataque)
            error_log("Security Warning: Intento de subir archivo con MIME no permitido: " . $mime);
            return false;
        }

        // 4. Generar nombre aleatorio seguro
        $ext = self::$allowedMimeTypes[$mime];
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $destinationFolder . $filename;

        // 5. Verificar directorio
        if (!is_dir($destinationFolder)) {
            mkdir($destinationFolder, 0755, true);
        }

        // 6. Mover archivo
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Retornar ruta relativa limpia para la BD (asumiendo estructura uploads/)
            return str_replace('../', '', $targetPath);
        }

        return false;
    }
}
