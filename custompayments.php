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

require_once __DIR__.'/vendor/autoload.php';

/**
 * Class CustomPayments
 *
 * @since 1.0.0
 */
class CustomPayments extends PaymentModule
{
    const CONFIRMATION_BUTTON = 'CUSTOMPAYMENTS_CONF_BUTTON';
    const IMAGE_WIDTH = 'CUSTOMPAYMENTS_IMAGE_WIDTH';
    const IMAGE_HEIGHT = 'CUSTOMPAYMENTS_IMAGE_HEIGHT';

    protected $paymentMethods = false;

    /**
     * CustomPayments constructor.
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'custompayments';
        $this->tab = 'payments_gateways';
        $this->version = '1.2.2';
        $this->author = 'thirty bees';
        $this->need_instance = 1;

        $this->controllers = ['payment', 'validation'];

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Custom payment methods');
        $this->description = $this->l('Add custom payment methods to your store.');
        Shop::addTableAssociation('custom_payment_method', ['type' => 'shop']);
    }

    /**
     * Install this module
     *
     * @return bool Indicates whether this module has been installed successfully
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function install()
    {
        CustomPaymentMethod::createDatabase();

        if (!parent::install()) {
            return false;
        }

        $this->registerHook('displayPayment');
        $this->registerHook('displayPaymentEU');
        $this->registerHook('actionCarrierUpdate');
        $this->registerHook('displayOrderDetail');
        $this->registerHook('displayPaymentReturn');
        $this->registerHook('advancedPaymentOptions');

        mkdir(_PS_IMG_DIR_.'pay');
        static::installModuleTab(
            'AdminCustomPayments',
            ['default' => 'Custom Payment Methods'],
            'AdminParentModules'
        );

        Configuration::updateGlobalValue(static::CONFIRMATION_BUTTON, true);
        Configuration::updateGlobalValue(static::IMAGE_HEIGHT, 64);
        Configuration::updateGlobalValue(static::IMAGE_WIDTH, 64);

        return true;
    }

    /**
     * Install this module's tab
     *
     * @param string $tabClass
     * @param array  $tabName
     * @param int    $tabParent
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    private function installModuleTab($tabClass, $tabName, $tabParent)
    {
        if (defined('TB_INSTALLATION_IN_PROGRESS')) {
            return true;
        }

        if (!($idTabParent = Tab::getIdFromClassName($tabParent))) {
            return false;
        }

        $tab = new Tab();
        $languages = Language::getLanguages(true);
        foreach ($languages as $language) {
            if (!isset($tabName[$language['iso_code']])) {
                $tab->name[$language['id_lang']] = $tabName['default'];
            } else {
                $tab->name[(int) $language['id_lang']] = $tabName[$language['iso_code']];
            }
        }
        $tab->class_name = $tabClass;
        $tab->module = $this->name;
        $tab->id_parent = $idTabParent;
        $tab->active = 1;

        if (!$tab->save()) {
            return false;
        }

        return true;
    }

    /**
     * Uninstall this module
     *
     * @return bool Indicates whether this module has been successfully uninstalled
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstall()
    {
        CustomPaymentMethod::dropDatabase();

        static::uninstallModuleTab('AdminCustomPayments');

        return static::rrmdir(_PS_IMG_DIR_.'pay') && parent::uninstall();
    }

    /**
     * Remove this module's tab
     *
     * @param string $tabClass
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    private function uninstallModuleTab($tabClass)
    {
        $idTab = Tab::getIdFromClassName($tabClass);
        if ($idTab != 0) {
            $tab = new Tab($idTab);
            $tab->delete();

            return true;
        }

        return false;
    }

    /**
     * Recursively remove directory
     *
     * @param string $dir Dir path
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($dir.'/'.$object) == 'dir') {
                        static::rrmdir($dir.'/'.$object);
                    } else {
                        unlink($dir.'/'.$object);
                    }
                }

            }
            reset($objects);
            rmdir($dir);
        }

        return true;
    }

    /**
     * Hook to displayPaymentReturn
     *
     * @param array $params Hook params
     *
     * @return string
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function hookdisplayPaymentReturn($params)
    {
        /** @var Order $order */
        $order = $params['objOrder'];

