<?php
namespace Forward\Autolaunch;

use Forward\Classes\ShipmentCriteria\ShipmentCriteria;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Module\Autolaunch\AutolaunchAbstract;

class UpdateShipmentCriteria extends AutolaunchAbstract
{
    protected static $title = 'Update Shipment Criteria';
    protected static $description = 'Обновляет критерии по перевозкам. Должен запускаться ежедневно в начале дня.';

    public function initFieldset()
    {
        $options = ['All' => ['text' => 'Update All', 'cssClass' => 'bold']];
        $options += ShipmentCriteria::getSelectOptions();

        $this->addFields(F::create(
            'CriterionID',
            F::TYPE_SELECT,
            [
                'name' => 'Criterion',
                'default' => 'All',
                'empty' => false,
                'options' => $options
            ]
        ));
    }

    public function run(array $params)
    {
        if (isset($params['CriterionID']) && is_numeric($params['CriterionID'])) {
            //Обновляем какой-то определённый критерий
            $criterionId = (int)$params['CriterionID'];
            $msg = 'Update criterion <strong>' . ShipmentCriteria::getText($criterionId) . '</strong>';
            $this->_msgBuff->addInfo($msg);
            $this->_msgBuffShort->addInfo($msg);

            Db::begin();
            ShipmentCriteria::update($criterionId);
            $result = ShipmentCriteria::flush();
            Db::commit();

            $this->_msgBuff->add($result['inserted'] ? 'success' : 'skipp', 'Added shipments: ' . $result['inserted']);
            $this->_msgBuff->add($result['deleted'] ? 'success' : 'skipp', 'Removed shipments: ' . $result['deleted']);
            $this->_msgBuff->addInfo("Total shipments under criterion: " . $result['total']);


        } else {

            $msg = 'Update <strong>all criteria</strong>';
            $this->_msgBuff->addInfo($msg);
            $this->_msgBuffShort->addInfo($msg);
            Db::begin();
            ShipmentCriteria::updateAll(0, 'Autolaunch Update Shipment Criteria');
            $result = ShipmentCriteria::flush();
            Db::commit();

            /** @noinspection ForeachSourceInspection */
            foreach ($result['perCriterion'] as $criterionId => $criterionResult) {

                $this->_msgBuff->addEmptyLine();

                $criterionText = ShipmentCriteria::getText($criterionId);

                $this->_msgBuff->add(
                    $criterionResult['inserted'] ? 'success' : 'skipp',
                    $criterionText . ' added shipments: ' . $criterionResult['inserted']
                );

                $this->_msgBuff->add(
                    $criterionResult['deleted'] ? 'success' : 'skipp',
                    $criterionText . ' removed shipments: ' . $criterionResult['deleted']
                );

                $this->_msgBuff->addInfo($criterionText . " total shipments: " . $criterionResult['total']);
            }

        }
    }
}
