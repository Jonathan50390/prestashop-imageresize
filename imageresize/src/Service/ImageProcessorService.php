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

    public function processStoreImage($storeId, array $imageTypes)
    {
        $imageDir = defined('_PS_STORE_IMG_DIR_') ? _PS_STORE_IMG_DIR_ : _PS_ROOT_DIR_ . '/img/st/';

        $sourceFile = $this->findSourceFile($imageDir, $storeId);

        if (!$sourceFile) {
            return false;
        }

        foreach ($imageTypes as $imageType) {
            $destFile = $imageDir . $storeId . '-' .
                        stripslashes($imageType['name']) . '.jpg';

            $this->resizeImage($sourceFile, $destFile, $imageType['width'], $imageType['height']);
        }

        return true;
    }

    public function processSlideImage($slideId, $imageFile)
    {
        $moduleDir = _PS_ROOT_DIR_ . '/modules/';

        $possiblePaths = [
            $moduleDir . 'ps_imageslider/images/',
            $moduleDir . 'imageslider/images/',
            $moduleDir . 'blockbanner/img/'
        ];

        $sourceFile = null;
        $imageDir = null;

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $testFile = $path . $imageFile;
                if (file_exists($testFile)) {
                    $sourceFile = $testFile;
                    $imageDir = $path;
                    break;
                }
            }
        }

        if (!$sourceFile || !$imageDir) {
            return false;
        }

        $imageTypes = [
            ['width' => 1920, 'height' => 600, 'name' => 'slider_lg'],
            ['width' => 1200, 'height' => 450, 'name' => 'slider_md'],
            ['width' => 768, 'height' => 350, 'name' => 'slider_sm'],
            ['width' => 576, 'height' => 250, 'name' => 'slider_xs']
        ];

        $filename = pathinfo($imageFile, PATHINFO_FILENAME);
        $extension = pathinfo($imageFile, PATHINFO_EXTENSION);

        foreach ($imageTypes as $imageType) {
            $destFile = $imageDir . $filename . '-' . $imageType['name'] . '.' . $extension;
            $this->resizeImage($sourceFile, $destFile, $imageType['width'], $imageType['height'], $extension);
        }

        return true;
    }
}
