<?php
namespace Forward\Module\VInvoice\Subtables;

use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;
use Light\SubtableBase\Subtable;

class Responsibles extends Subtable
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->setDbTable('vinvoice_responsible');

        $this->_printHeadBlock = false;
        $this->_tableExcelEnabled = false;
        $this->_tableLayoutsEnabled = false;
        $this->_allowPaste = false;
        $this->_tableMsgIfEmpty = 'No responsibles';
        $this->_textAddLink = 'Add Responsible';

        $this->addFields(

            F::create('UserID', F::TYPE_SELECT, [
                'name' => 'Responsible', 'options' => "SELECT ID, Name, Active FROM user ORDER BY Name",
                'tableWidthHtml' => 183
            ])

        );

        $this->_cacheFieldsKeys = ['UserID'];
    }

    public function getHtmlEdit()
    {
        //Добавим в подтаблицу одну строку с Responsible CS из перевозки
        //если в GET запросе есть ключевой параметр fromCostSheet - значит попали сюда
        //из Cost-sheet'а нажатием кнопки Register Vendor Invoice
        if (isset($_GET['fromCostSheet'])) {
            $shipmentId = (int)$_GET['fromCostSheet'];
            $respSales = Db::getOne("SELECT ResponsibleSales FROM shipment WHERE ID = @i", $shipmentId);
            if ($respSales) {
                $this->setAdditionalData([['UserID' => $respSales]]);
            }
        }

        return parent::getHtmlEdit();
    }
}
