<?php
namespace Light\HomeBase;

use Light\Db;
use Light\Table;
use Light\Traits\TableExcelExportable;

/**
 * Специально убрал из этого класса use SubmoduleTrait, т.к. по сути этот класс никогда не вызывается статически
 * для вывода какого-либо меню отображающего все возможные классы с их описаниями (как это просиходит, например, с ReportAbstract)
 * Поэтому нет смысла делать title, description и orderId статическими свойствами.
 * Напротив, сделах их динамическими можно использовать автодополнение кода при написии метода init(), как это работает
 * в других классах, например ModuleAbstract и Subtable.
 *
 * Class HomeBlockAbstract
 * @package Light\Home
 */
abstract class HomeBlockAbstract extends Table
{
    use TableExcelExportable;

    const CSSCLASS_RED = 'red';
    const CSSCLASS_RED2 = 'red2';
    const CSSCLASS_ORANGE = 'orange';
    const CSSCLASS_ORANGE2 = 'orange2';
    const CSSCLASS_YELLOW = 'yellow';
    const CSSCLASS_YELLOW2 = 'yellow2';
    const CSSCLASS_GREEN = 'green';
    const CSSCLASS_GREEN2 = 'green2';
    const CSSCLASS_CYAN = 'cyan';
    const CSSCLASS_CYAN2 = 'cyan2';
    const CSSCLASS_BLUE = 'blue';
    const CSSCLASS_BLUE2 = 'blue2';
    const CSSCLASS_DARKBLUE = 'darkblue';
    const CSSCLASS_DARKBLUE2 = 'darkblue2';
    const CSSCLASS_PURPLE = 'purple';
    const CSSCLASS_PURPLE2 = 'purple2';
    const CSSCLASS_PINK = 'pink';
    const CSSCLASS_PINK2 = 'pink2';

    /** @var string маленькая серая надпись под общим описанием, в которй указано для кого предназначено это напоминание */
    protected $_title;
    protected $_description;
    protected $_informationFor;
    protected $_width = 320;
    protected $_showCount = true; //показывать ли в заголовке блока число элементов строк в таблице?
    protected $_printHeadBlock = true;
    protected $_userId;

    public function __construct($userId)
    {
        parent::__construct($this->getHomeClass());

        $this->nosticky = true;
        $this->_tablePrintTotalRecordsFound = false;
        $this->_userId = $userId;

        $this->init();
        $this->tableLoadData($userId);
    }

    /**
     * Возвращает имя класса без неймспейсов
     * @return mixed
     */
    private function getHomeClass()
    {
        $parts = explode('\\', get_class($this));
        return end($parts);
    }

    protected function getExcelFilename()
    {
        return $this->_title;
    }

    protected function getExcelWorksheetName()
    {
        return 'Home';
    }

    public function getTableExcelUrl()
    {
        return '?' . $_SERVER['QUERY_STRING'] . '&class=' . $this->getHomeClass() . '&xls=1';
    }

    public function getTableReloadUrl()
    {
        return '?' . $_SERVER['QUERY_STRING'] . '&class=' . $this->getHomeClass();
    }

    /**
     * Метод для переопределения в классах потомках - возвращает число, которое будет отображаться в скобках
     * рядом с заголовком блока. Можно вернуть html, если нужно сделать подсветку или подсказки
     * @return string
     */
    protected function getCountForTitle()
    {
        return $this->getTableDataCount();
    }

    public function getTplData()
    {
        $tdata = [];

        $rowsCount = $this->getTableDataCount();
        if ($rowsCount) {
            $tdata['count'] = $this->_showCount ? $this->getCountForTitle() : null;
            $tdata['width'] = $this->_width;
            $tdata['title'] = $this->_title;
            $tdata['description'] = $this->_description;
            $tdata['informationFor'] = $this->_informationFor;
            $tdata['table'] = $this->getHtmlTable($this->_printHeadBlock);
            $tdata['viewAllLink'] = $this->getViewAllLink($this->_userId);
        }

        return $tdata;
    }

    /**
     * Переопределяем, чтобы использовать внутреннее свойство $this->_printHeadBlock вместо параметра функции
     * @param bool $printHeadBlock
     * @param bool $printIfEmpty
     * @return string
     */
    public function getHtmlTableForAjax($printHeadBlock = true, $printIfEmpty = false)
    {
        return parent::getHtmlTableForAjax($this->_printHeadBlock, $printIfEmpty);
    }

    protected function tableLoadData($userId)
    {
        $query = $this->getTableQuery($userId);
        $this->_tableData = Db::getAll($query);
    }

    /**
     * Метод для переопредления в классах потмоках, который должен инициализировать поля черех $this->addFields()
     */
    abstract protected function init();

    /**
     * Единственный метод для переопределения в классах потомках
     * @abstract
     * @param int $userId
     * @return mixed
     */
    abstract protected function getTableQuery($userId);

    /**
     * Метод для переопределеняи в классах потомках
     * @param int $userId
     * @return bool
     */
    protected function getViewAllLink($userId)
    {
        return false;
    }
}
