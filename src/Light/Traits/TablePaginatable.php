<?php

namespace Light\Traits;

use Light\Config;
use Light\Table\TableUserParamsRepository;

trait TablePaginatable
{
    private $tablePage = 1;
    private $tableShowAll = false; //показывать все записи на одной странице?
    private $tableTotalRecordsFound; //общее кол-во строк на всех страницах

    protected $defaultRowsPerPage = 50;
    protected $maxRowsPerPage = 1000;

    protected $allowPagination = true;

    protected function initPaginatable()
    {
        if (!$this->allowPagination) {
            return;
        }

        // Проинициализируем флаг $this->tableShowAll
        $this->initShowAll();

        // Прореверим, был ли передан номер страницы или номер записи с которой начать вывод
        $requestKey = $this->getTableName() . '_page';
        if (isset($_REQUEST[$requestKey])) {
            $this->setTablePage($_REQUEST[$requestKey]);
        }
    }

    private function initShowAll(): void
    {
        /** @var TableUserParamsRepository $tableUserParams */
        // Отдельный объект, которых заведует сохранением настроек в базу для данного пользователя
        $tableUserParams = $this->tableUserParams;

        $requestKey = $this->getTableName() . '_showall';
        if (isset($_REQUEST[$requestKey])) {
            // От браузера пришли какие-то новые настройки для таблицы - используем их!
            $newShowAllFromRequest = (bool)$_REQUEST[$requestKey];
            // Применим пришедшие настроки к текущей таблице
            $this->tableShowAll = $newShowAllFromRequest;
            // Также сохраним эти настройки в настройки пользователя
            $tableUserParams->saveShowAll($newShowAllFromRequest);

            return;
        }

        // Если из браузера не поступало никаких сигналов об изменении настройки showall,
        // то загрузим из настроек пользователя из базы
        $this->tableShowAll = $tableUserParams->loadShowAll();
    }

    protected function getTablePagesHtml()
    {
        if (!$this->allowPagination) {
            return '';
        }

        $pagesCount = $this->getTablePagesCount();
        if ($pagesCount <= 1) {
            return '';
        }

        //Если страниц больше чем одна, выводим список страниц
        $pages = [];

        //Если это не первая страница, вывести ссылку на предыдущую
        if ($this->tablePage != 1) {
            $pages[] = ['text' => '◀', 'num' => $this->tablePage - 1];
        }

        $pagesToShow = 9;
        if ($pagesCount > $pagesToShow) {
            //Страниц больше, чем положено выводить в линейку в блоке Pages
            //Поэтмоу разбиваем...
            //Весь интервал $pagesToShow делится пополам. Слева от $currentPage выводится половина и справа выводится половина
            $startPage = $this->tablePage - floor($pagesToShow / 2);
            if ($startPage < 1) {
                $startPage = 1;
            }
            $endPage = $startPage + $pagesToShow - 1;
            if ($endPage > $pagesCount) {
                $endPage = $pagesCount;
                $startPage = $endPage - $pagesToShow + 1;
            }

            if ($startPage > 1) {
                //Выведем ссылку на первую страницу
                $pages[] = ['text' => '1', 'num' => 1];
            }

            if ($startPage > 2) {
                //Выведем разделитель
                $pages[] = [];
            }

            for ($i = $startPage; $i <= $endPage; $i++) {

                $page = ['text' => $i, 'num' => $i];
                if ($i == $this->tablePage) {
                    $page['active'] = true;
                }

                $pages[] = $page;
            }

            if ($endPage < $pagesCount - 1) {
                //Выведем разделитель
                $pages[] = [];
            }

            if ($endPage < $pagesCount) {
                //Выведем ссылку на последнюю страницу
                $pages[] = ['text' => $pagesCount, 'num' => $pagesCount];
            }

        } else {
            //Если число страниц умещается в один пажинатор, просто выведем их все
            for ($i = 1; $i <= $pagesCount; $i++) {

                $page = ['text' => $i, 'num' => $i];
                if ($i == $this->tablePage) {
                    $page['active'] = true;
                }

                $pages[] = $page;
            }
        }

        //Выведем ссылку на следующую страницу Next
        if ($this->tablePage != $pagesCount) {
            $pages[] = ['text' => '▶', 'num' => $this->tablePage + 1];
        }

        $outHtml = '<span class="table-pagination"><span class="table-pages">';
        foreach ($pages as $page) {
            if (isset($page['num'])) {
                if (isset($page['active'])) {
                    $outHtml .= '<strong>' . $page['text'] . '</strong>';
                } else {
                    $outHtml .= '<a href="#' . $page['num'] . '">' . $page['text'] . '</a>';
                }
            } else {
                //Разделитель
                $outHtml .= '...';
            }
        }
        $outHtml .= '</span></span>';

        return $outHtml;
    }

