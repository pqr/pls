<?php


namespace Light\Module\UserVacation;


use Light\Css;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\Js;
use Light\Link;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

class UserVacation extends ModuleAbstract
{
    public $tableDefaultOrderBy = 'ValidFrom';
    public $tableLayoutDefault = 'UserID|VacationType|ValidFrom|ValidUntil|Days|AvailableRemote|AvailableMobile|Comments|Idle';

    public $tableLinkFieldKeys = [];

    protected function validateUnique(array $dataFromPost)
    {
        // Проверим пересечение по датам с уже имеющимися записями
        $currentValidFrom = $dataFromPost['ValidFrom'];
        $currentValidUntil = $dataFromPost['ValidUntil'];
        if (!FieldDate::empty($currentValidFrom) && !FieldDate::empty($currentValidUntil)) {
            $query = "SELECT ID, VacationType, ValidFrom, ValidUntil
                  FROM uservacation
                  WHERE '$currentValidFrom' <= ValidUntil
                      AND '$currentValidUntil' >= ValidFrom
                      AND UserID = " . (int)($dataFromPost['UserID'] ?? User::getId()) . "
                      AND ID != " . (int)$this->_obj->getId();

            if ($exist = Db::getRow($query)) {


                $linkText = $this->getField('ValidFrom')->getHtml($exist['ValidFrom'])
                    . ' - ' . $this->getField('ValidUntil')->getHtml($exist['ValidUntil']);
                $link = Link::create(self::class, $linkText)
                    ->view($exist['ID'])
                    ->blank();

                $msg = 'Запись пересекается с другой: '
                    . $this->getField('VacationType')->getHtml($exist['VacationType'])
                    . ' ' . $link;
                $this->riseSaveErorr($msg);
            }
        }
    }

    protected function initFilters()
    {
        $this->addFilters(
            UserVacationFields::userId(),
            UserVacationFields::vacationType(),
        );

        $this->addFiltersDatesFromUntil(F::createDate('Valid', [
            'name' => 'Даты',
            'asInterval' => true,
        ]));

        $this->getFilterField('ValidFrom')->setDefault(date('Y-m-d'));

        $this->filtersLayout = [
            ['UserID', 'VacationType'],
            ['Valid'],
        ];
    }

    protected function initList()
    {
        $this->addFields(
            UserVacationFields::days(),
            UserVacationFields::idle()
        );
    }

    protected function initCssJs()
    {
        if ($this->isStateAddNew() || $this->isStateEdit()) {
            Js::addLight('UserVacationEdit.js');
        }

        if ($this->isStateList()) {
            Js::addLight('fullcalendar.min.js');
            Css::addLight('fullcalendar.min.css');

            Js::addLight('UserVacationList.js');
            Css::addLight('UserVacationList.css');
        }
    }

    public function getHtmlTableForAjax()
    {
        $outHtml = parent::getHtmlTableForAjax();

        // Добавим скрытое поле с со списком событий
        $events = $this->getEventsForCalendar();
        $outHtml .= '<input type="hidden" class="CalendarEvents" value="' . htmlspecialchars(json_encode($events)) . '">';

        return $outHtml;
    }

    private function getEventsForCalendar():array
    {
        // При подготовке списка событий для календаря используем все фильтры, кроме фильтра по датам
        $filtersValues = $this->getFiltersValues();
        if (isset($filtersValues['ValidFrom'])) {
            unset($filtersValues['ValidFrom']);
        }
        if (isset($filtersValues['ValidUntil'])) {
            unset($filtersValues['ValidUntil']);
        }
        $sqlWhere = $this->getFiltersFieldset()->getSqlWhere($filtersValues);
        $query = "SELECT u.Name AS title
                , uv.ValidFrom AS start
                , uv.ValidUntil + INTERVAL 1 DAY AS end
                , uv.VacationType
                , uv.UserID
            FROM uservacation uv
                JOIN user u ON uv.UserID = u.ID
            WHERE u.Active AND $sqlWhere
            ORDER BY uv.ValidFrom, uv.ValidUntil";
        $events = Db::getAll($query);

        $vacationTypes = $this->getField('VacationType')->getEvaluatedOptions();
        foreach ($events as &$event) {
            $cssClass = $vacationTypes[$event['VacationType']]['cssClass'] ?? '';
            $event['className'] = $cssClass;
            unset($event['VacationType']);
            $event['url'] = Link::userDialog($event['UserID'], '')->getHref();
            unset($event['UserID']);
        }

        return $events;
    }


    protected function proceedListRenderInitialPage()
    {
        parent::proceedListRenderInitialPage();
        print '<div class="CalendarDiv"></div>';
    }
}
