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
            ['module' => 'ps_imageslider', 'table' => 'homeslider_slides'],
            ['module' => 'imageslider', 'table' => 'homeslider'],
        ];

        foreach ($modulePaths as $config) {
            if (Module::isInstalled($config['module'])) {
                $slides = Db::getInstance()->executeS(
                    'SELECT id_homeslider_slides, image FROM ' . _DB_PREFIX_ . $config['table']
                );

                if ($slides) {
                    foreach ($slides as $slide) {
                        $imageField = isset($slide['image']) ? $slide['image'] : null;
                        if ($imageField && $this->imageProcessor->processSlideImage($slide['id_homeslider_slides'], $imageField)) {
                            $count++;
                        }
                    }
                }
                break;
            }
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
