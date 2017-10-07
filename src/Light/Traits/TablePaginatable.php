<?php
namespace Light\Traits;

use Light\ApplicationAbstract;

/**
 * @method getTableUserParam($name)
 * @method setTableUserParam($name, $value)
 * @property array $_tableData
 */
trait TablePaginatable
{
    private $_page = 1;
    private $_showAll = false; //показывать все записи на одной странице?
    private $_rowsPerPage = 50;
    private $_tableTotalRecordsFound; //общее кол-во строк на всех страницах

    protected $_tablePaginationEnabled = true;

    protected function initPaginatable()
    {
        if ($this->_tablePaginationEnabled) {

            //Проверим параметр showall, который также мог прийти в REQUSET, сохраним его в базу
            if (isset($_REQUEST['showall'])) {
                $this->setTableUserParam('ShowAll', (int)$_REQUEST['showall']);
            }

            //Применим параметр ShowAll к текущему объекту
            $this->_showAll = (bool)$this->getTableUserParam('ShowAll');

            //Расчитаем текущую страницу
            if ($this->_showAll) {
                //В режиме отображения ВСЕХ записей на самом деле отображаем какое-то максимальное кол-во
                $this->_rowsPerPage = $this->getMaxRowsPerPage();
            } else {
                //В режиме отображения по страницам, оторбажаем константное значение default
                $this->_rowsPerPage = $this->getDefaultRowsPerPage();
            }

            //Прореверим, был ли передан номер страницы или номер записи с которой начать вывод
            if (isset($_POST['page'])) {
                $this->setTablePage($_POST['page']);
            }
        }
    }

    private function getDefaultRowsPerPage()
    {
        static $defaultRows = -1;
        if ($defaultRows === -1) {
            $defaultRows = ApplicationAbstract::getParam('TableDefaultRowsPerPage');
            $defaultRows = $defaultRows ?: 50;
        }
        return $defaultRows;
    }

    private function getMaxRowsPerPage()
    {
        static $maxRows = -1;
        if ($maxRows === -1) {
            $maxRows = ApplicationAbstract::getParam('TableDefaultRowsPerPage');
            $maxRows = $maxRows ?: 1000;
        }
        return $maxRows;
    }

    protected function getTablePagesHtml()
    {
        if (!$this->_tablePaginationEnabled) {
            return '';
        }

        $pagesCount = $this->getTablePagesCount();
        if ($pagesCount <= 1) {
            return '';
        }

        //Если страниц больше чем одна, выводим список страниц
        $pages = [];

        //Если это не первая страница, вывести ссылку на предыдущую
        if ($this->_page != 1) {
            $pages[] = ['text' => '◀', 'num' => $this->_page - 1];
        }

        $pagesToShow = 9;
        if ($pagesCount > $pagesToShow) {
            //Страниц больше, чем положено выводить в линейку в блоке Pages
            //Поэтмоу разбиваем...
            //Весь интервал $pagesToShow делится пополам. Слева от $currentPage выводится половина и справа выводится половина
            $startPage = $this->_page - floor($pagesToShow / 2);
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
                if ($i == $this->_page) {
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
                if ($i == $this->_page) {
                    $page['active'] = true;
                }

                $pages[] = $page;
            }
        }

        //Выведем ссылку на следующую страницу Next
        if ($this->_page != $pagesCount) {
            $pages[] = ['text' => '▶', 'num' => $this->_page + 1];
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
        if ($this->_tableTotalRecordsFound === null) {
            $this->_tableTotalRecordsFound = count($this->_tableData);
        }

        return $this->_tableTotalRecordsFound;
    }

    protected function setTableTotalRecordsFound($totalRows)
    {
        $this->_tableTotalRecordsFound = $totalRows;
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
        return $this->_page;
    }

    protected function setTablePage($page)
    {
        $this->_page = (int)$page;
        if ($this->_page < 1) {
            $this->_page = 1;
        }
    }

    protected function getTableRowsPerPage()
    {
        return $this->_rowsPerPage;
    }

    /**
     * Возвращает HTML код ссылки Show All или Show 50 per page
     * @return string
     */
    protected function getTableHtmlShowAll()
    {
        if (!$this->_tablePaginationEnabled) {
            return '';
        }

        if ($this->_showAll) {
            //Находимся в режиме "вывести все строки"
            //Выведем ссылку на постраничный просмотр
            $linkText = 'Show ' . $this->getDefaultRowsPerPage() . ' per page';
            return '<span><a href="#0" class="table-showall-link">' . $linkText . '</a></span>';

        }

        //Находим в режиме, когда отбражается по 50 строк на страницу
        if ($this->getTablePagesCount() > 1) {
            //Если страниц много, нужно вывести ссылку Show all
            $maxRowsPerPage = $this->getMaxRowsPerPage();
            if ($this->getTableDataCount() <= $maxRowsPerPage) {
                //Выведем ссылку с текстом 'Show all', если строк _всего_ такое кол-во, что они могут уместиться max
                $linkText = 'Show all';
            } else {
                //Если строк _всего_ больше, чем максимально допустимое значение, то текст ссылки Show all будет другим
                $linkText = 'Show ' . $maxRowsPerPage . ' per page';
            }

            return '<span><a href="#1" class="table-showall-link">' . $linkText . '</a></span>';
        }

        return '';
    }
}