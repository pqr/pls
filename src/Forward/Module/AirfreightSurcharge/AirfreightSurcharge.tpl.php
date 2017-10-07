<?php use Light\Template as t; ?>
<table>
    <tr>
        <td class="col-left">
            <h2>General Info</h2>
            <table class="form">
                <?= t::h3('Name') ?>
                <?= t::c('VendorID'); ?>
                <?php include LIGHT_DIR_PROJECT . '/templates/AirlineAlphaCode.tpl.php'; ?>
                <tr<?= t::title('LocationName') ?>>
                    <th><?= t::label('LocationName') ?></th>
                    <td><span title="IATA"><?= t::html('IATA') ?></span> <span
                            title="Location"><?= t::html('LocationName') ?></span></td>
                </tr>
                <?php include LIGHT_DIR_PROJECT . '/templates/Incoterms.tpl.php'; ?>
                <?= t::datesFromUntil('Valid') ?>
                <?= t::c('Comments') ?>
            </table>
        </td>
        <td class="col-right">
            <h2>Amounts</h2>
            <table class="form">
                <?= t::c('BaseAmount'); ?>
                <?= t::c('MinAmount'); ?>
                <?= t::c('MaxAmount'); ?>
                <?= t::c('PerKgAmount'); ?>
                <?= t::c('CurrencyISO'); ?>
            </table>
        </td>
    </tr>
</table>

