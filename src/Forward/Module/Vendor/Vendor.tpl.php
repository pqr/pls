<?php use Light\Template as t; ?>
<table>
    <tr>
        <td>
            <table>
                <tr>
                    <td class="col-left">
                        <h2>General Info</h2>
                        <table class="form">
                            <?= t::c('Name') ?>
                            <?= t::c('Active') ?>
                            <tr>
                                <th>
                                    Type
                                </th>
                                <td>
                                    <?= t::html('isAirline') ?> <?= t::label('isAirline') ?><br>
                                    <?= t::html('isCarrier') ?> <?= t::label('isCarrier') ?><br>
                                    <?= t::html('isPortExpeditor') ?> <?= t::label('isPortExpeditor') ?><br>
                                    <?= t::html('isTrucker') ?> <?= t::label('isTrucker') ?><br>
                                </td>
                            </tr>
                            <?= t::c('Address') ?>
                            <?= t::c('Contacts') ?>
                            <?= t::c('Comments') ?>
                            <tr class="vtop">
                                <th style="padding-top: 20px">
                                    Vendor Accounts
                                </th>
                                <td style="padding-top: 20px; background-color: #fff;">
                                    <?= t::subtableHtml('VendorAccounts') ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="col-middle">
                        <h2>Responsibles</h2>
                        <?= t::subtableHtml('Responsibles') ?>

                        <h2>Internal Info</h2>
                        <table class="form">
                            <?= t::c('CompanyID') ?>
                            <?= t::c('CreditPeriod') ?>
                            <?= t::amountWithCurrency('CreditAmount') ?>
                        </table>
                    </td>
                </tr>
            </table>

            <?= t::subtable('Contacts') ?>

        </td>
    </tr>
</table>
