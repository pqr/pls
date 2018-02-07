<?php
namespace Forward\Module\Shipment\CostSheet\Subtables\Base;

use Light\Field\FieldAbstract as F;
use Light\Field\FieldMoneyWithCurrency;
use Light\ObjAbstract;
use Light\SubtableBase\Subtable;

abstract class IncomeExpensesAbstract extends Subtable
{
    /**
     * @var array вспомагательная переменная - разбивка сумм по номерам счетов в колонке InvNum
     * Используется для подсветки сумм не соотвесвующих реальным инвойсам (информацию о реальных инвойсах получаем
     * через статический класс CommonDataProvider)
     */
    protected $_sumAmoutPerInvNum = [];

    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->_printHeadBlock = false;
        $this->allowExportToExcel = false;
        $this->allowTableLayouts = false;
        $this->_copyOnAddFieldKeys = ['Quantity', 'Amount'];
    }

    protected function getMasterModule()
    {
        return 'Shipment';
    }

    public function loadData()
    {
        parent::loadData();

        //Пройдём по загруженным данным и сделаем разбивку по номерам счетов в колонке InvNum
        $this->_sumAmoutPerInvNum = [];
        foreach ($this->_data as $rowData) {
            $invNum = $rowData['InvNum'];
            if ($invNum) {
                $key = 'InvNum' . $invNum;
                if (!isset($this->_sumAmoutPerInvNum[$key])) {
                    $this->_sumAmoutPerInvNum[$key] = 0;
                }

                list(, $amount) = FieldMoneyWithCurrency::getAsTouple($rowData['Amount']);
                $this->_sumAmoutPerInvNum[$key] += $amount;
            }
        }
    }

    public function getSubAmountForInvNum($invNum)
    {
        return $this->_sumAmoutPerInvNum['InvNum' . $invNum] ?? 0;
    }

    protected function initTableFieldsAdditional()
    {
        if ($this->isStateView()) {
            $this->getFieldsetFirst()->addField(
                F::create(
                    'Checkbox',
                    F::TYPE_HTML,
                    [
                        'name' => '<input type="checkbox" class="cb-all" checked="">',
                        'tableWidthHtml' => 20,
                        'tableAlign' => 'center',
                        'getHtmlFunction' => function ($value, $data, $outHtml) {
                            if (isset($data['ID'])) {
                                $checked = $data['InvNum'] == '' ? ' checked=""' : '';
                                $outHtml = '<input type="checkbox" class="cb" value="' . $data['ID'] . '"' . $checked . '>';
                            }

                            return $outHtml;
                        }
                    ]
                )
            );
        }

        parent::initTableFieldsAdditional();
    }

    public function getAmountPerCurrency()
    {
        //Создает готовую для отображения информацию о сумме каждой валюты в подтаблице
        $amountPerCurrency = [];
        foreach ($this->_data as $rowData) {
            list($currencyIso, $amount) = FieldMoneyWithCurrency::getAsTouple($rowData['Amount']);
            if (!isset($amountPerCurrency[$currencyIso])) {
                $amountPerCurrency[$currencyIso] = 0;
            }
            $amountPerCurrency[$currencyIso] += $amount;
        }

        return $amountPerCurrency;
    }

    /**
     * Переопределяем стандартный метод, возвращающий имя таблицы, которое в том числе используется в JS окружении
     * чтобы при нажатии кнопки Edit на экране Cost-sheet рендрилась таблица с другим именем (с суффиктом Edit)
     * @return string|void
     */
    public function getTableName()
    {
        $tableName = parent::getTableName();
        if ($this->isStateEdit() || $this->isStateSubmit()) {
            $tableName .= 'Edit';
        }

        return $tableName;
    }

    protected function isFindId(array $rowData):bool
    {
        if (isset($_GET['fromInvNum']) && isset($rowData['InvNum']) && $_GET['fromInvNum'] == $rowData['InvNum']) {
            return true;
        }

        return parent::isFindId($rowData);
    }

    protected function getTableRowTagParams(array $rowData, int $rowNum):string
    {
        //Если в GET параметрах есть параметр fromInvNum, значит нужно подсветить все строки с этим номером счёта
        $tagParams = parent::getTableRowTagParams($rowData, $rowNum);

        //Добавим сумму данной строки в параметр, чтобы в JavaScript можно было посчитать сумму выделенных строк
        list($currencyIso, $amount) = FieldMoneyWithCurrency::getAsTouple($rowData['Amount']);
        $tagParams .= ' data-amount="' . $amount . '" data-currency="' . $currencyIso . '"';

        //Добавим data параметр с номером счёта
        $invId = $this->getInvIdForRow($rowData);
        if ($invId) {
            $tagParams .= ' data-invid="' . $invId . '"';
        }

        return $tagParams;
    }

    public function getHtmlTableForAjax($printHeadBlock = true, $printIfEmpty = false)
    {
        $outHtml = parent::getHtmlTableForAjax($printHeadBlock, $printIfEmpty);
        if ($this->isStateView()) {
            $outHtml .= '<div class="total-selected alert">Total selected: <span></span></div>';
        }
        return $outHtml;
    }


    abstract protected function getInvIdForRow(array $rowData);
}
