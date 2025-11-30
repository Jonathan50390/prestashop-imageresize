<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class EntityImageService
{
    private $imageProcessor;

    public function __construct(ImageProcessorService $imageProcessor)
    {
        $this->imageProcessor = $imageProcessor;
    }

    public function regenerateProductImages()
    {
        $imageTypes = ImageType::getImagesTypes('products');
        $images = Image::getAllImages();
        $count = 0;

        foreach ($images as $image) {
            $imageObj = new Image($image['id_image']);
            if ($this->imageProcessor->processProductImage($imageObj, $imageTypes)) {
                $count++;
            }
        }

        return $count;
    }

    public function regenerateCategoryImages()
    {
        $imageTypes = ImageType::getImagesTypes('categories');
        $categories = Category::getCategories(false, false);
        $count = 0;

        foreach ($categories as $category) {
            $categoryObj = new Category($category['id_category']);
            if ($categoryObj->id_image) {
                if ($this->imageProcessor->processCategoryImage($categoryObj->id, $imageTypes)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public function regenerateManufacturerImages()
    {
        $imageTypes = ImageType::getImagesTypes('manufacturers');
        $manufacturers = Manufacturer::getManufacturers();
        $count = 0;

        foreach ($manufacturers as $manufacturer) {
            if ($this->imageProcessor->processManufacturerImage($manufacturer['id_manufacturer'], $imageTypes)) {
                $count++;
            }
        }

        return $count;
    }

    public function regenerateSupplierImages()
    {
        $imageTypes = ImageType::getImagesTypes('suppliers');
        $suppliers = Supplier::getSuppliers();
        $count = 0;

        foreach ($suppliers as $supplier) {
            if ($this->imageProcessor->processSupplierImage($supplier['id_supplier'], $imageTypes)) {
                $count++;
            }
        }

        return $count;
    }

    public function regenerateStoreImages()
    {
        $imageTypes = ImageType::getImagesTypes('stores');
        $stores = Db::getInstance()->executeS('SELECT id_store FROM ' . _DB_PREFIX_ . 'store');
        $count = 0;

        foreach ($stores as $store) {
            if ($this->imageProcessor->processStoreImage($store['id_store'], $imageTypes)) {
                $count++;
            }
        }

        return $count;
    }

    public function regenerateSlideImages()
    {
        $count = 0;

        if (Module::isInstalled('ps_imageslider')) {
            $slides = Db::getInstance()->executeS(
                'SELECT hs.id_homeslider_slides, hsl.image_url, hsl.image
                 FROM ' . _DB_PREFIX_ . 'homeslider_slides hs
                 LEFT JOIN ' . _DB_PREFIX_ . 'homeslider_slides_lang hsl
                 ON hs.id_homeslider_slides = hsl.id_homeslider_slides
                 WHERE hsl.image_url IS NOT NULL OR hsl.image IS NOT NULL
                 GROUP BY hs.id_homeslider_slides'
            );

            if ($slides) {
                PrestaShopLogger::addLog(
                    'ImageResize: Found ' . count($slides) . ' slides in ps_imageslider',
                    1,
                    null,
                    'ImageResize'
                );

                foreach ($slides as $slide) {
                    $imageFile = !empty($slide['image']) ? $slide['image'] : (!empty($slide['image_url']) ? basename($slide['image_url']) : null);

                    if ($imageFile) {
                        if ($this->imageProcessor->processSlideImage($slide['id_homeslider_slides'], $imageFile)) {
                            $count++;
                        }
                    }
                }
            }
        } elseif (Module::isInstalled('imageslider')) {
            $slides = Db::getInstance()->executeS(
                'SELECT id_homeslider_slides, image FROM ' . _DB_PREFIX_ . 'homeslider'
            );

            if ($slides) {
                PrestaShopLogger::addLog(
                    'ImageResize: Found ' . count($slides) . ' slides in imageslider',
                    1,
                    null,
                    'ImageResize'
                );

                foreach ($slides as $slide) {
                    if (!empty($slide['image'])) {
                        if ($this->imageProcessor->processSlideImage($slide['id_homeslider_slides'], $slide['image'])) {
                            $count++;
                        }
                    }
                }
            }
        }

        if ($count === 0) {
            PrestaShopLogger::addLog(
                'ImageResize: No slide images were processed.',
                2,
                null,
                'ImageResize'
            );
        }

        return $count;
    }

    public function regenerateImagesByEntity($entity)
    {
        switch ($entity) {
            case 'products':
                return $this->regenerateProductImages();
            case 'categories':
                return $this->regenerateCategoryImages();
            case 'manufacturers':
                return $this->regenerateManufacturerImages();
            case 'suppliers':
                return $this->regenerateSupplierImages();
            case 'stores':
                return $this->regenerateStoreImages();
            case 'slides':
                return $this->regenerateSlideImages();
            default:
                throw new Exception('Invalid entity type: ' . $entity);
        }
    }
}
