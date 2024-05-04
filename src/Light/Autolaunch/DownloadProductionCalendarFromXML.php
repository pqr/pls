<?php

namespace Light\Autolaunch;

use Light\Db;
use Light\Module\Autolaunch\AutolaunchAbstract;
use Light\Field\FieldAbstract as F;
use Light\Module\ProductionCalendar\ProductionCalendarObj;

class DownloadProductionCalendarFromXML extends AutolaunchAbstract
{
    protected static $title = 'Скачать производственный календарь';
    protected static $description = 'Скачивает производственный календарь в формате XML с сайта xmlcalendar.ru';

    public function initFieldset(): void
    {
        $this->addFields(
            F::createInt('Year', [
                'name' => 'Год',
                'editAlign' => 'left',
                'default' => date('Y')
            ])
        );
    }

    protected function run(array $params)
    {
        // распарсим производственный календарь в XML
        $year = (int)$params['Year'];
        $url = "http://xmlcalendar.ru/data/ru/$year/calendar.xml";
        try {
            $calendar = simplexml_load_file($url);
        } catch (\Exception $e) {
            $this->_msgBuff->addError($e->getMessage());
            $this->_msgBuffShort->addError($e->getMessage());
            return;
        }

        $changed = 0;
        $notChanged = 0;

        Db::begin();

        $existDays = Db::getAssoc("SELECT `Date`, ID, WorkingDay FROM production_calendar WHERE YEAR(Date) = $year");

        foreach ($calendar->days->day as $calendarDay) {
            [$month, $day] = explode(".", (string)$calendarDay['d']);

            $date = "$year-$month-$day";
            $workingDay = (int)$calendarDay['t'] == 1 ? 0 : 1;
            if (isset($existDays[$date])) {

                if ($existDays[$date]['WorkingDay'] != $workingDay) {
                    $productionCalendarObj = new ProductionCalendarObj();
                    $productionCalendarObj->setId($existDays[$date]['ID']);
                    $productionCalendarObj->updateDb(['WorkingDay' => $workingDay], 'Автоматическое обновление с сайта xmlcalendar.ru');
                    $changed++;
                } else {
                    $notChanged++;
                }
                unset($existDays[$date]);

            } else {

                $productionCalendarObj = new ProductionCalendarObj();
                $productionCalendarObj->insertDb(['Date' => $date,'WorkingDay' => $workingDay], 'Автоматическое добавление с сайта xmlcalendar.ru');
                $changed++;

            }

        }

        // Все существовавшие ранее в базе дни, но не подтверждённые текущей закачкой нужно удалить
        // В массиве $existDays остались именно такие дни
        foreach ($existDays as ['ID' => $deleteId]) {
            $productionCalendarObj = new ProductionCalendarObj();
            $productionCalendarObj->setId($deleteId);
            $productionCalendarObj->loadData();
            $productionCalendarObj->deleteDb('Автоматическое удаление, т.к. этой даты нет на сайте xmlcalendar.ru');
            $changed++;
        }

        Db::commit();

        $msg = "Добавлено/обновлено/удалено дней: $changed";
        $this->_msgBuff->addSuccessOrInfo($msg, $changed);
        $this->_msgBuffShort->addSuccessOrInfo($msg, $changed);

        if ($notChanged) {
            $msg = "Не изменилось: $notChanged";
            $this->_msgBuff->addInfo($msg);
            $this->_msgBuffShort->addInfo($msg);
        }
    }
}
