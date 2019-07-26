<?php use Light\Template as t; ?>
<table>
    <tr>
        <td class="col-left">
            <table class="form">
                <?= t::c('Name') ?>
                <?= t::c('CompanyID') ?>
                <?= t::c('OneCName') ?>
                <?= t::c('Active') ?>
                <?= t::c('InvoiceAddress') ?>
                <?= t::c('Comments') ?>
                <tr class="vtop">
                    <th style="padding-top: 20px">
                        Vendors
                    </th>
                    <td style="padding-top: 20px; background-color: #fff;">
                        <?= t::subtableHtml('Vendors') ?>
                    </td>
                </tr>
            </table>
        </td>
        <td class="col-right">
            <table class="form">
                <?= t::c('NotifyAccountingByEmail') ?>
            </table>
        </td>
    </tr>
</table>
