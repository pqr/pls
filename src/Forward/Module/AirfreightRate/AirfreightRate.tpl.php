<?php use Light\Template as t; ?>
<table>
    <tr>
        <td class="col-left">
            <h2>General Info</h2>
            <table class="form">
                <?= t::c('VendorID'); ?>
                <?php include LIGHT_DIR_PROJECT .'/templates/AirlineAlphaCode.tpl.php'; ?>
                <?php include LIGHT_DIR_PROJECT . '/templates/DepartureIataLocation.tpl.php' ?>
                <?php include LIGHT_DIR_PROJECT . '/templates/ArrivalIataLocation.tpl.php' ?>
                <?= t::c('TransitTime') ?>
                <?= t::c('Name') ?>
                <?= t::datesFromUntil('Valid') ?>
                <?= t::c('Comments') ?>
            </table>
        </td>
        <td class="col-right">
            <h2>Amounts</h2>
            <table class="form">
                <?= t::c('MinAmount'); ?>
                <?= t::c('AmountN'); ?>
                <?= t::c('Amount45'); ?>
                <?= t::c('Amount100'); ?>
                <?= t::c('Amount200'); ?>
                <?= t::c('Amount300'); ?>
                <?= t::c('Amount500'); ?>
                <?= t::c('Amount750'); ?>
                <?= t::c('Amount1000'); ?>
                <?= t::c('Amount2000'); ?>
                <?= t::c('FSCAmount'); ?>
                <?= t::c('SSCAmount'); ?>
                <?= t::c('CurrencyISO'); ?>
            </table>
        </td>
    </tr>
</table>

