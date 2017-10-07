<?php use Light\Template as t; ?>
<table>
    <tr>
        <td>
            <h2>File Info</h2>
            <table class="form">
                <?= t::mu('FileClosed') ?>
                <?= t::mu('CompanyID') ?>
                <?= t::mu('CustomerID') ?>
                <?= t::mu('CustomerAccountID') ?>
                <?= t::mu('ConsigneeID') ?>
                <?= t::mu('ResponsibleSales') ?>
                <?= t::mu('ResponsibleCS') ?>
            </table>

            <h2>Route</h2>
            <table class="form">
                <?= t::mu('PlaceOfReceipt') ?>
                <?= t::mu('Destination') ?>
            </table>
            <table class="form">
                <h2>Ocean Freight</h2>
                <?= t::mu('ContainerName') ?>
                <?= t::mu('ContainerType') ?>
                <?= t::mu('Vessel') ?>
                <?= t::mu('PODTerminalID') ?>
                <?= t::mu('PortExpeditorID') ?>
            </table>

            <h2>Delivery</h2>
            <table class="form">
                <?= t::mu('TruckerID') ?>
                <?= t::mu('TruckNumber') ?>
                <?= t::mu('Comments') ?>
            </table>
        </td>
        <td class="col-right">

            <h2>Dates</h2>
            <table class="form">
                <?= t::mu('Month') ?>
                <?= t::mu('MAWBDate') ?>
                <?= t::mu('ReadyForCarriageDate') ?>
                <?= t::mu('ETD') ?>
                <?= t::mu('ATD') ?>
                <?= t::mu('ETA') ?>
                <?= t::mu('ATA') ?>
            </table>

            <h2>Cargo</h2>
            <table class="form">
                <?= t::mu('Pieces') ?>
                <?= t::mu('ChargeableWeight') ?>
                <?= t::mu('GrossWeight') ?>
                <?= t::mu('Volume') ?>
                <?= t::mu('NatureOfGoods') ?>
            </table>
        </td>
    </tr>
</table>
