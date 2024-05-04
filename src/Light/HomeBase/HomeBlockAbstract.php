<?php

namespace Light\HomeBase;

use Light\Db;
use Light\Link;
use Light\Metric;
use Light\Misc;
use Light\Request;
use Light\Table;
use Light\Template;
use Light\Timer;
use Light\Traits\SubmoduleTrait;
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
    use TableExcelExportable, SubmoduleTrait;

    public const CSSCLASS_RED = 'red';
    public const CSSCLASS_RED2 = 'red2';
    public const CSSCLASS_ORANGE = 'orange';
    public const CSSCLASS_ORANGE2 = 'orange2';
    public const CSSCLASS_YELLOW = 'yellow';
    public const CSSCLASS_YELLOW2 = 'yellow2';
    public const CSSCLASS_GREEN = 'green';
    public const CSSCLASS_GREEN2 = 'green2';
    public const CSSCLASS_CYAN = 'cyan';
    public const CSSCLASS_CYAN2 = 'cyan2';
    public const CSSCLASS_BLUE = 'blue';
    public const CSSCLASS_BLUE2 = 'blue2';
    public const CSSCLASS_DARKBLUE = 'darkblue';
    public const CSSCLASS_DARKBLUE2 = 'darkblue2';
    public const CSSCLASS_PURPLE = 'purple';
    public const CSSCLASS_PURPLE2 = 'purple2';
    public const CSSCLASS_PINK = 'pink';
    public const CSSCLASS_PINK2 = 'pink2';

    /** @var string маленькая серая надпись под общим описанием, в которй указано для кого предназначено это напоминание */
    protected static $informationFor;

    protected $width = 320;
    protected $showCount = true; //показывать ли в заголовке блока число элементов строк в таблице?
    protected $userId;

    public $tableSticky = false;
    public $allowTablePrintHeadBlock = true;
    public $allowTablePrintTotalRecordsFound = false;

    public function __construct(int $useId)
    {
        parent::__construct();

        $this->userId = $useId;
        $this->tableReloadUrl = '?' . $_SERVER['QUERY_STRING'] . "&class=" . Misc::getClass($this);
    }

    public static function getInformationFrom()
    {
        return static::$informationFor;
    }

    protected function getExcelFilename()
    {
        return self::getTitle();
    }

    protected function getExcelWorksheetName()
    {
        return 'Home';
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

    /**
     * Метод для переопределеняи в классах потомках
     * @return Link|null
     */
    protected function getViewAllLink(int $userId): ?Link
    {
        return null;
    }

    /**
     * метод для переопределения в классах потомках
     * @return mixed
     */
    protected function getDataQuery(int $userId)
    {
        return '';
    }

    protected function tableLoadData(int $userId)
    {
        $timer = new Timer();
        $query = $this->getDataQuery($userId);
        if ($query) {
            $this->tableData = Db::getAll($query);
        }
        Metric::record(Request::getModule() . '/' . Misc::getClass($this), $timer->getMs(), ['UserID' => $userId]);
    }

    public function proceedSendExcel(): void
    {
        $this->tableLoadData($this->userId);
        $this->sendExcel();
    }

    public function getHtmlTableForAjax()
    {
        $this->tableLoadData($this->userId);
        return parent::getHtmlTableForAjax();
    }

    public function getHtml(string $cssClass)
    {
        $this->tableLoadData($this->userId);
        if (!$this->getTableDataCount() && !$this->allowTablePrintTheadIfEmpty) {
            return '';
        }

        $tdata = [
            'count' => $this->showCount ? $this->getCountForTitle() : null,
            'width' => $this->width,
            'title' => self::getTitle(),
            'description' => self::getDescription(),
            'informationFor' => self::getInformationFrom(),
            'table' => $this->getHtmlTable(),
            'viewAllLink' => (string)$this->getViewAllLink($this->userId),
        ];
        return Template::parseTpl('homeblock.tpl.php', $tdata + ['cssClass' => $cssClass]);
    }
}
