<?php use Light\Template as t; ?>
<table>
    <tr>
        <td>
            <table>
                <tr>
                    <td class="col-left">
                        <h2>General Info</h2>
                        <table class="form">
                            <?= t::h3('Name') ?>
                            <?= t::c('CompanyID') ?>
                            <?= t::c('CustomerID') ?>
                            <?= t::c('ResponsibleSalesID') ?>
                            <?= t::c('ResponsibleCSID') ?>
                            <?= t::c('NatureOfGoods') ?>
                            <?php include LIGHT_DIR_PROJECT . '/templates/DepartureIataLocation.tpl.php' ?>
                            <?php include LIGHT_DIR_PROJECT . '/templates/ArrivalIataLocation.tpl.php' ?>
                            <?= t::datesFromUntil('Valid') ?>
                            <?= t::c('CurrencyISO') ?>

                            <tr>
                                <th>
                                    <?= t::label('Status') ?>
                                </th>
                                <td>
                                    <?= t::html('Status') ?>
                                    <?php if (t::value('Status') == 'merged' || t::_state() != 'view'): ?>
                                        <span
                                                id="mergedid-span"<?= t::value('Status') != 'merged' ? ' class="display-none"' : '' ?>>by <?= t::html('MergedQuotationID') ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <tr id="comments-lost-tr" class="<?= t::value('Status') === 'lost' ? '' : 'display-none' ?>">
                                <th>
                                    <?= t::label('CommentsLost') ?>
                                </th>
                                <td>
                                    <?= t::html('CommentsLost') ?>
                                </td>
                            </tr>
                        </table>
                    </td>

                    <td class="col-middle">
                        <h2>Cargo</h2>

                        <table class="form">
                            <?= t::c('CargoUOM') ?>
                        </table>

                        <div style="float: right;">
                            <?= t::subtableHtml('Cargo') ?>
                        </div>
                        <div style="clear: both;"></div>

                        <table class="form" style="margin-top: 10px">
                            <?= t::c('GrossWeight') ?>
                            <?= t::c('ChargeableWeight') ?>
                            <tr>
                                <th>Density</th>
                                <td>
                                    <span
                                            id="DensityKgPerM3-span"><?= t::value('DensityKgPerM3') ?></span> <?= t::uom('DensityKgPerM3') ?>
                                    <small style="margin: 0 10px">or</small>
                                    <span
                                            id="DensityM3PerTon-span"><?= t::value('DensityM3PerTon') ?></span> <?= t::uom('DensityM3PerTon') ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="col-left">
                        <h2 class="selling">Selling
                            <small>
                                <a href="#" id="apply-addon"><span class="glyphicon glyphicon-plus"></span> Apply Addon</a>
                            </small>
                        </h2>
                        <?= t::subtableHtml('Income') ?>
                    </td>

                    <td class="col-middle">
                        <h2 class="buying">
                            Buying
                            <?php if (t::_state() !== 'view'): ?>
                                <small>
                                    <a href="#" id="copy-to-selling-link"><span
                                                class="glyphicon glyphicon-arrow-left"></span> Copy to Selling</a>
                                </small>
                            <?php endif; ?>
                        </h2>
                        <?= t::subtableHtml('Expenses') ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: center; font-weight: bold; padding-top: 10px;">
                        GP: <span id="gp-span"><?= t::html('CacheGP') ?></span> <span id="currency-span"><?=
                            t::value(
                                'CurrencyISO'
                            ) ?></span>
                    </td>
                </tr>
                <tr>
                    <td class="col-left">
                        <table class="form" style="margin-top: 10px">
                            <?= t::c('CommentsCustomer') ?>
                            <?= t::c('AirlineAlphaCode') ?>
                            <?php include LIGHT_DIR_PROJECT . '/templates/Incoterms.tpl.php'; ?>
                            <?= t::c('TransitTime') ?>
                        </table>
                    </td>

                    <td class="col-left" style="padding-left: 10px;">
                        <table class="form" style="margin-top: 10px">
                            <?= t::c('CommentsInternal') ?>
                            <?= t::c('VendorID') ?>
                        </table>
                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>