        $customPaymentMethod = new CustomPaymentMethod(
            (int) Tools::getValue(CustomPaymentMethod::$definition['primary']),
            $this->context->cookie->id_lang
        );
        $descriptionSuccess = str_replace(
            ['%total%', '%order_number%', '%order_id%'],
            [
                Tools::DisplayPrice($order->total_paid),
                Tools::safeOutput($order->reference),
                (int) $order->id,
            ],
            $customPaymentMethod->description_success
        );

        return '<div class="box">'.$descriptionSuccess.'</div>';
    }

    /**
     * Subscribe to Carrier object updates
     *
     * @param array $params
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function hookactionCarrierUpdate($params)
    {
        CustomPaymentMethod::updateCarrier($params['id_carrier'], $params['carrier']->id);
    }

    /**
     * Show on customer portal - order detail page
     *
     * @param array $params
     *
     * @return bool|mixed
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function hookdisplayOrderDetail($params)
    {
        /** @var Order $order */
        $order = $params['order'];
        if ($order->module != $this->name) {
            return false;
        }

        if (!($idCustomPaymentMethod = CustomPaymentMethod::getIdByName($params['order']->payment))) {
            return false;
        }

        $customPaymentMethod = new CustomPaymentMethod($idCustomPaymentMethod, $this->context->cookie->id_lang);

        return str_replace(
            ['%total%', '%order_number%'],
            [Tools::DisplayPrice($params['order']->total_paid), '#'.$params['order']->reference],
            $customPaymentMethod->description_success
        );
    }

    /**
     * @param array $params
     *
     * @return string|null
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayPayment($params)
    {
        /** @var Cart $cart */
        $cart = $params['cart'];

        if (!$this->active) {
            return null;
        }
        if (!$this->checkCurrency($cart)) {
            return null;
        }

        $virtual = $this->context->cart->isVirtualCart();
        $customPaymentMethods = $this->getCustomPaymentMethods($params);
        foreach ($customPaymentMethods as $key => &$paymentMethod) {
            if (($paymentMethod['cart_type'] == CustomPaymentMethod::CART_REAL) && $virtual) {
                unset($customPaymentMethods[$key]);
            } elseif (($paymentMethod['cart_type'] == CustomPaymentMethod::CART_VIRTUAL) && !$virtual) {
                unset($customPaymentMethods[$key]);
            }
            $paymentMethod['logo'] =
                Media::getMediaPath(CustomPaymentMethod::getImagePath($paymentMethod['id_custom_payment_method']));
        }
        $this->smarty->assign(
            [
                'custompayments'         => $customPaymentMethods,
                'custompayments_onepage' => Configuration::get(static::CONFIRMATION_BUTTON),
            ]
        );

        return $this->display(__FILE__, 'payment.tpl');
    }

    /**
     * Hook to Advanced EU checkout
     *
     * @param array $params Hook parameters
     *
     * @return array|bool Smarty variables, nothing if should not be shown
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookDisplayPaymentEU($params)
    {
        /** @var Cart $cart */
        $cart = $params['cart'];

        if (!$this->active) {
            return null;
        }
        if (!$this->checkCurrency($cart)) {
            return null;
        }

        $virtual = $this->context->cart->isVirtualCart();
        $customPaymentMethods = $this->getCustomPaymentMethods($params);
        $paymentOptions = [];
        foreach ($customPaymentMethods as $key => &$paymentMethod) {
            if (($paymentMethod['cart_type'] == CustomPaymentMethod::CART_REAL) && $virtual) {
                continue;
            } elseif (($paymentMethod['cart_type'] == CustomPaymentMethod::CART_VIRTUAL) && !$virtual) {
                continue;
            }
            $paymentOptions[] = [
                'cta_text' => $paymentMethod['name'],
                'logo'     => Media::getMediaPath(
                    CustomPaymentMethod::getImagePath($paymentMethod['id_custom_payment_method'])
                ),
                'action'   => $this->context->link->getModuleLink(
                    'custompayments',
                    'payment',
                    ['id_custom_payment_method' => $paymentMethod['id_custom_payment_method']],
                    true
                ),
            ];
        }

        return $paymentOptions;
    }

    /**
     * @param Cart $cart
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function checkCurrency(Cart $cart)
    {
        $orderCurrency = new Currency($cart->id_currency);
        $moduleCurrrencies = $this->getCurrency((int) $cart->id_currency);

        if (is_array($moduleCurrrencies)) {
            foreach ($moduleCurrrencies as $moduleCurrency) {
                if ($orderCurrency->id == $moduleCurrency['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array $params
     *
     * @return array|bool|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function getCustomPaymentMethods($params)
    {
        /** @var Cart $cart */
        $cart = $params['cart'];

        $paymentMethods = CustomPaymentMethod::getCustomPaymentMethods(
            $this->context->language->id,
            true,
            $cart->id_carrier,
            $this->context->customer->getGroups()
        );

        foreach ($paymentMethods as &$paymentMethod) {
            $paymentMethod['description'] = str_replace(
                ['%total%'],
                [Tools::DisplayPrice($cart->getOrderTotal(true, Cart::BOTH))],
                $paymentMethod['description']
            );
        }
        $this->paymentMethods = $paymentMethods;

        return $paymentMethods;
    }

    /**
     * @return string
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function getContent()
    {
        $this->postProcess();

        return $this->display(__FILE__, 'views/templates/admin/configure.tpl').$this->renderSettingsForm();
    }

    /**
     * @since 1.0.0
     * @throws PrestaShopException
     */
    protected function postProcess()
    {
        if (Tools::isSubmit('submitSave')) {
            Configuration::updateValue(
                static::CONFIRMATION_BUTTON,
                (bool) Tools::getValue(static::CONFIRMATION_BUTTON)
            );
            $height = (int) Tools::getValue(static::IMAGE_HEIGHT);
            $width = (int) Tools::getValue(static::IMAGE_WIDTH);

            if ($height * $width <= 0) {
                $this->context->controller->errors[] = $this->l('Logo width or height is incorrect');
            } else {
                Configuration::updateValue(static::IMAGE_HEIGHT, $height);
                Configuration::updateValue(static::IMAGE_WIDTH, $width);

                $this->context->controller->confirmations[] = $this->l('Settings successfully updated');
            }
        }
    }

    /**
     * @return string
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function renderSettingsForm()
    {
        $fieldsForm = [
            'form' => [
                'legend'      => [
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ],
                'input'       => [
                    [
                        'type'    => 'switch',
                        'label'   => $this->l('Confirmation button'),
                        'hint'    => $this->l('Show a confirmation button directly on the checkout page'),
                        'name'    => static::CONFIRMATION_BUTTON,
                        'is_bool' => true,
                        'values'  => [
                            [
                                'id'    => static::CONFIRMATION_BUTTON.'_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id'    => static::CONFIRMATION_BUTTON.'_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Logo width'),
                        'name'  => static::IMAGE_WIDTH,
                        'class' => 'fixed-width-xs',
                        'desc'  => $this->l('Defines the width of payment logos.'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Logo height'),
                        'name'  => static::IMAGE_HEIGHT,
                        'class' => 'fixed-width-xs',
                        'desc'  => $this->l('Defines the height of payment logos.'),
                    ],
                ],
                'submit'      => [
                    'name'  => 'submitSave',
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            : 0;
        $this->fields_form = [];
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSave';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$fieldsForm]);

    }

    /**
     * @return array
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function getConfigFieldsValues()
    {
        return [
            static::CONFIRMATION_BUTTON => Configuration::get(static::CONFIRMATION_BUTTON),
            static::IMAGE_WIDTH => Configuration::get(static::IMAGE_WIDTH),
            static::IMAGE_HEIGHT => Configuration::get(static::IMAGE_HEIGHT),
        ];
    }
}
