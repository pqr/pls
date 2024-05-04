<?php

namespace Forward\Module\Port;

use Forward\Application;
use Forward\Module\Shipment\ShipmentObj;
use Light\Db;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;
use Light\Response;
use Light\Session;
use Light\User;

class Port extends ModuleAbstract
{
    protected $textSaveBtn = 'Port';
    public $tableDefaultOrderBy = 'Name ASC';
    public $tableLayoutDefault = 'ID|Name|NameRu|isPOL|isPOD';

    public function __construct()
    {
        parent::__construct();

        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->addFields(

            CF::id(),
            F::createString('Name'),
            F::createString('NameRu', 'Name (ru)'),
            F::createCheckbox('isPOL', ['name' => 'POL', 'ajax' => true]),
            F::createCheckbox('isPOD', ['name' => 'POD', 'ajax' => true])

        );
    }

    #[\Override]
    protected function initFilters()
    {
        $this->allowFiltersLayoutChange = false;
        $this->addFilterText(['Name', 'NameRu'], ['name' => 'Name']);
    }

    #[\Override]
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

    #[\Override]
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

                $shipmentObj->updateDb($fieldsData, 'Удаление порта ' . htmlspecialchars((string) $this->_obj['Name']));
            }

            $msg = '<strong>' . count($shipments) . '</strong> перевозок было обновлено';
            Session::flashSuccess($msg);
        }
    }
}

