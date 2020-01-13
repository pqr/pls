<?php


namespace Light\Module\UserVacation;


use Light\Db;

class UserVacationHelpers
{
    public static function getCurrentVacationLabelHtml(int $userId): string
    {
        $currentVacationType = self::getCurrentVacationType($userId);
        if (!$currentVacationType) {
            return '';
        }

        static $vacationTypeField;
        if (!$vacationTypeField) {
            $vacationTypeField = UserVacationFields::vacationType();
        }

        return ' ' . $vacationTypeField->getHtml($currentVacationType);
    }

    public static function getCurrentVacationType(int $userId): ?int
    {
        static $currentVacations;
        if ($currentVacations === null) {
            $currentVacations = Db::getAssoc(
                "SELECT UserID, VacationType
                 FROM uservacation
                 WHERE ValidFrom <= CURDATE() AND ValidUntil >= CURDATE()
                 "
            );
        }

        return $currentVacations[$userId] ?? null;
    }

    public static function isOnVacation(int $userId): ?int
    {
        return self::getCurrentVacationType($userId) === UserVacationFields::VACATION_VACATION;
    }
}
