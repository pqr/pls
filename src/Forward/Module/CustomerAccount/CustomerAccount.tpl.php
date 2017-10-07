<?php use Light\Template as t; ?>
<table>
    <tr>
        <td class="col-left">
            <table class="form">
                <?= t::c('Name') ?>
                <?= t::c('Active') ?>
                <?= t::c('CompanyID') ?>
                <?= t::c('ContractNo') ?>
                <?= t::c('InvoiceAddress') ?>
                <?= t::c('Comments') ?>
                <?= t::c('RatePercent') ?>
                <?= t::c('BUIDs') ?>
                <tr class="vtop">
                    <th style="padding-top: 20px">
                        Customers
                    </th>
                    <td style="padding-top: 20px; background-color: #fff;">
                        <?= t::subtableHtml('Customers') ?>
                    </td>
                </tr>
            </table>
        </td>
        <td class="col-middle">
            <h2>Для заявки в графу "Плательщик"</h2>
            <table class="form">
                <?= t::c('ShipmentRequestZIP') ?>
                <?= t::c('ShipmentRequestStreet') ?>
                <?= t::c('ShipmentRequestCity') ?>
                <?= t::c('ShipmentRequestCountry') ?>
                <?= t::c('ShipmentRequestPhone') ?>
            </table>
        </td>
    </tr>
</table>