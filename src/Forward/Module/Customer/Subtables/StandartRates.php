<?php

namespace Forward\Module\Customer\Subtables;

use Forward\Module\Customer\Customer;
use Light\Field\FieldAbstract as F;
use Light\Ico;
use Light\Link;
use Light\ObjAbstract;
use Light\SubtableBase\Subtable;

class StandartRates extends Subtable
{
    public $allowExportToExcel = false;
    public $tableEmptyText = '';
    public $tableDefaultOrderBy = 'ID DESC';
    public $tableLayoutDefault = 'ID|Name|ResponsibleSalesID|DepartureIATA|ArrivalIATA|ValidFrom|ValidUntil|CommentsInternal';

    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

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
            'getHtmlFunction' => fn($value, $data) => Link::create(Customer::class, Ico::delete())
                ->view($data['CustomerID'])
                ->func('StandartRateDialog')
                ->params(['action' => 'del', 'srid' => $data['ID']])
                ->ajax()
                ->title('Delete')
                ->getHtml()
        ]);
    }
}
