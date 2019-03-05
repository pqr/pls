<?php

namespace Light\Module\Autolaunch;

use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Fieldset;
use Light\Link;
use Light\Table;
use Light\Traits\ModuleSessionTrait;
use Light\Traits\TablePaginatable;

class AutolaunchLog extends Table
{
    use ModuleSessionTrait, TablePaginatable;

    protected $className;

    public $tableLinkModule = 'Autolaunch';
    public $tableFieldsShow = [
        'ID',
        'CreatedDateTime',
        'CreatedBy',
        'Filename',
        'ShortMessage',
        'WorkTime',
        'Log',
    ];

    public function __construct(string $className)
    {
        parent::__construct('AutolaunchLog');

        $this->className = $className;

        $params = ['class' => $className];
        $this->tableLinkUrlParams = $params;
        $this->tableReloadUrl = Link::create(Autolaunch::class)->params($params)->getHref();

        // Добавим поле Log в котором будут ссылки на режим View
        $this->setFieldsetLast(
            new Fieldset(
                F::createHtml('Log', [
                    'getHtmlFunction' => function ($value, array $data) use ($params) {
                        $logId = $data['ID'];
                        $params += ['id' => $logId] + $this->getModuleSessionUrlParams();
                        return Link::create(Autolaunch::class, 'Log')->params($params)->getHtml();
                    }
                ])
            )
        );


        $this->initPaginatable();
        $this->tableLoadData();
    }

    private function tableLoadData()
    {
        $findid = $this->getTableFindIdFromRequest();
        $newPage = $this->getTablePageFromModuleSession($findid);
        if ($newPage !== null) {
            $this->setTablePage($newPage);
        }

        $query = "SELECT SQL_CALC_FOUND_ROWS
                      ID
                    , CreatedDateTime
                    , CreatedBy
                    , ShortMessage
                    , WorkTime
                   FROM log_autolaunch
                   WHERE ClassName=@s
                   ORDER BY ID DESC";

        $tablePage = $this->getTablePage();
        $rowsPerPage = $this->getTableRowsPerPage();
        $query .= ' LIMIT ' . ($tablePage - 1) * $rowsPerPage . ', ' . $rowsPerPage;

        $data = Db::getAll($query, $this->className);

        $this->setTableData($data);

        //ID всех элементов соберём в отдельный массив, чтобы записать в moduleSession
        $ids = array_column($data, 'ID');

        $this->createModuleSession($ids, $tablePage);

        $foundRows = Db::getOne("SELECT FOUND_ROWS()");
        $this->setTableTotalRecordsFound($foundRows);
    }

    /**
     * Метод для переоределения в классах потомках
     * Возвращает url-параметры для строки. Они будут вставлены в аттрибут data-urlparams в теге tr,
     * Эти url-параметры в итоге будут использованы при двойном клике на строке (см. Table.js)
     * и при клике на одну из ссылок $this->_listLinkFieldKeys
     * @param array $rowDataCalculated
     * @param int $rowNum
     * @return array
     */
    protected function getTableRowLinkUrlParams(array $rowDataCalculated, int $rowNum): array
    {
        return ['id' => $rowDataCalculated['ID']] + $this->getModuleSessionUrlParams();
    }

}
