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
            $moduleDir . 'blockbanner/img/',
            _PS_ROOT_DIR_ . '/img/cms/'
        ];

        $sourceFile = null;
        $imageDir = null;

        PrestaShopLogger::addLog(
            'ImageResize: Looking for slide image: ' . $imageFile,
            1,
            null,
            'ImageResize'
        );

        foreach ($possiblePaths as $path) {
            PrestaShopLogger::addLog(
                'ImageResize: Checking path: ' . $path,
                1,
                null,
                'ImageResize'
            );

            if (file_exists($path)) {
                $testFile = $path . $imageFile;
                PrestaShopLogger::addLog(
                    'ImageResize: Testing file: ' . $testFile . ' - Exists: ' . (file_exists($testFile) ? 'YES' : 'NO'),
                    1,
                    null,
                    'ImageResize'
                );

                if (file_exists($testFile)) {
                    $sourceFile = $testFile;
                    $imageDir = $path;
                    PrestaShopLogger::addLog(
                        'ImageResize: Found slide image at: ' . $sourceFile,
                        1,
                        null,
                        'ImageResize'
                    );
                    break;
                }
            }
        }

        if (!$sourceFile || !$imageDir) {
            PrestaShopLogger::addLog(
                'ImageResize: Slide image not found: ' . $imageFile,
                2,
                null,
                'ImageResize'
            );
            return false;
        }

        $imageTypes = [
            ['width' => 1110, 'height' => 340, 'name' => 'homeslider'],
            ['width' => 992, 'height' => 304, 'name' => 'homeslider_tablet'],
            ['width' => 768, 'height' => 235, 'name' => 'homeslider_mobile'],
            ['width' => 576, 'height' => 176, 'name' => 'homeslider_xs']
        ];

        $filename = pathinfo($imageFile, PATHINFO_FILENAME);
        $extension = pathinfo($imageFile, PATHINFO_EXTENSION);

        foreach ($imageTypes as $imageType) {
            $destFile = $imageDir . $filename . '-' . $imageType['name'] . '.' . $extension;
            if ($this->resizeImage($sourceFile, $destFile, $imageType['width'], $imageType['height'], $extension)) {
                PrestaShopLogger::addLog(
                    'ImageResize: Created slide: ' . basename($destFile),
                    1,
                    null,
                    'ImageResize'
                );
            }
        }

        return true;
    }
}
