<?php use Light\Template as t; ?>
<table>
    <tr>
        <td>
            <table class="filters">
                <?= t::c('FileNumbers') ?>
                <?= t::c('MAWBNumbers') ?>
                <?= t::c('TrafficID') ?>
                <?= t::c('CompanyID') ?>
                <?= t::c('CustomerID') ?>
                <?= t::c('CustomerAccountID') ?>
                <?= t::c('Responsible') ?>
                <?= t::filtersButtonsRow() ?>
            </table>
        </td>
        <td>
            <table class="filters">
                <tr>
                    <th><?= t::label('DepartureLocationName') ?></th>
                    <td><?= t::html('DepartureIATA') ?> <?= t::html('DepartureLocationName') ?></td>
                </tr>

                <tr>
                    <th><?= t::label('ArrivalLocationName') ?></th>
                    <td><?= t::html('ArrivalIATA') ?> <?= t::html('ArrivalLocationName') ?></td>
                </tr>

                <?= t::c('Comments') ?>
                <?= t::c('CriterionID') ?>

                <tr>
                    <td></td>
                    <td width="254">
                        <div id="criteria-description-div"></div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div id="criteria-checkboxes-div"
                            <?php if (is_numeric(t::value('CriterionID')) || t::value('CriterionID') === '%'): ?>
                                class="display-none"
                            <?php endif; ?>
                        >
                            <?php
                            //Выведем первую половину чекбоксов с критериями
                            $criteriaArr = \Forward\Classes\ShipmentCriteria\ShipmentCriteria::getSelectOptions();
                            $htmlArr = [];
                            foreach ($criteriaArr as $crId => $info) {
                                $cssClass = isset($info['cssClass']) ? ' ' . $info['cssClass'] : '';
                                $htmlArr[] = '<span class="nobr' . $cssClass . '" title="'
                                    . htmlspecialchars($info['text'])
                                    . '">'
                                    . t::html('Criterion' . $crId)
                                    . ' ' . t::label('Criterion' . $crId)
                                    . '</span>';
                            }
                            print \Light\Html::getArrayInColumns($htmlArr, 2);
                            ?>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
        <td>
            <table class="filters">
                <tr>
                    <th><?= t::label('Color') ?></th>
                    <td class="color-container color<?= t::value('Color') ?>"><?= t::html('Color') ?></td>
                </tr>
                <?= t::c('BUIDsFilter') ?>
                <?= t::c('FileClosed') ?>
                <tr>
                    <th>Month</th>
                    <td><?= t::html('MonthFrom') ?> - <?= t::html('MonthUntil') ?></td>
                </tr>
                <?= t::datesFromUntil('ReadyForCarriageDate') ?>
                <?= t::datesFromUntil('ETD') ?>
                <?= t::datesFromUntil('ETA') ?>
                <?= t::datesFromUntil('ATA') ?>
                <tr>
                    <th>Flight</th>
                    <td><?= t::html('FlightNo') ?> <?= t::html('FlightDate') ?></td>
                </tr>
                <?= t::c('HasVAT') ?>
            </table>
        </td>
    </tr>
</table>
