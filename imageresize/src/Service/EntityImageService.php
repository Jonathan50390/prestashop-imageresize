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

        $modulePaths = [
            ['module' => 'ps_imageslider', 'table' => 'homeslider_slides', 'id_field' => 'id_homeslider_slides', 'img_field' => 'image'],
            ['module' => 'imageslider', 'table' => 'homeslider', 'id_field' => 'id_homeslider_slides', 'img_field' => 'image'],
        ];

        foreach ($modulePaths as $config) {
            if (Module::isInstalled($config['module'])) {
                $tableName = _DB_PREFIX_ . $config['table'];

                $tableExists = Db::getInstance()->executeS(
                    "SHOW TABLES LIKE '" . $tableName . "'"
                );

                if (!$tableExists) {
                    PrestaShopLogger::addLog(
                        'ImageResize: Table ' . $tableName . ' not found',
                        1,
                        null,
                        'ImageResize'
                    );
                    continue;
                }

                $slides = Db::getInstance()->executeS(
                    'SELECT * FROM ' . $tableName
                );

                if ($slides) {
                    PrestaShopLogger::addLog(
                        'ImageResize: Found ' . count($slides) . ' slides in ' . $config['module'],
                        1,
                        null,
                        'ImageResize'
                    );

                    foreach ($slides as $slide) {
                        $imageField = isset($slide[$config['img_field']]) ? $slide[$config['img_field']] : null;
                        $idField = isset($slide[$config['id_field']]) ? $slide[$config['id_field']] : null;

                        if ($imageField && $idField) {
                            if ($this->imageProcessor->processSlideImage($idField, $imageField)) {
                                $count++;
                            }
                        }
                    }
                } else {
                    PrestaShopLogger::addLog(
                        'ImageResize: No slides found in ' . $config['module'],
                        1,
                        null,
                        'ImageResize'
                    );
                }
                break;
            }
        }

        if ($count === 0) {
            PrestaShopLogger::addLog(
                'ImageResize: No slide images were processed. Check if ps_imageslider or imageslider is installed.',
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
