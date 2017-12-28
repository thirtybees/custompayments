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

<style>
    a.custompayments:after {
        content: "\f054";
        display: block;
        font-family: "FontAwesome";
        font-size: 25px;
        height: 22px;
        margin-top: -11px;
        position: absolute;
        right: 15px;
        top: 50%;
        width: 14px;
    }
</style>
{foreach from=$custompayments item=ps}
    <div class="row">
        <div class="col-xs-12">
            <p class="payment_module">
                <a style="background:url('{$ps.logo|escape:'html':'UTF-8'}') no-repeat scroll 15px 15px #FBFBFB"
                   class="custompayments"
                        {if $custompayments_onepage}
                            onclick='showForm({$ps.id_custom_payment_method|intval})' href='javascript:;'
                        {else}
                            href="{$link->getModuleLink('custompayments', 'payment', ['id_custom_payment_method'=>$ps.id_custom_payment_method], true)|escape:'html':'UTF-8'}"
                        {/if}
                   title="{$ps.name|escape:'html':'UTF-8'}">
                    {$ps.description_short|escape:'html':'UTF-8'}
                </a>
                {if $custompayments_onepage}
                <br/>
                <form action="{$link->getModuleLink('custompayments', 'validation', [], true)|escape:'html':'UTF-8'}"
                      method="post" id="custompayments_hidden{$ps.id_custom_payment_method|intval}" style="display:none;">
                    <div class="box cheque-box">
                        {$ps.description}
                    </div>
            <p>
                <b>{l s='Please confirm your order by clicking "I confirm my order"' mod='custompayments'}</b>
            </p>
            <p class="cart_navigation clearfix">
                <input type="hidden" name="id_custom_payment_method" value="{$ps.id_custom_payment_method|intval}"/>
                <button class="button btn btn-default button-medium" type="submit">
                    <span>{l s='I confirm my order' mod='custompayments'}<i class="icon-chevron-right right"></i></span>
                </button>
            </p>
            </form>
            {/if}
            </p>
        </div>
    </div>
{/foreach}
{if $custompayments_onepage}
    <script type="text/javascript">
        {literal}
        function showForm(a) {
            if ($('#custompayments_hidden' + a).is(':hidden'))
                $('#custompayments_hidden' + a).show();
            else
                $('#custompayments_hidden' + a).hide();
            return false;
        }
        {/literal}
    </script>
{/if}
