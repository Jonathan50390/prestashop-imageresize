<?php

namespace ImageResize\Helper;

use Configuration;
use HelperForm as PrestashopHelperForm;
use ImageType;
use Tools;

class FormHelper
{
    private $module;

    public function __construct($module)
    {
        $this->module = $module;
    }

    public function renderConfigurationForm()
    {
        $helper = new PrestashopHelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->module->table;
        $helper->module = $this->module;
        $helper->default_form_language = $this->module->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->module->identifier;
        $helper->submit_action = 'submitImageResize';
        $helper->currentIndex = $this->module->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->module->name
            . '&tab_module=' . $this->module->tab
            . '&module_name=' . $this->module->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => [],
            'languages' => $this->module->context->controller->getLanguages(),
            'id_language' => $this->module->context->language->id,
        ];

        return $helper->generateForm([$this->getFormStructure()]);
    }

    private function getFormStructure()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->module->l('Redimensionner les images'),
                    'icon' => 'icon-picture'
                ],
                'description' => $this->generateImageTypesTable(),
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->module->l('Type d\'image à redimensionner'),
                        'name' => 'image_entity',
                        'options' => [
                            'query' => $this->getEntityOptions(),
                            'id' => 'id',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Régénérer toutes les images'),
                        'name' => 'regenerate_all',
                        'is_bool' => true,
                        'desc' => $this->module->l('Attention : Cette opération peut prendre du temps'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->module->l('Oui')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->module->l('Non')
                            ]
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->module->l('Redimensionner'),
                ]
            ],
        ];
    }

    private function getEntityOptions()
    {
        return [
            ['id' => 'products', 'name' => $this->module->l('Produits')],
            ['id' => 'categories', 'name' => $this->module->l('Catégories')],
            ['id' => 'manufacturers', 'name' => $this->module->l('Fabricants')],
            ['id' => 'suppliers', 'name' => $this->module->l('Fournisseurs')],
        ];
    }

    private function generateImageTypesTable()
    {
        $themeImageTypes = $this->getThemeImageTypes();

        $html = '<div class="panel">';
        $html .= '<div class="panel-heading">' . $this->module->l('Types d\'images du thème actif') . '</div>';
        $html .= '<table class="table">';
        $html .= '<thead><tr>';
        $html .= '<th>' . $this->module->l('Nom') . '</th>';
        $html .= '<th>' . $this->module->l('Largeur') . '</th>';
        $html .= '<th>' . $this->module->l('Hauteur') . '</th>';
        $html .= '<th>' . $this->module->l('Entité') . '</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($themeImageTypes as $type) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($type['name']) . '</td>';
            $html .= '<td>' . (int)$type['width'] . 'px</td>';
            $html .= '<td>' . (int)$type['height'] . 'px</td>';
            $html .= '<td>' . htmlspecialchars($type['entity']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    private function getThemeImageTypes()
    {
        $types = [];
        $entities = ['products', 'categories', 'manufacturers', 'suppliers'];

        foreach ($entities as $entity) {
            $imageTypes = ImageType::getImagesTypes($entity);
            foreach ($imageTypes as $type) {
                $types[] = [
                    'name' => $type['name'],
                    'width' => $type['width'],
                    'height' => $type['height'],
                    'entity' => $entity
                ];
            }
        }

        return $types;
    }
}
