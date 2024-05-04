<?php

namespace Forward\Module\Shipment\Subtables;

use Forward\Module\Shipper\Shipper;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Link;
use Light\ObjAbstract;
use Light\SubtableBase\Subtable;

class Shippers extends Subtable
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->setDbTable('shipmentshipper');

        $this->allowTablePrintHeadBlock = false;
        $this->allowExportToExcel = false;
        $this->allowTableLayouts = false;
        $this->textTableEmpty = 'No shippers';

        $this->textSubtableTitle = 'Shippers';
        $this->textAddLink = 'Add Shipper';

        $this->addFields(

            F::createString('Name', [
                'name' => 'Shipper',
                'tableWidthHtml' => 204,
                F::PARAM_AC_SOURCE => 'list_shipper',
                'width' => 200,
                'upper' => true,
                'editCssClass' => 'shippers-Name',
                'getHtmlFunction' => function ($value, array $data, $outHtml) {
                    if ($value) {
                        $shipperId = Db::getOne("SELECT ID FROM list_shipper WHERE Name = @s", $value);
                        if ($shipperId) {
                            $outHtml = Link::create(Shipper::class, $outHtml)
                                ->view($shipperId)
                                ->blank()
                                ->getHtml();
                        } else {
                            $outHtml = Link::create(Shipper::class, $outHtml)
                                ->params(['addnew' => 1, 'Name' => $value])
                                ->blank()
                                ->getHtml();
                        }
                    }

                    return $outHtml;
                }
            ]),

            F::createString('Reference', [
                'name' => 'Ref. #',
                'tableWidthHtml' => 154,
                'upper' => true,
                'editCssClass' => 'shippers-Reference'
            ]),

            F::createString('HAWBNumber', [
                'name' => 'HAWB Number',
                'tableWidthHtml' => 104,
                'upper' => true,
                'editCssClass' => 'shippers-HAWBNumber'
            ]),

            F::createDate('HAWBDate', ['name' => 'HAWB Date', 'tableWidthHtml' => 84])

        );

        $this->_cacheFieldsKeys = ['Name', 'Reference', 'HAWBNumber', 'HAWBDate'];
    }

    /**
     * При полном копиовании перевозки (кнопка Full Copy) нужно скопировать и всех отправителей
     * @return void
     */
    private function loadFromShipmentRequest(): void
    {
        if (isset($_GET['copymode']) && $_GET['copymode'] === 'Full Copy') {

            $query = "SELECT DISTINCT(Name) FROM shipmentshipper WHERE Name != '' AND ShipmentID = " .
                (int)$_GET['copyfrom'];
            $data = Db::getAll($query);
            $this->setSubtableDataAdditional($data);
        }
    }

    #[\Override]
    public function getHtmlEdit()
    {
        $this->loadFromShipmentRequest();
        return parent::getHtmlEdit();
    }
}
