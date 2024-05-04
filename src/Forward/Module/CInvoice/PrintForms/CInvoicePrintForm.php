<?php

namespace Forward\Module\CInvoice\PrintForms;

use Forward\Classes\Constant;
use Forward\Module\CInvoice\Subtables\Lines;
use Light\ObjAbstract;
use Light\PrintForm\PrintForm;

class CInvoicePrintForm extends PrintForm
{
    private $stamp;

    public function __construct($key, $title, $format)
    {
        parent::__construct($key, $title, $format, self::OUT_SAVE_AND_STREAM);

        $this->stamp = str_contains((string) $key, 'Stamp');
    }

    #[\Override]
    public function testForObj(ObjAbstract $obj): bool
    {
        return ($obj['CompanyID'] == Constant::OC_GTS && !$this->stamp)
            || $obj['CompanyID'] == Constant::OC_PLS
            || $obj['CompanyID'] == Constant::OC_PLS_USD;
    }

    #[\Override]
    protected function getTitleHtml(ObjAbstract $obj): string
    {
        return 'Invoice ' . strip_tags((string) $obj->getDescriptionHtml());
    }

    #[\Override]
    protected function getTitleHtmlMultiple(int $count): string
    {
        return 'Invoices (' . $count . ')';
    }

    #[\Override]
    protected function getTemplate(ObjAbstract $obj): string
    {
        //Определим файл шаблона на основе компании и языка
        //Компания определяет папку в которой искать файл
        return match ($obj['CompanyID']) {
            Constant::OC_PLS, Constant::OC_PLS_USD => 'PrintForms/PLS/CInvoicePrintPls.tpl.php',
            Constant::OC_GTS => 'PrintForms/GTS/CInvoicePrintGts.tpl.php',
            default => throw new \Exception('No print form for CompanyID = ' . $obj['CompanyID']),
        };
    }

    #[\Override]
    protected function getTplData(ObjAbstract $obj): array
    {
        //Для начала в переменные для шаблона попадают все поля из шапки счёта
        $tdata = parent::getTplData($obj);

        //Теперь добавим дополнительные переменные шаблона: строки с услугами, флаги печати
        //Флаг отвечающий на заличие печати определяем по вхождению слова Stamp в название выбранной печатной формы
        $tdata['WithStamp'] = $this->stamp;

        //Информация по компании
        $tdata += Constant::getCompanyTplData($obj['CompanyID'], $obj['BankID']);

        //Добавим подтаблицу с линиями
        $linesSubtable = new Lines($obj);
        $linesSubtable->allowTablePrintHeadBlock = false;
        $linesSubtable->allowTableTotal = false;
        foreach ($linesSubtable->getFieldset() as $field) {
            $field->setTotal(false);
        }
        $linesSubtable->loadData();
        $linesSubtable->copySubtableDataToTableData();
        $linesSubtable->tableFieldsShow = ['Name', 'UOMID', 'Quantity', 'Price', 'Amount'];
        $tdata['LinesSubtable'] = $linesSubtable->getHtmlTable();

        return $tdata;
    }

}
