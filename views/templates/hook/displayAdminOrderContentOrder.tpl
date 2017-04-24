{*
 *
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
 * @author    0RS <admin@prestalab.ru>
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2009-2017 PrestaLab.Ru
 * @copyright 2017 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 * This module is based on the original `universalpay` module
 * which you can find on https://github.com/universalpay/universalpay
 *
 * Credits go to PrestaLab.Ru (http://www.prestalab.ru) for making the initial version
 *
*}
<div class="tab-pane" id="up_fields">
    <div class="table-responsive">
        <table class="table" id="up_fields">
            <thead>
            <tr>
                <th>
                    <span class="title_box">{l s='Name' mod='custompayments'}</span>
                </th>
                <th>
                    <span class="title_box ">{l s='Value' mod='custompayments'}</span>
                </th>
            </tr>
            </thead>
            <tbody>
            {foreach $up_fields as $up_field}
                <tr>
                    <td>{$up_field@key|escape:'html':'UTF-8'}</td>
                    <td>{$up_field|escape:'html':'UTF-8'}</td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
</div>
