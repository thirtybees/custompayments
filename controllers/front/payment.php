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
 * Class CustompaymentspaymentModuleFrontController
 *
 * @since 1.0.0
 */
class CustompaymentspaymentModuleFrontController extends ModuleFrontController
{
    // @codingStandardsIgnoreStart
    public $display_column_left = false;
    public $display_column_right = false;
    public $ssl = true;
    // @codingStandardsIgnoreEnd

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;

        $customPaymentMethod = new CustomPaymentMethod((int) Tools::getValue('id_custom_payment_method'), $this->context->cookie->id_lang);

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
                'nbProducts'          => $cart->nbProducts(),
                'customPaymentMethod' => $customPaymentMethod,
                'this_path'           => $this->module->getPathUri(),
                'this_path_ssl'       => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/',
            ]
        );

        $this->setTemplate('payment_execution.tpl');
    }
}
