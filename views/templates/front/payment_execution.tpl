{*
 *
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
 *
*}

{capture name=path}{$customPaymentMethod->name|escape:'htmlall':'UTF-8'}{/capture}

<h1 class="page-heading">
    {l s='Order summary' mod='custompayments'}
</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
    <p class="alert alert-warning">
        {l s='Your shopping cart is empty.' mod='custompayments'}
    </p>
{elseif isset($paymentMethodAvailable) && !$paymentMethodAvailable}
    <p class="alert alert-error">
        {l s='The selected payment method is not available. Please try a different payment method.' mod='custompayments'}
    </p>
{else}
    <form action="{$link->getModuleLink('custompayments', 'validation', [], true)|escape:'html':'UTF-8'}" method="post">
        <div class="box cheque-box">
            <h3 class="page-subheading">
                {$customPaymentMethod->name|escape:'html':'UTF-8'}
            </h3>
            <p class="cheque-indent">
                {$customPaymentMethod->description}
            </p>
            <p>
                <b>{l s='Please confirm your order by clicking "I confirm my order"' mod='custompayments'}.</b>
            </p>
        </div>
        <p class="cart_navigation clearfix" id="cart_navigation">
            <a class="button-exclusive btn btn-default"
               href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
                <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='custompayments'}
            </a>
            <input type="hidden" name="id_custom_payment_method" value="{$customPaymentMethod->id|intval}"/>
            <button class="button btn btn-default button-medium" type="submit">
                <span>{l s='I confirm my order' mod='custompayments'}<i class="icon-chevron-right right"></i></span>
            </button>
        </p>
    </form>
{/if}
