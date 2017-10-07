<?php use Light\Template as t; ?>
<table>
    <tr>
        <td>
            <table class="filters">
                <?= t::c('CompanyID') ?>
                <?= t::c('VendorID') ?>
                <?= t::c('VendorAccountID') ?>
                <?= t::c('FilterName') ?>
                <?= t::filtersButtonsRow() ?>
            </table>
        </td>
        <td>
            <table class="filters">
                <?= t::datesFromUntil('PaymentDate') ?>
            </table>
        </td>
    </tr>
</table>