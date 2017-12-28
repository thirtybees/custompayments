<?php
/**
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    0RS <admin@prestalab.ru>
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2009-2017 PrestaLab.Ru
 * @copyright 2017-2018 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 * This module is based on the original `universalpay` module
 * which you can find on https://github.com/universalpay/universalpay
 *
 * Credits go to PrestaLab.Ru (http://www.prestalab.ru) for making the initial version
 */

use CustomPaymentsModule\CustomPaymentMethod;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class AdminCustomPaymentsController
 */
class AdminCustomPaymentsController extends ModuleAdminController
{
    /**
     * AdminCustomPaymentsController constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // This controller supports nice Bootstrap interfaces
        $this->bootstrap = true;

        // Set the table for this controller
        $this->table = 'custom_payment_method';

        // Set the ObjectModel classname for this controller
        $this->className = 'CustomPaymentsModule\\CustomPaymentMethod';

        // This controller has a multilang ObjectModel
        $this->lang = true;

        // Retrieve the context from a static context, just because
        $this->context = \Context::getContext();

        // Only display this page in single store context
        $this->multishop_context = Shop::CONTEXT_SHOP;

        // Make sure that when we save the `CustomPaymentMethod` ObjectModel, the `_shop` table is set, too (primary => id_shop relation)
        Shop::addTableAssociation('custom_payment_method', ['type' => 'shop']);

        // Add two extra row actions to the list
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->fieldImageSettings = ['name' => 'logo', 'dir' => 'pay'];

        $this->fields_list = [
            'id_custom_payment_method' => ['title' => $this->l('ID'), 'align' => 'center', 'width' => 30],
            'logo'                     => [
                'title'   => $this->l('Logo'),
                'align'   => 'center',
                'image'   => 'pay',
                'orderby' => false,
                'search'  => false,
            ],
            'name'                     => [
                'title' => $this->l('Name'),
                'width' => 150,
                'lang'  => true,
            ],
            'description_short'        => [
                'title'     => $this->l('Short description'),
                'width'     => 450,
                'maxlength' => 90,
                'orderby'   => false,
                'lang'      => true,
            ],
            'active'                   => [
                'title'   => $this->l('Displayed'),
                'active'  => 'status',
                'align'   => 'center',
                'type'    => 'bool',
                'orderby' => false,
            ],
        ];

        parent::__construct();
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        $this->display = 'edit';
        $this->initToolbar();

        if (!$this->loadObject(true)) {
            return null;
        }

        $id = (int) Tools::getValue(CustomPaymentMethod::$definition['primary']);

        $imageUrl = ImageManager::thumbnail(CustomPaymentMethod::getImagePath($id), $this->table."_{$id}.jpg", 200, 'jpg', true, true);
        $imageSize = file_exists(CustomPaymentMethod::getImagePath($id)) ? filesize(CustomPaymentMethod::getImagePath($id)) / 1000 : false;

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Payment methods'),
                'icon'  => 'icon-money',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'required' => true,
                    'lang'     => true,
                    'class'    => 'copy2friendlyUrl',
                    'hint'     => $this->l('Invalid characters:').' <>;=#{}',
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Displayed'),
                    'name'     => 'active',
                    'required' => false,
                    'class'    => 't',
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('Cart type'),
                    'name'     => 'cart_type',
                    'required' => false,
                    'options'  => [
                        'query' => [
                            ['id' => CustomPaymentMethod::CART_BOTH, 'name' => $this->l('Real and virtual')],
                            ['id' => CustomPaymentMethod::CART_REAL, 'name' => $this->l('Real')],
                            ['id' => CustomPaymentMethod::CART_VIRTUAL, 'name' => $this->l('Virtual')],
                        ],
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'     => 'textarea',
                    'label'    => $this->l('Short description'),
                    'name'     => 'description_short',
                    'required' => true,
                    'lang'     => true,
                    'rows'     => 5,
                    'cols'     => 40,
                    'hint'     => $this->l('Invalid characters:').' <>;=#{}',
                    'desc'     => $this->l('Displayed in payment selection page.'),
                ],
                [
                    'type'         => 'textarea',
                    'label'        => $this->l('Description'),
                    'name'         => 'description',
                    'autoload_rte' => true,
                    'lang'         => true,
                    'rows'         => 5,
                    'cols'         => 40,
                    'hint'         => $this->l('Invalid characters:').' <>;=#{}',
                    'desc'         => $this->l('%total% will be replaced with total amount.').' '.$this->l('You can use additional input field with name prefixed up_'),
                ],
                [
                    'type'         => 'textarea',
                    'label'        => $this->l('Description success'),
                    'name'         => 'description_success',
                    'autoload_rte' => true,
                    'lang'         => true,
                    'rows'         => 5,
                    'cols'         => 40,
                    'hint'         => $this->l('Invalid characters:').' <>;=#{}',
                    'desc'         => $this->l('%order_number% will be replaced with order reference, %order_id% - order id, %total% - total amount, %up_field_name - value of input field'),
                ],
                [
                    'type'          => 'file',
                    'label'         => $this->l('Image'),
                    'name'          => 'logo',
                    'display_image' => true,
                    'desc'          => $this->l('Upload payment logo from your computer'),
                    'image'         => $imageUrl ? $imageUrl : false,
                    'size'          => $imageSize,
                    'delete_url'    => static::$currentIndex.'&'.$this->identifier.'='.\Tools::getValue(CustomPaymentMethod::$definition['primary']).'&token='.$this->token.'&deleteImage=1',
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Order state'),
                    'name'    => 'id_order_state',
                    'desc'    => $this->l('Order state after create.'),
                    'options' => [
                        'query' => OrderState::getOrderStates($this->context->language->id),
                        'name'  => 'name',
                        'id'    => 'id_order_state',
                    ],
                ],
                [
                    'type'   => 'checkbox',
                    'label'  => $this->l('Carriers'),
                    'name'   => 'carrierBox',
                    'values' => [
                        'query' => Carrier::getCarriers(
                            $this->context->language->id,
                            true,
                            false,
                            false,
                            null,
                            Carrier::ALL_CARRIERS
                        ),
                        'id'    => 'id_carrier',
                        'name'  => 'name',
                    ],
                    'desc'   => $this->l('The carriers for which this payment method is going to be used'),
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        $cartRules = [0 => ['id_cart_rule' => 0, 'name' => $this->l('No discount')]];

        $sql = new DbQuery();
        $sql->select('cr.`id_cart_rule`, crl.`name`, cr.`reduction_percent`, cr.`reduction_amount`');
        $sql->from('cart_rule', 'cr');
        $sql->innerJoin('cart_rule_lang', 'crl', 'crl.`id_cart_rule` = cr.`id_cart_rule`');
        $sql->where('crl.`id_lang` = '.(int) $this->context->language->id);
        $sql->where('cr.`active` = 1');

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        foreach ($result as $discount) {
            $cartRules[$discount['id_cart_rule']] = [
                'id_cart_rule' => $discount['id_cart_rule'],
                'name'         => $discount['name'].' - '.($discount['reduction_percent'] > 0 ? $discount['reduction_percent'].'%' : $discount['reduction_amount']),
            ];
        }

        if (CartRule::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'    => 'select',
                'label'   => $this->l('Order discount:'),
                'name'    => 'id_cart_rule',
                'desc'    => $this->l('Select cart rule'),
                'options' => [
                    'query' => $cartRules,
                    'name'  => 'name',
                    'id'    => 'id_cart_rule',
                ],
            ];
        }

        if (Group::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'   => 'group',
                'label'  => $this->l('Groups:'),
                'name'   => 'groupBox',
                'values' => Group::getGroups($this->context->language->id),
                'desc'   => $this->l('The customer groups for which this payment method is going to be used'),
            ];
        }

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'  => 'shop',
                'label' => $this->l('Shop association'),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        if (!($obj = $this->loadObject(true))) {
            return null;
        }

        // Added values of object Group
        /** @var CustomPaymentMethod $obj */
        $custompaymentsSystemCarrierIds = $obj->getCarriers();

