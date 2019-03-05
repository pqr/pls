<?php
use Forward\Classes\Constant;
use Light\ModuleBase\ModuleAbstract;
use Light\Template as t;

$csLineIds = false;

if (isset($_GET['fromCostSheet'])) {

    //Переход на режим Add New состоялся из Cost-sheet'а где несколько строк были выделены галочками
    $csLineIds = explode(',', $_GET['csLineIds']);
    $csLineIds = array_filter($csLineIds);

} elseif (isset($_GET['fromShipmentsMassaction'])) {

    //Переход на режим Add New cостоялся из Shipments Massaction "Создать Customer Invoice"
    //В этом случае в POST лежат id строк из кост-шитов на основе которых делаем счёт
    /** @var array $csLineIdsPerShipment */
    $csLineIdsPerShipment = $_POST['csLineIdsPerShipment'];

    $shipmentIds = [];
    $csLineIds = [];
    foreach ($csLineIdsPerShipment as $shipmentId => $csLineIdsForShipment) {
        // Добавим в массив $csLineIds только не нулевые галочки
        /** @noinspection SlowArrayOperationsInLoopInspection */
        $csLineIds = array_merge($csLineIds, array_filter($csLineIdsForShipment));
    }

}

//Если в итоге удалось сформировать массив $csLineIds, то запишем его в скрытое поле
if ($csLineIds) {
    print '<input type="hidden" name="csLineIds" value="' . htmlspecialchars(implode(',', $csLineIds)) . '">';
}
?>

    <table>
        <tr>
            <td>
                <h2>General Info</h2>
                <table class="form">
                    <?= t::c('CompanyID') ?>
                    <?php if (t::_state() !== 'view' || !Constant::isOneCCompany(t::value('CompanyID'))): ?>
                        <tr class="only-not-ooo">
                            <th><?= t::label('BankID') ?></th>
                            <td><?= t::html('BankID') ?></td>
                        </tr>
                    <?php endif; ?>
                    <?= t::c('CustomerID') ?>
                    <?= t::c('CustomerAccountID') ?>
                    <?= t::c('Comments') ?>

                    <?= t::c('DocType') ?>

                    <tr>
                        <th>
                            <?= t::label('Amount') ?>
                        </th>
                        <td style="font-weight: bold;">
                            <span id="cinvoice-amount"><?= t::html('Amount') ?></span> <?= t::html('CurrencyISO') ?>
                            <?php if (t::_state() !== 'view' || Constant::isOneCCompany(t::value('CompanyID'))): ?>
                                <span class="only-ooo"
                                      style="float: right"><?= t::label('RatePercent') ?> <?= t::html('RatePercent') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <?php if (t::_state() !== 'view' || Constant::isOneCCompany(t::value('CompanyID'))): ?>
                        <tr class="only-ooo">
                            <th><?= t::label('PaymentCurrencyISO') ?></th>
                            <td><?= t::html('PaymentCurrencyISO') ?></td>
                        </tr>
                    <?php endif; ?>

                    <?php if (t::_state() !== 'view'): ?>
                        <tr id="converttorub-tr">
                            <th></th>
                            <td style="background-color: white">
                                <small><a href="#" class="inner">Convert to RUB</a></small>
                            </td>
                        </tr>
                    <?php endif; ?>

                </table>

                <h2>Status</h2>

                <table class="form">
                    <tr>
                        <th>
                            <?= t::label('Status') ?>
                        </th>
                        <td>
                            <?php if (t::_state() === 'view') {
                                print t::html('StatusIco');
                            } ?> <?= t::html('Status') ?>
                            <span<?= t::title('CacheLastPaymentDate') ?>><?= t::html('CacheLastPaymentDate') ?></span>
                        </td>
                    </tr>

                    <?php if (t::value('CachePaymentsTotalAmount') != 0): ?>
                        <tr<?= t::title('CachePaymentsTotalAmount') ?>>
                            <th>
                                <?= t::label('CachePaymentsTotalAmount') ?>
                            </th>
                            <td>
                                <?= t::value('CachePaymentsTotalAmount') ?> <?= t::value('CurrencyISO') ?>
                                <?php if (
                                    t::issetvar('LastPaymentCurrRate') && t::variable('LastPaymentCurrRate') != 1
                                ) {
                                    print ' <span title="Курс валюты последней оплаты (если оплата была не в валюте счёта)">(Currency rate: '
                                        . t::variable('LastPaymentCurrRate')
                                        . ')</span>';

                                } ?>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php if (t::value('CacheOutstandingAmount') != 0): ?>
                        <tr<?= t::title('CacheOutstandingAmount') ?>>
                            <th>
                                <?= t::label('CacheOutstandingAmount') ?>
                            </th>
                            <td>
                                <?= t::html('CacheOutstandingAmount') ?> <?= t::value('CurrencyISO') ?>
                            </td>
                        </tr>
                    <?php endif; ?>

                </table>
            </td>
            <td class="col-right">
                <h2>Documents</h2>
                <table class="form" id="docs-table">
                    <tr>
                        <th>
                        </th>
                        <th style="text-align: center;">
                            Copy
                        </th>
                        <th style="text-align: center;">
                            Original
                        </th>
                    </tr>

                    <tr<?= t::title('CopyRequestReceived') ?>>
                        <th>
                            Заявка
                        </th>
                        <td>
                            <?= t::html('CopyRequestReceived') ?>
                        </td>
                        <td>
                            <?= t::html('OriginalRequestReceived') ?>
                        </td>
                    </tr>

                    <tr<?= t::title('CopyInvoiceReceived') ?>>
                        <th>
                            Счёт
                        </th>
                        <td>
                            <?= t::html('CopyInvoiceReceived') ?>
                        </td>
                        <td>
                            <?= t::html('OriginalInvoiceReceived') ?>
                        </td>
                    </tr>

                    <tr<?= t::title('CopyActReceived') ?>>
                        <th>
                            Акт
                        </th>
                        <td>
                            <?= t::html('CopyActReceived') ?>
                        </td>
                        <td>
                            <?= t::html('OriginalActReceived') ?>
                        </td>
                    </tr>

                    <tr<?= t::title('CopyFakturaReceived') ?>>
                        <th>
                            Счёт-Фактура
                        </th>
                        <td>
                            <?= t::html('CopyFakturaReceived') ?>
                        </td>
                        <td>
                            <?= t::html('OriginalFakturaReceived') ?>
                        </td>
                    </tr>
                </table>

                <table class="form">
                    <?= t::c('CActName') ?>
                    <?= t::c('CActDate') ?>
                    <?= t::c('CActAmount') ?>
                </table>

            </td>
        </tr>
    </table>

    <div style="width: 210mm; border: 1px solid silver; padding: 10px; margin-top: 10px">


        <div style="text-align: right;" id="invoicedate">Date: <?= t::html('DocDate') ?></div>
        <div style="text-align: right;" id="invoicename"><?= strtoupper(t::text('DocType')) ?> # <?= t::html('Name') ?></div>
        <div style="text-align: right; padding-right: 200px;" id="invoiceto1">To</div>
        <div style="text-align: right; margin-bottom: 15px;" id="invoiceto2"><?= t::html('InvoiceTo') ?></div>

        <table id="headfields-table">
            <tr>
                <td colspan="2">Shipping Document #</td>
                <td>Shipment Reference</td>
                <td>Payment Terms</td>
                <td>Due Date</td>
            </tr>
            <tr>
                <td colspan="2" class="bordered"><?= t::html('ShippingDocument') ?></td>
                <td class="bordered"><?= t::html('ShipmentReference') ?></td>
                <td class="bordered"><?= t::html('PaymentTerms') ?></td>
                <td class="bordered"><?= t::html('DueDate') ?>&nbsp;</td>
            </tr>
            <tr>
                <td>Pieces</td>
                <td>Gross Weight</td>
                <td>Chargeable Weight</td>
                <td colspan="2">Incoterms</td>
            </tr>
            <tr>
                <td class="bordered"><?= t::html('Pieces') ?></td>
                <td class="bordered"><?= t::html('GrossWeight') ?></td>
                <td class="bordered"><?= t::html('ChargeableWeight') ?></td>
                <td colspan="2"
                    class="bordered"><?= t::html('Incoterms') ?> <?= t::html('IncotermsLocation') ?></td>
            </tr>
            <tr>
                <td colspan="2">Departure</td>
                <td>Destination</td>
                <td colspan="2">Consignee Reference</td>
            </tr>
            <tr>
                <td colspan="2" class="bordered"><?= t::html('DepartureLocationName') ?>&nbsp;</td>
                <td class="bordered"><?= t::html('ArrivalLocationName') ?>&nbsp;</td>
                <td colspan="2" class="bordered"><?= t::html('ConsigneeReference') ?>&nbsp;</td>
            </tr>
        </table>

        <?= t::subtable('Lines') ?>

    </div>

<?php if (t::_state() !== ModuleAbstract::STATE_ADDNEW): ?>
    <?= t::subtable('CPayments') ?>
<?php endif; ?>
