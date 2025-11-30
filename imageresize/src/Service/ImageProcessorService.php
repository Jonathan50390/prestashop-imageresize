<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class ImageProcessorService
{
    private const SUPPORTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function findSourceFile($path, $filename)
    {
        foreach (self::SUPPORTED_EXTENSIONS as $extension) {
            $testFile = $path . $filename . '.' . $extension;
            if (file_exists($testFile)) {
                return $testFile;
            }
        }
        return null;
    }

    public function resizeImage($sourceFile, $destFile, $width, $height, $format = 'jpg')
    {
        return ImageManager::resize(
            $sourceFile,
            $destFile,
            (int)$width,
            (int)$height,
            $format
        );
    }

    public function processProductImage(Image $image, array $imageTypes)
    {
        $imageDir = defined('_PS_PROD_IMG_DIR_') ? _PS_PROD_IMG_DIR_ : _PS_ROOT_DIR_ . '/img/p/';

        $sourceFile = $this->findSourceFile(
            $imageDir . $image->getImgFolder(),
            $image->id
        );

        if (!$sourceFile) {
            return false;
        }

        foreach ($imageTypes as $imageType) {
            $destFile = $imageDir . $image->getImgFolder() . $image->id . '-' .
                        stripslashes($imageType['name']) . '.jpg';

            if (!$this->resizeImage($sourceFile, $destFile, $imageType['width'], $imageType['height'])) {
                PrestaShopLogger::addLog(
                    'ImageResize: Failed to resize image ' . $image->id . ' to ' . $imageType['name'],
                    3,
                    null,
                    'Image',
                    $image->id
                );
                return false;
            }
        }

        return true;
    }

    public function processCategoryImage($categoryId, array $imageTypes)
    {
        $imageDir = defined('_PS_CAT_IMG_DIR_') ? _PS_CAT_IMG_DIR_ : _PS_ROOT_DIR_ . '/img/c/';

        $sourceFile = $this->findSourceFile($imageDir, $categoryId);

        if (!$sourceFile) {
            return false;
        }

        foreach ($imageTypes as $imageType) {
            $destFile = $imageDir . $categoryId . '-' .
                        stripslashes($imageType['name']) . '.jpg';

            $this->resizeImage($sourceFile, $destFile, $imageType['width'], $imageType['height']);
        }

        return true;
    }

    public function processManufacturerImage($manufacturerId, array $imageTypes)
    {
        $imageDir = defined('_PS_MANU_IMG_DIR_') ? _PS_MANU_IMG_DIR_ : _PS_ROOT_DIR_ . '/img/m/';

        $sourceFile = $this->findSourceFile($imageDir, $manufacturerId);

        if (!$sourceFile) {
            return false;
        }

        foreach ($imageTypes as $imageType) {
            $destFile = $imageDir . $manufacturerId . '-' .
                        stripslashes($imageType['name']) . '.jpg';

            $this->resizeImage($sourceFile, $destFile, $imageType['width'], $imageType['height']);
        }

        return true;
    }

    public function processSupplierImage($supplierId, array $imageTypes)
    {
        $imageDir = defined('_PS_SUPP_IMG_DIR_') ? _PS_SUPP_IMG_DIR_ : _PS_ROOT_DIR_ . '/img/su/';

        $sourceFile = $this->findSourceFile($imageDir, $supplierId);

        if (!$sourceFile) {
            return false;
        }

        foreach ($imageTypes as $imageType) {
            $destFile = $imageDir . $supplierId . '-' .
                        stripslashes($imageType['name']) . '.jpg';

            $this->resizeImage($sourceFile, $destFile, $imageType['width'], $imageType['height']);
        }

        return true;
    }
}
