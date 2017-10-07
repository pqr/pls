<?php use Light\Template as t; ?>
<table>
    <tr>
        <td>
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
    </tr>
</table>