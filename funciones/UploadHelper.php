<?php
use Minimarcket\Core\Helpers\UploadHelper as CoreUploadHelper;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Core\Helpers\UploadHelper instead.
 */
class UploadHelper
{
    public static function uploadImage($file, $destinationFolder = '../uploads/product_images/')
    {
        return CoreUploadHelper::uploadImage($file, $destinationFolder);
    }
}
