<?php

namespace ImageResize\Service;

use Category;
use Image;
use ImageType;
use Manufacturer;
use Supplier;

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
            default:
                throw new \Exception('Invalid entity type: ' . $entity);
        }
    }
}