        $carriers = Carrier::getCarriers($this->context->language->id, false, false, false, null, Carrier::PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);

        foreach ($carriers as $carrier) {
            $this->fields_value['carrierBox_'.$carrier['id_carrier']] = Tools::getValue(
                'carrierBox_'.$carrier['id_carrier'],
                (in_array($carrier['id_carrier'], $custompaymentsSystemCarrierIds))
            );
        }

        $custompaymentsSystemGroupIds = $obj->getGroups();

        if (Group::isFeatureActive()) {
            $groups = Group::getGroups($this->context->language->id);

            foreach ($groups as $group) {
                $this->fields_value['groupBox_'.$group['id_group']] = Tools::getValue(
                    'groupBox_'.$group['id_group'],
                    (in_array($group['id_group'], $custompaymentsSystemGroupIds))
                );
            }
        }

        return parent::renderForm();
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (\Tools::isSubmit('deleteImage')) {
            return $this->processForceDeleteImage();
        } else {
            $return = parent::postProcess();

            if (Tools::getValue('submitAdd'.$this->table) && Validate::isLoadedObject($return)) {
                $carriers = Carrier::getCarriers($this->context->language->iso_code, false, false, false, null, Carrier::PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);
                $carrierBox = [];
                foreach ($carriers as $carrier) {
                    if (Tools::getIsset('carrierBox_'.$carrier['id_carrier'])) {
                        $carrierBox[] = $carrier['id_carrier'];
                    }
                }
                $return->updateCarriers($carrierBox);
                if (Group::isFeatureActive()) {
                    $return->updateGroups(Tools::getValue('groupBox'));
                }
                if (Shop::isFeatureActive()) {
                    $this->updateAssoShop($return->id);
                }
            }

            return $return;
        }
    }

    /**
     * @since 1.0.0
     */
    public function processForceDeleteImage()
    {
        $customPaymentMethod = $this->loadObject(true);

        if (\Validate::isLoadedObject($customPaymentMethod)) {
            $this->deleteImage($customPaymentMethod->id);
        }
    }

    /**
     * @param int $idBeesBlogCategory
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function deleteImage($idBeesBlogCategory)
    {
        $deleted = false;
        // Delete base image
        foreach (['png', 'jpg'] as $extension) {
            if (file_exists(_PS_IMG_DIR_."pay/{$idBeesBlogCategory}.{$extension}")) {
                unlink(_PS_IMG_DIR_."pay/{$idBeesBlogCategory}.{$extension}");
            }
        }

        if ($deleted) {
            $this->confirmations[] = $this->l('Successfully deleted image');
        }

        return true;
    }

    /**
     * @param int         $id
     * @param string      $name
     * @param string      $dir
     * @param string|bool $ext
     * @param int|null    $width
     * @param int|null    $height
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function uploadImage($id, $name, $dir, $ext = false, $width = null, $height = null)
    {
        $width = (int) Configuration::get(CustomPayments::IMAGE_WIDTH);
        $height = (int) Configuration::get(CustomPayments::IMAGE_HEIGHT);

        return parent::uploadImage($id, $name, $dir, $ext, $width, $height);
    }
}
