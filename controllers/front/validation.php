<?php
/**
 * Copyright (C) 2017 thirty bees
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
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2017 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $customPaymentMethod = new CustomPaymentMethod((int) Tools::getValue('id_custom_payment_method'), $this->context->cookie->id_lang);
        if (!Validate::isLoadedObject($customPaymentMethod)) {
            return;
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

    /**
     * Set template
     *
     * @param string $defaultTemplate
     *
     * @since 1.0.0
     */
    public function setTemplate($defaultTemplate)
    {
        if ($this->context->getMobileDevice() != false) {
            $this->setMobileTemplate($defaultTemplate);
        } else {
            $template = $this->getOverrideTemplate();
            if ($template) {
                $this->template = $template;
            } else {
                $this->template = $defaultTemplate;
            }
        }
    }
}
