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
            $langTableExists = Db::getInstance()->executeS(
                "SHOW TABLES LIKE '" . _DB_PREFIX_ . "homeslider_slides_lang'"
            );

            if ($langTableExists) {
                $columns = Db::getInstance()->executeS(
                    "SHOW COLUMNS FROM " . _DB_PREFIX_ . "homeslider_slides_lang"
                );

                $columnNames = array_column($columns, 'Field');
                $hasImageUrl = in_array('image_url', $columnNames);
                $hasImage = in_array('image', $columnNames);

                $selectFields = 'hs.id_homeslider_slides';
                if ($hasImage) {
                    $selectFields .= ', hsl.image';
                }
                if ($hasImageUrl) {
                    $selectFields .= ', hsl.image_url';
                }

                $whereConditions = [];
                if ($hasImage) {
                    $whereConditions[] = 'hsl.image IS NOT NULL';
                }
                if ($hasImageUrl) {
                    $whereConditions[] = 'hsl.image_url IS NOT NULL';
                }

                $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' OR ', $whereConditions) : '';

                $slides = Db::getInstance()->executeS(
                    "SELECT {$selectFields}
                     FROM " . _DB_PREFIX_ . "homeslider_slides hs
                     LEFT JOIN " . _DB_PREFIX_ . "homeslider_slides_lang hsl
                     ON hs.id_homeslider_slides = hsl.id_homeslider_slides
                     {$whereClause}
                     GROUP BY hs.id_homeslider_slides"
                );

                if ($slides) {
                    PrestaShopLogger::addLog(
                        'ImageResize: Found ' . count($slides) . ' slides in ps_imageslider',
                        1,
                        null,
                        'ImageResize'
                    );

                    foreach ($slides as $slide) {
                        $imageFile = null;

                        if ($hasImage && !empty($slide['image'])) {
                            $imageFile = $slide['image'];
                        } elseif ($hasImageUrl && !empty($slide['image_url'])) {
                            $imageFile = basename($slide['image_url']);
                        }

                        if ($imageFile) {
                            PrestaShopLogger::addLog(
                                'ImageResize: Processing slide ID ' . $slide['id_homeslider_slides'] . ' with image: ' . $imageFile,
                                1,
                                null,
                                'ImageResize'
                            );

                            if ($this->imageProcessor->processSlideImage($slide['id_homeslider_slides'], $imageFile)) {
                                $count++;
                            }
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
