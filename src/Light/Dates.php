<?php


namespace Light;


use Light\Field\FieldDate;

class Dates
{
    public const DAYS_CALENDAR = 1;
    public const DAYS_WORKING = 2;

    public static function addDays(string $dateFrom, int $addDays, int $calendarOrWorking): string
    {
        return $calendarOrWorking === self::DAYS_CALENDAR
            ? self::addDaysCalendar($dateFrom, $addDays)
            : self::addDaysWorking($dateFrom, $addDays);
    }

    public static function isWorkingDay(string $date): bool
    {
        static $cacheCalendar = [];

        $year = substr($date, 0, 4);

        if (!isset($cacheCalendar[$year])) {
            // Закешируем список исключений на год указанный в $startDate
            $cacheCalendar[$year] = Db::getAssoc("SELECT Date, WorkingDay FROM production_calendar WHERE YEAR(Date) = @i",
                $year);
        }

        if (isset($cacheCalendar[$year][$date])) {
            // Если запрашиваемый день обнаружился в производственном календаре - вернём информацию оттуда,
            // рабочий он или нет
            return (bool)$cacheCalendar[$year][$date];
        }

        // Иначе используем стандартный подход
        // 'N' - ISO-8601 numeric representation of the day of the week (added in PHP 5.1.0)
        // 1 (for Monday) through 7 (for Sunday)
        $weekDay = date('N', strtotime($date));
        // Вохвращаем true, если это день с понедельника по пятницу
        return $weekDay <= 5;
    }

    public static function addDaysWorking(string $dateFrom, int $addWorkingDays): string
    {
        // будем прибавлять к стартовой дате рабочие дни
        $currentDate = new \DateTime($dateFrom);

        $addedDaysActual = 0;

        while ($addedDaysActual < $addWorkingDays) {
            // Двигаем счётчик на один день вперёд
            $currentDate->modify("+1 day");
            // Проверим, это рабочий день? (с учётом производственного календаря)
            if (self::isWorkingDay($currentDate->format("Y-m-d"))) {
                // Да, это рабочий день - увеличиваем счётчик реально использованных рабочих дней
                // чтобы рано или поздно выйти из while цикла
                $addedDaysActual++;
            }
        }

        return $currentDate->format("Y-m-d");
    }

    public static function addDaysCalendar(string $dateFrom, int $addDays): string
    {
        return date('Y-m-d', strtotime("$dateFrom +$addDays days"));
    }

    /**
     * Возвращает разницу в днях $endDate - $startDate с учётом знака
     * @param string $startDate
     * @param string $endDate
     * @return int|null
     */
    public static function diff(string $startDate, string $endDate): ?int
    {
        if (FieldDate::empty($startDate) || FieldDate::empty($endDate)) {
            return null;
        }
        // Отрезаем время
        $startDate = substr($startDate, 0, 10);
        $endDate = substr($endDate, 0, 10);
        // https://www.php.net/manual/ru/dateinterval.format.php
        // %r - Знак "-" при отрицательном числе, пусто при положительном
        // %a - Общее количество дней
        try {
            return (int)date_diff(new \DateTime($startDate), new \DateTime($endDate))->format('%r%a');
        } catch (\Exception $e) {
            return 0;
        }
    }
}
