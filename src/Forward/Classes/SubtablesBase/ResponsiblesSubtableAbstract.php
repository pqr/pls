<?php
namespace Forward\Classes\SubtablesBase;

use Forward\Application;
use Light\Field\FieldAbstract as F;
use Light\Link;
use Light\ObjAbstract;
use Light\SubtableBase\Subtable;

/**
 * Обрабатывает блоки "Responsibles" в модулях Customers и Vendors
 */
abstract class ResponsiblesSubtableAbstract extends Subtable
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->textSubtableTitle = 'Responsibles';
        $this->allowExportToExcel = false;
        $this->allowTableLayouts = false;
        $this->allowTablePrintHeadBlock = false;
        $this->allowPaste = false;

        $this->textAddLink = 'Add Responsible';
        $this->textTableEmpty = 'No responsibles';

        $this->addFields(

            F::createSelect('UserID', [
                'name' => 'User',
                'tableWidthHtml' => 160,
                'options' => "SELECT ID, Name, Active FROM user ORDER BY Name",
                'empty' => false,
                'editWidth' => 'medium',
                'getHtmlFunction' => function($value, array $data, $outHtml) {
                    if (!$value) {
                        return $outHtml;
                    }

                    return Link::userDialog($value, $outHtml);
                }
            ]),

            F::createSelect('Role', [
                'name' => 'Role',
                'tableWidthHtml' => 160,
                'options' => [
                    Application::ROLE_CS => 'CS',
                    Application::ROLE_SALES => 'Sales'
                ],
                'empty' => false,
                'editWidth' => 'medium'
            ])

        );

        $this->setNameFieldKey('UserID');
    }

    /**
     * Переопределяем метод, чтобы добавить LEFT JOIN user и сделать сортировку по user.Name
     * @return string
     */
    #[\Override]
    protected function getDataQuery()
    {
        $query = "SELECT r.ID
                      , r.UserID
                      , r.Role
                  FROM {$this->getDbTable()} r
                      LEFT JOIN user ON user.ID = r.UserID
                  WHERE ";

        if ($this->_masterObj->getId()) {
            $query .= $this->getMasterFieldKey() . ' = ' . $this->getMasterFieldValue();
        } else {
            $query .= "0";
        }

        $query .= " ORDER BY r.ID";

        return $query;
    }
}
