<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/src/Service/ImageProcessorService.php';
require_once __DIR__ . '/src/Service/EntityImageService.php';
require_once __DIR__ . '/src/Helper/FormHelper.php';

class ImageResize extends Module
{
    private $formHelper;
    private $entityImageService;
    private $imageProcessorService;

    public function __construct()
    {
        $this->name = 'imageresize';
        $this->tab = 'administration';
        $this->version = '2.5.0';
        $this->author = 'Jonathan Guillerm';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => '9.99.99'
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Image Resize');
        $this->description = $this->l('Redimensionne automatiquement les images selon les paramètres du thème actif');
        $this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller ce module ?');

        $this->initializeServices();
    }

    private function initializeServices()
    {
        if (!$this->imageProcessorService) {
            $this->imageProcessorService = new ImageProcessorService();
        }
        if (!$this->entityImageService) {
            $this->entityImageService = new EntityImageService($this->imageProcessorService);
        }
        if (!$this->formHelper) {
            $this->formHelper = new FormHelper($this);
        }
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionAdminControllerSetMedia')
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
            $output .= $this->processImageResizeRequest();
        }

        return $output . $this->formHelper->renderConfigurationForm();
    }

    private function processImageResizeRequest()
    {
        $entity = Tools::getValue('image_entity', 'products');

        try {
            $count = $this->entityImageService->regenerateImagesByEntity($entity);

            return $this->displayConfirmation(
                sprintf($this->l('%d image(s) redimensionnée(s) avec succès'), $count)
            );
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'ImageResize: Error processing images - ' . $e->getMessage(),
                3,
                null,
                'Module',
                $this->id
            );

            return $this->displayError(
                $this->l('Erreur lors du redimensionnement : ') . $e->getMessage()
            );
        }
    }

    public function hookActionObjectImageAddAfter($params)
    {
        $this->processImageHook($params);
    }

    public function hookActionObjectImageUpdateAfter($params)
    {
        $this->processImageHook($params);
    }

    private function processImageHook($params)
    {
        if (!isset($params['object']) || !$params['object'] instanceof Image) {
            return;
        }

        try {
            $imageTypes = ImageType::getImagesTypes('products');
            $this->imageProcessorService->processProductImage($params['object'], $imageTypes);
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
