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

        $this->stamp = strpos($key, 'Stamp') !== false;
    }

    public function testForObj(ObjAbstract $obj): bool
    {
        return ($obj['CompanyID'] == Constant::OC_GTS && !$this->stamp)
            || $obj['CompanyID'] == Constant::OC_PLS
            || $obj['CompanyID'] == Constant::OC_PLS_USD;
    }

    protected function getTitleHtml(ObjAbstract $obj): string
    {
        return 'Invoice ' . strip_tags($obj->getDescriptionHtml());
    }

    protected function getTitleHtmlMultiple(int $count): string
    {
        return 'Invoices (' . $count . ')';
    }

    protected function getTemplate(ObjAbstract $obj = null): string
    {
        //Определим файл шаблона на основе компании и языка
        //Компания определяет папку в которой искать файл
        switch ($obj['CompanyID']) {
            case Constant::OC_PLS:
            case Constant::OC_PLS_USD:
                return 'PrintForms/PLS/CInvoicePrintPls.tpl.php';

            case Constant::OC_GTS:
                return 'PrintForms/GTS/CInvoicePrintGts.tpl.php';

            default:
                throw new \Exception('No print form for CompanyID = ' . $obj['CompanyID']);
        }
    }

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
        foreach ($linesSubtable->getFieldset() as $field) {
            $field->setTotal(false);
        }
        $linesSubtable->loadData();
        $linesSubtable->setTableTotal([]);

        $linesSubtable->setTableFieldsShow('Name|UOMID|Quantity|Price|Amount');
        $tdata['LinesSubtable'] = $linesSubtable->getHtmlTable(false);

        return $tdata;
    }

}