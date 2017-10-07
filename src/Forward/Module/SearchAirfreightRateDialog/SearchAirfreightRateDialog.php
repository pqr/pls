<?php
namespace Forward\Module\SearchAirfreightRateDialog;

use Light\ApplicationAbstract;
use Light\Js;
use Light\Response;
use Light\Template;

/**
 * Класс отвичает за рендринг диалогового окна поиска покупных тарифов
 */
class SearchAirfreightRateDialog
{
    /**
     * Основная точка входа
     */
    public function __invoke()
    {
        switch ($_GET['action']) {
            case "render":
                $this->proceedRender();
                break;

            case "ajaxSearch":
                $this->proceedAjaxSearch();
                break;

            default:
                Response::abort(400, 'Unknown action ' . $_GET['action']);
        }
    }

    /**
     * Метод вызывается из внешних модулей, поэтому пишем абсолютный путь до js файла относительно папки js
     */
    public static function initCssJs()
    {
        Js::add('Module/SearchAirfreightRateDialog/SearchAirfreightRateDialog.js');
    }

    private function proceedRender()
    {
        $resultsTable = new ResultsTable();
        $tdata = [
            'FiltersHtml' => $resultsTable->getFiltersHtml(),
            'ResultsTableHtml' => $resultsTable->getHtmlTable()
        ];
        $body = Template::parseTpl('SearchAirfreightRateDialog.tpl.php', $tdata);

        Response::showDialog($body, 'Choose', 'SearchAirfreightRateDialog.buttonOkHandler', 'Cancel', null, 1200, '90%', 0);
    }

    private function proceedAjaxSearch()
    {
        $resultsTable = new ResultsTable();
        print $resultsTable->getHtmlTableForAjax();
        ApplicationAbstract::terminate();
    }
}