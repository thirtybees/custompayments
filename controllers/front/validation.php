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
 * Class CustompaymentsvalidationModuleFrontController
 *
 * @since 1.0.0
 */
class CustompaymentsvalidationModuleFrontController extends ModuleFrontController
{
    // @codingStandardsIgnoreStart
    /** @var CustomPayments $module */
    public $module;
    // @codingStandardsIgnoreEnd

    /**
     * @since 1.0.0
     */
    public function postProcess()
    {
        $cart = $this->context->cart;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'custompayments') {
                $authorized = true;
                break;
            }
        }
        $customPaymentMethod = new CustomPaymentMethod((int) Tools::getValue('id_custom_payment_method'), $this->context->cookie->id_lang);
        if (!Validate::isLoadedObject($customPaymentMethod)) {
            $this->errors[] = $this->module->l('This payment method is not available.', 'validation');

            return;
        }

        $customPaymentMethods = $this->module->getCustomPaymentMethods(['cart' => $cart]);
        $paymentMethodAvailable = in_array($customPaymentMethod->id, array_column($customPaymentMethods, 'id_custom_payment_method'));

        if (!$authorized || !$paymentMethodAvailable) {
            $this->errors[] = $this->module->l('This payment method is not available.', 'validation');

            return;
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        if ($customPaymentMethod->id_cart_rule) {
            $cart->addCartRule($customPaymentMethod->id_cart_rule);
        }

        $currency = $this->context->currency;
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);

        $upFields = [];
        foreach ($_POST as $key => $val) {
            $keyParts = explode('_', $key);
            if ($keyParts[0] == 'up') {
                $upFields[$keyParts[1]] = $val;
            }
        }

        $mailVars = [
            '{custom_payment_method_name}' => $customPaymentMethod->name,
        ];

        foreach ($upFields as $key => $val) {
            $mailVars['{up_'.$key.'}'] = $val;
        }

        if ($customPaymentMethod->id_cart_rule) {
            $cart->addCartRule($customPaymentMethod->id_cart_rule);
        }

        $this->module->validateOrder((int) $cart->id, $customPaymentMethod->id_order_state, $total, $customPaymentMethod->name, null, $mailVars, (int) $currency->id, false, $customer->secure_key);

        Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int) $cart->id.'&id_module='.(int) $this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key.'&id_custom_payment_method='.$customPaymentMethod->id);
    }
}
