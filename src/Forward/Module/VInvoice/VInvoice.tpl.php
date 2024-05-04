<?php

use Light\ModuleBase\ModuleAbstract;
use Light\Template as t;

$csLineIds = false;

if (isset($_GET['fromCostSheet'])) {

    //Переход на режим Add New состоялся из Cost-sheet'а где несколько строк были выделены галочками
    $csLineIds = explode(',', (string) $_GET['csLineIds']);
    $csLineIds = array_filter($csLineIds);

}

//Если в итоге удалось сформировать массив $csLineIds, то запишем его в скрытое поле
if ($csLineIds) {
    print '<input type="hidden" name="csLineIds" value="' . htmlspecialchars(implode(',', $csLineIds)) . '">';
}
?>

<table>
    <tr>
        <td>
            <table>
                <tr>
                    <td>
                        <h2>General Info</h2>
                        <table class="form">
                            <?= t::c('CompanyID') ?>
                            <?= t::c('VendorID') ?>
                            <?= t::c('VendorAccountID') ?>
                            <?= t::h3('Name') ?>
                            <?= t::c('Comments') ?>
                            <?= t::c('DocType') ?>

                            <tr>
                                <th>Amount</th>
                                <td><span id="amount-span"><?= t::html('Amount') ?></span> <?= t::html('CurrencyISO') ?></td>
                            </tr>
                        </table>

                        <h2>Status</h2>
                        <table class="form">

                            <tr<?= t::title('Status') ?>>
                                <th>
                                    <?= t::label('Status') ?>
                                </th>
                                <td>
                                    <?php if (t::_state() == ModuleAbstract::STATE_VIEW) {
                                        print t::html('StatusIco');
                                    } ?>
                                    <?= t::html('Status') ?>
                                    <?php if (t::value('NotOnCheckingDate') != '' && t::value('NotOnCheckingDate') != '0000-00-00'): ?>
                                        <span class="small nobr">Status changed from On Checking: <?= t::html('NotOnCheckingDate') ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <?php if (t::value('CachePaymentsTotalAmount') != 0): ?>
                                <tr<?= t::title('CachePaymentsTotalAmount') ?>>
                                    <th>
                                        <?= t::label('CachePaymentsTotalAmount') ?>
                                    </th>
                                    <td>
                                        <?= t::html('CachePaymentsTotalAmount') ?> <?= t::value('CurrencyISO') ?>
                                    </td>
                                </tr>
                            <?php endif ?>

                            <?php if (t::value('CacheOutstandingAmount') != 0): ?>
                                <tr<?= t::title('CacheOutstandingAmount') ?>>
                                    <th>
                                        <?= t::label('CacheOutstandingAmount') ?>
                                    </th>
                                    <td>
                                        <?= t::html('CacheOutstandingAmount') ?> <?= t::value('CurrencyISO') ?>
                                    </td>
                                </tr>
                            <?php endif ?>

                            <tr>
                                <th></th>
                                <td style="background-color: #ffffff"><?= t::subtableHtml('Responsibles') ?></td>
                            </tr>
                        </table>
                    </td>

                    <td class="col-right">
                        <h2>Dates</h2>
                        <table class="form">
                            <?= t::c('ReceivedDate') ?>
                            <?= t::c('DocDate') ?>
                            <?= t::c('DueDate') ?>
                        </table>

                        <h2>Documents</h2>
                        <table id="docs-table" class="form">
                            <tr<?= t::title('CopyRequestReceived') ?>>
                                <th>
                                </th>
                                <th style="text-align: center;">Copy</th>
                                <th style="text-align: center;">Original</th>
                            </tr>

                            <tr<?= t::title('CopyRequestReceived') ?>>
                                <th>Заявка</th>
                                <td><?= t::html('CopyRequestReceived') ?></td>
                                <td><?= t::html('OriginalRequestReceived') ?></td>
                            </tr>

                            <tr<?= t::title('CopyInvoiceReceived') ?>>
                                <th>Счёт</th>
                                <td><?= t::html('CopyInvoiceReceived') ?></td>
                                <td><?= t::html('OriginalInvoiceReceived') ?></td>
                            </tr>

                            <tr<?= t::title('CopyActReceived') ?>>
                                <th>Акт</th>
                                <td><?= t::html('CopyActReceived') ?></td>
                                <td><?= t::html('OriginalActReceived') ?></td>
                            </tr>

                            <tr<?= t::title('CopyFakturaReceived') ?>>
                                <th>Счёт-Фактура</th>
                                <td><?= t::html('CopyFakturaReceived') ?></td>
                                <td><?= t::html('OriginalFakturaReceived') ?></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <?= t::subtable('Lines') ?>

            <?php if (t::_state() !== ModuleAbstract::STATE_ADDNEW): ?>
            <?= t::subtable('VPayments') ?>
            <?php endif; ?>

        </td>
    </tr>
</table>
