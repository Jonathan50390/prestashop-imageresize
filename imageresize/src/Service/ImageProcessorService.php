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
        $sourceFile = $this->findSourceFile(
            _PS_PROD_IMG_DIR_ . $image->getImgFolder(),
            $image->id
        );

        if (!$sourceFile) {
            return false;
        }

        foreach ($imageTypes as $imageType) {
            $destFile = _PS_PROD_IMG_DIR_ . $image->getImgFolder() . $image->id . '-' .
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
        $sourceFile = $this->findSourceFile(_PS_CAT_IMG_DIR_, $categoryId);

        if (!$sourceFile) {
            return false;
        }

        foreach ($imageTypes as $imageType) {
            $destFile = _PS_CAT_IMG_DIR_ . $categoryId . '-' .
                        stripslashes($imageType['name']) . '.jpg';

            $this->resizeImage($sourceFile, $destFile, $imageType['width'], $imageType['height']);
        }

        return true;
    }

    public function processManufacturerImage($manufacturerId, array $imageTypes)
    {
        $sourceFile = $this->findSourceFile(_PS_MANU_IMG_DIR_, $manufacturerId);

        if (!$sourceFile) {
            return false;
        }

        foreach ($imageTypes as $imageType) {
            $destFile = _PS_MANU_IMG_DIR_ . $manufacturerId . '-' .
                        stripslashes($imageType['name']) . '.jpg';

            $this->resizeImage($sourceFile, $destFile, $imageType['width'], $imageType['height']);
        }

        return true;
    }

    public function processSupplierImage($supplierId, array $imageTypes)
    {
        $sourceFile = $this->findSourceFile(_PS_SUPP_IMG_DIR_, $supplierId);

        if (!$sourceFile) {
            return false;
        }

        foreach ($imageTypes as $imageType) {
            $destFile = _PS_SUPP_IMG_DIR_ . $supplierId . '-' .
                        stripslashes($imageType['name']) . '.jpg';

            $this->resizeImage($sourceFile, $destFile, $imageType['width'], $imageType['height']);
        }

        return true;
    }
}
