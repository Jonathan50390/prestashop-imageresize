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
        $this->version = '5.0.0';
        $this->author = 'Jonathan Guillerm';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => '9.99.99'
        ];
        $this->module_key = '';
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
            $debugInfo = [];

            if ($entity === 'slides') {
                $debugInfo = $this->getSlideDebugInfo();
            }

            $count = $this->entityImageService->regenerateImagesByEntity($entity);

            $message = sprintf($this->l('%d image(s) redimensionnée(s) avec succès'), $count);

            if ($entity === 'slides' && !empty($debugInfo)) {
                $message .= '<br><br><strong>Debug Info:</strong><br>' . implode('<br>', $debugInfo);
            }

            return $this->displayConfirmation($message);
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

    private function getSlideDebugInfo()
    {
        $info = [];

        $modulePaths = [
            ['module' => 'ps_imageslider', 'table' => 'homeslider_slides'],
            ['module' => 'imageslider', 'table' => 'homeslider'],
        ];

        foreach ($modulePaths as $config) {
            $isInstalled = Module::isInstalled($config['module']);
            $info[] = '• Module ' . $config['module'] . ': ' . ($isInstalled ? '<span style="color:green">Installé</span>' : '<span style="color:red">Non installé</span>');

            if ($isInstalled) {
                $tableName = _DB_PREFIX_ . $config['table'];
                $tableExists = Db::getInstance()->executeS("SHOW TABLES LIKE '" . $tableName . "'");
                $info[] = '  → Table ' . $tableName . ': ' . ($tableExists ? '<span style="color:green">Existe</span>' : '<span style="color:red">N\'existe pas</span>');

                if ($tableExists) {
                    $slides = Db::getInstance()->executeS('SELECT * FROM ' . $tableName . ' LIMIT 1');

                    if ($slides) {
                        $firstSlide = $slides[0];
                        $info[] = '  → Colonnes table principale: ' . implode(', ', array_keys($firstSlide));
                    }

                    $langTableName = $tableName . '_lang';
                    $langTableExists = Db::getInstance()->executeS("SHOW TABLES LIKE '" . $langTableName . "'");

                    if ($langTableExists) {
                        $info[] = '  → Table ' . $langTableName . ': <span style="color:green">Existe</span>';

                        $langSlides = Db::getInstance()->executeS('SELECT * FROM ' . $langTableName . ' LIMIT 1');
                        if ($langSlides) {
                            $firstLangSlide = $langSlides[0];
                            $info[] = '  → Colonnes table langues: ' . implode(', ', array_keys($firstLangSlide));
                        }

                        $slidesWithImages = Db::getInstance()->executeS(
                            'SELECT COUNT(*) as total FROM ' . $langTableName . '
                             WHERE image IS NOT NULL OR image_url IS NOT NULL'
                        );
                        $info[] = '  → Slides avec images: ' . $slidesWithImages[0]['total'];
                    }

                    $allSlides = Db::getInstance()->executeS('SELECT COUNT(*) as total FROM ' . $tableName);
                    $info[] = '  → Nombre total de slides: ' . $allSlides[0]['total'];

                    $moduleDir = _PS_ROOT_DIR_ . '/modules/';
                    $possiblePaths = [
                        $moduleDir . 'ps_imageslider/images/',
                        $moduleDir . 'imageslider/images/',
                        $moduleDir . 'blockbanner/img/',
                        _PS_ROOT_DIR_ . '/img/cms/'
                    ];

                    foreach ($possiblePaths as $path) {
                        $exists = file_exists($path);
                        $info[] = '  → Chemin ' . $path . ': ' . ($exists ? '<span style="color:green">Existe</span>' : '<span style="color:red">N\'existe pas</span>');

                        if ($exists) {
                            $files = scandir($path);
                            $imageFiles = array_filter($files, function($f) {
                                return preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f);
                            });
                            $info[] = '    → Fichiers images: ' . count($imageFiles);

                            if (count($imageFiles) > 0 && count($imageFiles) <= 5) {
                                $info[] = '    → Fichiers: ' . implode(', ', array_slice($imageFiles, 0, 5));
                            }
                        }
                    }
                }
            }
        }

        return $info;
    }

    public function hookActionAdminControllerSetMedia()
    {
        // Hook pour charger des CSS/JS si nécessaire
        return true;
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