    /**
     * Переопределяем стандартную функцию подсчёта кол-ва строк
     * @return int
     */
    protected function getTableTotalRecordsFound()
    {
        if ($this->tableTotalRecordsFound === null) {
            $this->tableTotalRecordsFound = $this->getTableDataCount();
        }

        return $this->tableTotalRecordsFound;
    }

    protected function setTableTotalRecordsFound($totalRows)
    {
        $this->tableTotalRecordsFound = $totalRows;
    }

    /**
     * Вычисляет и возвращает количество страниц (используется в getHtmlTablePagination() и getHtmlShowAll())
     * @return int
     */
    public function getTablePagesCount()
    {
        return ceil($this->getTableTotalRecordsFound() / $this->getTableRowsPerPage());
    }

    public function getTablePage()
    {
        return $this->tablePage;
    }

    protected function setTablePage($page)
    {
        $this->tablePage = (int)$page;
        if ($this->tablePage < 1) {
            $this->tablePage = 1;
        }
    }

    protected function getTableRowsPerPage()
    {
        return $this->tableShowAll ? $this->maxRowsPerPage : $this->defaultRowsPerPage;
    }

    /**
     * Возвращает HTML код ссылки Show All или Show 50 per page
     * @return string
     */
    protected function getTableHtmlShowAll()
    {
        if (!$this->allowPagination) {
            return '';
        }

        if ($this->tableShowAll) {
            //Находимся в режиме "вывести все строки"
            //Выведем ссылку на постраничный просмотр
            $linkText = Config::$lang === 'ru' ? "Показать {$this->defaultRowsPerPage} на страницу" : "Show {$this->defaultRowsPerPage} per page";
            return "<span><a href=#0 class=table-showall-link>$linkText</a></span>";

        }

        // Находим в режиме, когда отбражается по 50 строк на страницу
        if ($this->getTablePagesCount() > 1) {
            //Если страниц много, нужно вывести ссылку Show all
            $linkText = Config::$lang === 'ru' ? "Показать {$this->maxRowsPerPage} на страницу" : "Show {$this->maxRowsPerPage} per page";
            return "<span><a href=#1 class=table-showall-link>$linkText</a></span>";
        }

        return '';
    }

    protected function getShowAllWarning()
    {
        if ($this->allowPagination && $this->tableShowAll && $this->getTableDataCount() > $this->defaultRowsPerPage) {
            $msg = Config::$lang === 'ru'
                ? "Вы просматриваете таблицу в режиме \"{$this->maxRowsPerPage} записей на страницу\" - страницы могут загружаться медленно!
                Скорее всего, вам и не нужно столько записей на одном экране, сложно смотреть одновременно на {$this->maxRowsPerPage} строк в таблице и понимать смысл происходящего.
                <br>Рекомендуем <a href=#0 class=table-showall-link>переключиться в режим {$this->defaultRowsPerPage} на страницу</a>. Примечание: функция экспорта в Excel выгружает все найденные записи вне зависимости от выбранного режима отображения на экране и без разбивки по страницам."
                : "You are in \"{$this->maxRowsPerPage} pre page\" mode - this might be slow. Recommend <a href=#0 class=table-showall-link>switch to {$this->defaultRowsPerPage} per page</a>.";
            return '<div class="alert alert-warning">' . $msg . '</div>';
        }

        return '';

    }

    protected function getDataQueryWithLimits(string $query, bool $isXls): string
    {
        if ($isXls) {
            return $query .' LIMIT ' . $this->getTableExcelMaxRows();
        }

        if (!$this->allowPagination) {
            return $query;
        }

        $page = $this->getTablePage();
        $rowsPerPage = $this->getTableRowsPerPage();
        return $query . ' LIMIT ' . ($page - 1) * $rowsPerPage . ', ' . $rowsPerPage;
    }
}
