<?php

namespace Forward\Module\Customer\Subtables;

use Light\Field\FieldAbstract as F;
use Light\Img;
use Light\Link;
use Light\ObjAbstract;
use Light\SubtableBase\Subtable;

class StandartRates extends Subtable
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->allowExportToExcel = false;
        $this->_tableMsgIfEmpty = '';
        $this->_orderBy = 'ID DESC';

        $this->tableLayouts['Default'] = 'ID|Name|ResponsibleSalesID|DepartureIATA|ArrivalIATA|ValidFrom|ValidUntil|CommentsInternal';

        $this->addCheckboxCol()
            ->setGetHtmlFunction(function ($value, array $data) {
                if (isset($data['ID'])) {
                    $validFrom = $data['ValidFrom'];
                    $validUntil = $data['ValidUntil'];
                    $checked = '';
                    $currDate = date('Y-m-d');
                    if ($validFrom <= $currDate && $currDate <= $validUntil) {
                        $checked = ' checked';
                    }
                    return '<input type="checkbox" class="cb" value="' . $data['ID'] . '"' . $checked . '>';
                }

                return '';
            });

        $this->getFieldsetLast()->createField('Del', F::TYPE_HTML, [
            'tableWidthHtml' => 20,
            'nameTable' => '',
            'tableAlign' => 'center',
            'getHtmlFunction' => function ($value, $data) {
                $delUrl = Link::url([
                    'id' => $data['CustomerID'],
                    'func' => 'StandartRateDialog',
                    'action' => 'del',
                    'srid' => $data['ID']
                ]);
                return '<a href="' . $delUrl . '#ajax" title="Delete">' . Img::iconDelete() . '</a>';
            }
        ]);
    }
}
