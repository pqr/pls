<?php use Light\Template as t; ?>
<table>
    <tr>
        <td class="col-left">
            <table class="form">

                <tr>
                    <th>
                        <?= t::label('Name') ?>
                    </th>
                    <td class="color-container<?php if (t::value('Color')) {
                        print ' color' . t::value('Color');
                    } ?>">
                        <h3 style="float: left;"><?= t::html('Name') ?></h3>
                        <div style="float: left; margin-left: 100px"><?= t::html('Color') ?></div>
                        <h3 style="float: right;"><?= t::html('FileClosed') ?></h3>
                    </td>
                </tr>

                <tr>
                    <th>
                        <?= t::label('MAWBNumber') ?>
                    </th>
                    <td>
                        <h3><?= t::html('MAWBNumber') ?> <?= t::html('Trace') ?></h3>
                    </td>
                </tr>
                <tr>
                    <th><?= t::label('CustomerID') ?></th>
                    <td><?= t::html('CustomerID') ?>
                        <?php if (t::variable('isPrepaidCustomer')): ?><div class="bad">Attention: Prepaid Customer</div><?php endif; ?>
                    </td>
                </tr>
                <?= t::c('CustomerAccountID') ?>
                <?= t::c('Comments') ?>
                <?= t::c('CActs') ?>
            </table>
        </td>
        <td class="col-right">
            <table class="form">
                <?= t::c('Pieces') ?>
                <?= t::c('GrossWeight') ?>
                <?= t::c('ChargeableWeight') ?>
                <?= t::c('Volume') ?>
                <tr<?= t::title('QuotationID')?>>
                    <th><?= t::label('QuotationID') ?></th>
                    <td id="QuotationID-td"><?= t::html('QuotationID') ?></td>
                </tr>
            </table>
        </td>
        <td class="col-right">
            <table class="form">
                <?= t::c('ETD') ?>
                <?= t::c('ATD') ?>
                <?= t::c('ETA') ?>
                <?= t::c('ATA') ?>
            </table>
        </td>
    </tr>
</table>