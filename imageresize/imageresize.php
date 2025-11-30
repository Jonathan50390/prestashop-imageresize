<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class ImageResize extends Module
{
    public function __construct()
    {
        $this->name = 'imageresize';
        $this->tab = 'administration';
        $this->version = '2.2.0';
        $this->author = 'Jonathan Guillerm';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => '9.99.99'
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Image Resize');
        $this->description = $this->l('Régénère les images produits selon les types d\'images configurés');
        $this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller ce module ?');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionObjectImageAddAfter')
            && $this->registerHook('actionObjectImageUpdateAfter');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitImageResize')) {
            $output .= $this->processImageRegeneration();
        }

        return $output . $this->renderForm();
    }

    protected function processImageRegeneration()
    {
        $regenerateProducts = Tools::getValue('regenerate_products');
        $regenerateCategories = Tools::getValue('regenerate_categories');
        $regenerateManufacturers = Tools::getValue('regenerate_manufacturers');
        $regenerateSuppliers = Tools::getValue('regenerate_suppliers');

        $output = '';

        if ($regenerateProducts) {
            $result = $this->regenerateProductImages();
            if ($result['success']) {
                $output .= $this->displayConfirmation($result['message']);
            } else {
                $output .= $this->displayError($result['message']);
            }
        }

        if ($regenerateCategories) {
            $result = $this->regenerateCategoryImages();
            if ($result['success']) {
                $output .= $this->displayConfirmation($result['message']);
            } else {
                $output .= $this->displayError($result['message']);
            }
        }

        if ($regenerateManufacturers) {
            $result = $this->regenerateManufacturerImages();
            if ($result['success']) {
                $output .= $this->displayConfirmation($result['message']);
            } else {
                $output .= $this->displayError($result['message']);
            }
        }

        if ($regenerateSuppliers) {
            $result = $this->regenerateSupplierImages();
            if ($result['success']) {
                $output .= $this->displayConfirmation($result['message']);
            } else {
                $output .= $this->displayError($result['message']);
            }
        }

        if (empty($output)) {
            $output = $this->displayError($this->l('Veuillez sélectionner au moins un type d\'image à régénérer'));
        }

        return $output;
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitImageResize';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Régénération des images'),
                    'icon' => 'icon-picture'
                ],
                'input' => [
                    [
                        'type' => 'checkbox',
                        'label' => $this->l('Types d\'images à régénérer'),
                        'name' => 'regenerate',
                        'values' => [
                            'query' => [
                                ['id' => 'products', 'name' => $this->l('Images produits'), 'val' => '1'],
                                ['id' => 'categories', 'name' => $this->l('Images catégories'), 'val' => '1'],
                                ['id' => 'manufacturers', 'name' => $this->l('Images fabricants'), 'val' => '1'],
                                ['id' => 'suppliers', 'name' => $this->l('Images fournisseurs'), 'val' => '1'],
                            ],
                            'id' => 'id',
                            'name' => 'name'
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Régénérer les images'),
                ]
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        return [
            'regenerate_products' => true,
            'regenerate_categories' => false,
            'regenerate_manufacturers' => false,
            'regenerate_suppliers' => false,
        ];
    }

    protected function regenerateProductImages()
    {
        $images = Image::getAllImages();
        $imageTypes = ImageType::getImagesTypes('products');
        $processed = 0;
        $errors = 0;

        foreach ($images as $image) {
            $imageObj = new Image($image['id_image']);

            if ($this->processProductImage($imageObj, $imageTypes)) {
                $processed++;
            } else {
                $errors++;
            }
        }

        return [
            'success' => $errors === 0,
            'message' => sprintf(
                $this->l('%d images produits traitées avec succès (%d erreurs)'),
                $processed,
                $errors
            )
        ];
    }

    protected function regenerateCategoryImages()
    {
        $categories = Category::getCategories(false, false);
        $imageTypes = ImageType::getImagesTypes('categories');
        $processed = 0;

        foreach ($categories as $category) {
            if ($this->processCategoryImage($category['id_category'], $imageTypes)) {
                $processed++;
            }
        }

        return [
            'success' => true,
            'message' => sprintf($this->l('%d images catégories traitées'), $processed)
        ];
    }

    protected function regenerateManufacturerImages()
    {
        $manufacturers = Manufacturer::getManufacturers(false);
        $imageTypes = ImageType::getImagesTypes('manufacturers');
        $processed = 0;

        foreach ($manufacturers as $manufacturer) {
            if ($this->processManufacturerImage($manufacturer['id_manufacturer'], $imageTypes)) {
                $processed++;
            }
        }

        return [
            'success' => true,
            'message' => sprintf($this->l('%d images fabricants traitées'), $processed)
        ];
    }

    protected function regenerateSupplierImages()
    {
        $suppliers = Supplier::getSuppliers(false);
        $imageTypes = ImageType::getImagesTypes('suppliers');
        $processed = 0;

        foreach ($suppliers as $supplier) {
            if ($this->processSupplierImage($supplier['id_supplier'], $imageTypes)) {
                $processed++;
            }
        }

        return [
            'success' => true,
            'message' => sprintf($this->l('%d images fournisseurs traitées'), $processed)
        ];
    }

    protected function processProductImage($image, $imageTypes)
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

            if (!ImageManager::resize($sourceFile, $destFile, (int)$imageType['width'], (int)$imageType['height'])) {
                return false;
            }
        }

        return true;
    }

    protected function processCategoryImage($categoryId, $imageTypes)
    {
        $sourceFile = $this->findSourceFile(_PS_CAT_IMG_DIR_, $categoryId);

        if (!$sourceFile) {
            return false;
        }

        foreach ($imageTypes as $imageType) {
            $destFile = _PS_CAT_IMG_DIR_ . $categoryId . '-' .
                        stripslashes($imageType['name']) . '.jpg';

            ImageManager::resize($sourceFile, $destFile, (int)$imageType['width'], (int)$imageType['height']);
        }

        return true;
    }

    protected function processManufacturerImage($manufacturerId, $imageTypes)
    {
        $sourceFile = $this->findSourceFile(_PS_MANU_IMG_DIR_, $manufacturerId);

        if (!$sourceFile) {
            return false;
        }

        foreach ($imageTypes as $imageType) {
            $destFile = _PS_MANU_IMG_DIR_ . $manufacturerId . '-' .
                        stripslashes($imageType['name']) . '.jpg';

            ImageManager::resize($sourceFile, $destFile, (int)$imageType['width'], (int)$imageType['height']);
        }

        return true;
    }

    protected function processSupplierImage($supplierId, $imageTypes)
    {
        $sourceFile = $this->findSourceFile(_PS_SUPP_IMG_DIR_, $supplierId);

        if (!$sourceFile) {
            return false;
        }

        foreach ($imageTypes as $imageType) {
            $destFile = _PS_SUPP_IMG_DIR_ . $supplierId . '-' .
                        stripslashes($imageType['name']) . '.jpg';

            ImageManager::resize($sourceFile, $destFile, (int)$imageType['width'], (int)$imageType['height']);
        }

        return true;
    }

    protected function findSourceFile($path, $filename)
    {
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        foreach ($extensions as $extension) {
            $testFile = $path . $filename . '.' . $extension;
            if (file_exists($testFile)) {
                return $testFile;
            }
        }

        return null;
    }

    public function hookActionObjectImageAddAfter($params)
    {
        $this->processImageHook($params);
    }

    public function hookActionObjectImageUpdateAfter($params)
    {
        $this->processImageHook($params);
    }

    protected function processImageHook($params)
    {
        if (!isset($params['object']) || !$params['object'] instanceof Image) {
            return;
        }

        try {
            $imageTypes = ImageType::getImagesTypes('products');
            $this->processProductImage($params['object'], $imageTypes);
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'ImageResize: Hook error - ' . $e->getMessage(),
                3,
                null,
                'Image',
                $params['object']->id
            );
        }
    }
}
