<?php
namespace Forward\Module\Port;

use Forward\Module\Shipment\ShipmentObj;
use Light\Db;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;
use Light\Response;
use Light\Session;

class Port extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->_textObjectName = 'Port';
        $this->tableDefaultOrderBy = 'Name ASC';
        $this->tableLayouts['Default'] = 'ID|Name|NameRu|isPOL|isPOD';

        $this->addFields(

            CF::id(),
            F::createString('Name'),
            F::createString('NameRu', 'Name (ru)'),
            F::createCheckbox('isPOL', ['name' => 'POL', 'ajax' => true]),
            F::createCheckbox('isPOD', ['name' => 'POD', 'ajax' => true])

        );
    }

    protected function initFilters()
    {
        $this->filtersNoSettings = true;
        $this->addFilterName(F::TYPE_STRING, ['name' => 'Name', 'sqlWhere' => "Name LIKE '%{value}%' OR NameRu LIKE '%{value}%'"]);
    }

    protected function getDeleteConfirmationDialogHtml()
    {
        $portId = $this->_obj->getId();
        //Выведем диалоговое окно на подтверждение удаления отправителя
        //Проверим, есть ли перевозки привязанные к этому отправителю?
        $shipmentsCount = Db::getOne("SELECT count(*) FROM shipment WHERE POLID = @i OR PODID = @i", $portId, $portId);
        if ($shipmentsCount) {
            $outHtml = 'К этому порту привязано <strong>' . $shipmentsCount . '</strong> перевозок.<br>';
            $outHtml .= 'Выберите новый порт, который будет использоваться вместо удаляемого:<br>';
            $f = F::createSelect('NewPortID',
                ['options' => "SELECT ID, Name FROM port WHERE ID != $portId ORDER BY Name"]);
            $outHtml .= $f->getHtmlInput('');
            return $outHtml;
        }

        return parent::getDeleteConfirmationDialogHtml();
    }

    protected function checkDeleteConfirmResult(): bool
    {
        if (isset($_POST['NewPortID'])) {
            $newPortId = (int)$_POST['NewPortID'];
            if (!$newPortId) {
                Response::showDialog('При удалении нужно выбрать порт для замены');
            }

            $oldPortId = $this->_obj->getId();
            $shipments = Db::getAll("SELECT ID, POLID, PODID FROM shipment WHERE POLID = @i OR PODID = @i", $oldPortId,
                $oldPortId);
            foreach ($shipments as $shipmentInfo) {
                $shipmentObj = new ShipmentObj();
                $shipmentObj->setId($shipmentInfo['ID']);
                $fieldsData = [];
                if ($shipmentInfo['POLID'] == $oldPortId) {
                    $fieldsData['POLID'] = $newPortId;
                }
                if ($shipmentInfo['PODID'] == $oldPortId) {
                    $fieldsData['PODID'] = $newPortId;
                }

                $shipmentObj->updateDb($fieldsData, 'Удаление порта ' . htmlspecialchars($this->_obj['Name']));
            }

            $msg = '<strong>' . count($shipments) . '</strong> перевозок было обновлено';
            Session::flashSuccess($msg);
        }
    }
}

