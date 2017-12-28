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
 * Class CustompaymentspaymentModuleFrontController
 *
 * @since 1.0.0
 */
class CustompaymentspaymentModuleFrontController extends ModuleFrontController
{
    // @codingStandardsIgnoreStart
    /** @var bool $display_column_left */
    public $display_column_left = false;
    /** @var bool $display_column_right */
    public $display_column_right = false;
    /** @var bool $ssl */
    public $ssl = true;
    /** @var CustomPayments $module */
    public $module;
    // @codingStandardsIgnoreEnd

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;

        $customPaymentMethod = new CustomPaymentMethod((int) Tools::getValue('id_custom_payment_method'), $this->context->cookie->id_lang);
        $customPaymentMethods = $this->module->getCustomPaymentMethods(['cart' => $cart]);
        $paymentMethodAvailable = in_array($customPaymentMethod->id, array_column($customPaymentMethods, 'id_custom_payment_method'));

        if (!Validate::isLoadedObject($customPaymentMethod)) {
            return;
        }

        $total = $cart->getOrderTotal(true, Cart::BOTH);
        $customPaymentMethod->description = str_replace(
            ['%total%'],
            [Tools::DisplayPrice($total)],
            $customPaymentMethod->description
        );

        $this->context->smarty->assign(
            [
                'nbProducts'             => $cart->nbProducts(),
                'customPaymentMethod'    => $customPaymentMethod,
                'this_path'              => $this->module->getPathUri(),
                'this_path_ssl'          => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/',
                'paymentMethodAvailable' => $paymentMethodAvailable,
            ]
        );

        $this->setTemplate('payment_execution.tpl');
    }
}
