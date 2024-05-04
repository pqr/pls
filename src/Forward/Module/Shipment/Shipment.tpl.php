<?php use Light\Template as t; ?>
<table>
    <tr>
        <td>
            <table>
                <tr>
                    <td class="col-left">
                        <h2>File Info</h2>

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
                                    <h3>
                                        <?php if (t::_state() === 'view'): ?>
                                            <?= t::html('MAWBNumber') ?> <?= t::html('Trace') ?>
                                        <?php else: ?>
                                            <span id="full-mawb-span"
                                                  class="display-none"><?= t::html('MAWBNumber') ?></span>
                                            <span id="split-mawb-span" class="display-none"><?= $MAWBNumber1 ?>
                                                - <?= $MAWBNumber2 ?></span>
                                        <?php endif; ?>
                                    </h3>
                                </td>
                            </tr>

                            <tr>
                                <th><?= t::label('CustomerID') ?></th>
                                <td><?= t::html('CustomerID') ?>
                                    <div id="prepaid-div" class="bad<?= $isPrepaidCustomer ? '' : ' display-none' ?>">
                                        Attention: Prepaid Customer
                                    </div>
                                </td>
                            </tr>
                            <?= t::c('CustomerAccountID') ?>
                            <?= t::c('ConsigneeID') ?>
                            <?php if (t::value('Consignee') !== ''): ?>
                                <?= t::c('Consignee') ?>
                            <?php endif; ?>
                            <?= t::c('ConsigneeReference') ?>
                            <?= t::c('ResponsibleSales') ?>
                            <?= t::c('ResponsibleCS') ?>
                        </table>

                        <h2>Route</h2>
                        <table class="form">
                            <?= t::c('AirlineAlphaCode') ?>

                            <tr id="OceanCarrierID-tr"<?php if ($TrafficType !== 'ocean') {
                                print ' class="display-none"';
                            } ?>>
                                <th><?= t::label('OceanCarrierID'); ?></th>
                                <td><?= t::html('OceanCarrierID'); ?></td>
                            </tr>

                            <?= t::c('PlaceOfReceipt') ?>

                            <?php include LIGHT_DIR_PROJECT . '/templates/DepartureIataLocation.tpl.php' ?>
                            <?php include LIGHT_DIR_PROJECT . '/templates/ArrivalIataLocation.tpl.php' ?>

                            <tr id="POLID-tr"<?php if ($TrafficType !== 'ocean') {
                                print ' class="display-none"';
                            } ?>>
                                <th><?= t::label('POLID'); ?></th>
                                <td><?= t::html('POLID'); ?></td>
                            </tr>

                            <tr id="PODID-tr"<?php if ($TrafficType !== 'ocean') {
                                print ' class="display-none"';
                            } ?>>
                                <th><?= t::label('PODID'); ?></th>
                                <td><?= t::html('PODID'); ?></td>
                            </tr>

                            <?= t::c('Destination') ?>

                            <?php include LIGHT_DIR_PROJECT . '/templates/Incoterms.tpl.php'; ?>

                        </table>


                        <div id="ocean-div"<?php if ($TrafficType !== 'ocean') {
                            print ' class="display-none"';
                        } ?>>

                            <h2>Ocean Freight</h2>
                            <table class="form">

                                <tr>
                                    <th>
                                        <?= t::label('ContainerName') ?>
                                    </th>
                                    <td>
                                        <?= t::html('ContainerName') ?> <span
                                                style="margin-left: 20px;"><?= t::html('ContainerType') ?></span>
                                    </td>
                                </tr>

                                <tr>
                                    <th>
                                        <?= t::label('Vessel') ?>
                                    </th>
                                    <td>
                                        <?= t::html('Vessel') ?>
                                        <span id="voyage-span"<?= t::title('VoyageNbr') ?>>
                                        <?= t::label('VoyageNbr') ?>: <?= t::html('VoyageNbr') ?>
                                        </span>
                                    </td>
                                </tr>

                                <?= t::c('PODTerminalID') ?>
                                <?= t::c('PortExpeditorID') ?>

                            </table>
                        </div>
                        <h2>Trucking</h2>

                        <table class="form">
                            <?= t::c('TruckerID') ?>
                            <?= t::c('TruckNumber') ?>
                            <?= t::c('Comments') ?>
                        </table>
                    </td>

                    <td class="col-right">
                        <h2>Traffic / Company / Cargo</h2>
                        <table class="form">
                            <?= t::c('TrafficID') ?>
                            <?= t::c('CompanyID') ?>
                            <?= t::c('VendorID') ?>
                            <?= t::c('Pieces') ?>
                            <?= t::c('GrossWeight') ?>
                            <?= t::c('ChargeableWeight') ?>
                            <?= t::c('Volume') ?>
                            <?= t::c('NatureOfGoods') ?>
                            <?= t::c('FlightNo') ?>
                            <?= t::c('FlightDate') ?>

                            <tr<?= t::title('EmailNoticeSent') ?>>
                                <th><?= t::label('EmailNoticeSent') ?></th>
                                <td id="emailnoticesent-td"><?= t::html('EmailNoticeSent') ?></td>
                            </tr>

                            <?= t::c('BUIDs') ?>
                            <?= t::c('QuotationID') ?>
                            <?= t::c('InsuranceAmount') ?>

                            <tr id="shipmentrequestno-tr"<?php if (!\Forward\Classes\Constant::isOneCCompany((int)t::value('CompanyID'))) {
                                print ' class="display-none"';
                            } ?>>
                                <th><?= t::label('ShipmentRequestNo') ?></th>
                                <td>
                                    <?= t::html('ShipmentRequestNo') ?>

                                    <?php if (t::_state() !== \Light\ModuleBase\ModuleAbstract::STATE_VIEW): ?>-
                                        <?= substr((string) $ShipmentRequestDate, 2, 2) ?>

                                    <?php endif; ?>
                                </td>
                            </tr>

                            <?= t::c('CActs') ?>

                            <?php if (t::text('DocsInventory')): ?>
                            <?= t::c('DocsInventory') ?>
                            <?php endif; ?>

                            <?php if (isset($Criteria)): ?>
                                <tr>
                                    <td colspan="2" style="background-color: white;"><?= $Criteria ?></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </td>

                    <td class="col-right">
                        <h2>Dates</h2>
                        <table class="form">
                            <?= t::c('Month') ?>
                            <?= t::c('MAWBDate') ?>
                            <?= t::c('ReadyForCarriageDate') ?>
                            <?= t::c('ETD') ?>
                            <?= t::c('ATD') ?>
                            <?= t::c('ETA') ?>
                            <?= t::c('ATA') ?>
                            <?= t::c('DocsPrintedDate') ?>
                            <?= t::c('DocsSentToAccountingDate') ?>
                        </table>

                        <?php if (\Forward\Classes\Constant::isOneCCompany((int)t::value('CompanyID'))): ?>
                            <h2>Files</h2>
                            <table class="form">
                                <?php foreach (\Forward\Classes\Constant::$standardFolders as $i => $_): ?>
                                    <?= t::c("File$i") ?>
                                <?php endforeach; ?>
                            </table>
                        <?php endif; ?>

                    </td>
                </tr>
            </table>

            <?= t::subtable('Shippers') ?>

        </td>
    </tr>
</table>
