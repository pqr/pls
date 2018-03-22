<?php use Light\Template as t; ?>
<table>
    <tr>
        <td>
            <table id="headfields-table">
                <tr>
                    <td colspan="2">Shipping Document #</td>
                    <td>Shipment Reference</td>
                    <td>Payment Terms</td>
                    <td>Due Date</td>
                </tr>
                <tr>
                    <td colspan="2" class="bordered"><?= t::text('ShippingDocument') ?></td>
                    <td class="bordered"><?= t::text('ShipmentReference') ?></td>
                    <td class="bordered"><?= t::text('PaymentTerms') ?></td>
                    <td class="bordered"><?= t::text('DueDate') ?>&nbsp;</td>
                </tr>
                <tr>
                    <td>Pieces</td>
                    <td>Gross Weight</td>
                    <td>Chargeable Weight</td>
                    <td colspan="2">Incoterms</td>
                </tr>
                <tr>
                    <td class="bordered"><?= t::text('Pieces') ?></td>
                    <td class="bordered"><?= t::text('GrossWeight') ?></td>
                    <td class="bordered"><?= t::text('ChargeableWeight') ?></td>
                    <td colspan="2" class="bordered"><?= t::text('Incoterms') ?> <?= t::text('IncotermsLocation') ?></td>
                </tr>
                <tr>
                    <td colspan="2">Departure</td>
                    <td>Destination</td>
                    <td colspan="2">Consignee Reference</td>
                </tr>
                <tr>
                    <td colspan="2" class="bordered"><?= t::text('DepartureLocationName') ?>&nbsp;</td>
                    <td class="bordered"><?= t::text('ArrivalLocationName') ?>&nbsp;</td>
                    <td colspan="2" class="bordered"><?= t::text('ConsigneeReference') ?>&nbsp;</td>
                </tr>
            </table>
            <p>&nbsp;</p>

            <div>
                <?= $LinesSubtable ?>
            </div>
        </td>
    </tr>
</table>
