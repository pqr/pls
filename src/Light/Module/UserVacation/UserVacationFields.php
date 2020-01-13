<?php


namespace Light\Module\UserVacation;


use Carbon\Carbon;
use Light\Db;
use Light\Field\CommonFields;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\Field\FieldInt;
use Light\Ico;
use Light\Link;
use Light\User;

class UserVacationFields
{
    const VACATION_VACATION = 1;
    const VACATION_REMOTE = 2;
    const VACATION_SEEK = 3;
    const VACATION_BUSINESS = 4;

    public static function userId()
    {
        return F::createSelect('UserID', [
            'name' => 'Сотрудник',
            'editMandatory' => true,
            'options' => "SELECT ID, Name, Active FROM user ORDER BY Name",
            'select2' => true,
            'tableWidhtExcel' => 20,
            'getHtmlFunction' => static function ($value, array $data, $outHtml) {
                $value = (int)$value;
                if (!$value) {
                    return '';
                }
                $outHtml = User::avatarImgWithNameHtml($value, $outHtml);
                return Link::userDialog($value, $outHtml);
            },
        ]);
    }

    public static function vacationType()
    {
        return F::createSelect('VacationType', [
            'name' => 'Причина отсутствия',
            'empty' => false,
            'tableWidhtExcel' => 25,
            'options' => [
                self::VACATION_REMOTE => ['text' => 'Удалённая работа', 'cssClass' => 'warning'],
                self::VACATION_VACATION => ['text' => 'Отпуск', 'cssClass' => 'good'],
                self::VACATION_SEEK => ['text' => 'Болезнь', 'cssClass' => 'bad'],
                self::VACATION_BUSINESS => ['text' => 'На встрече', 'cssClass' => 'info'],
            ],
        ]);
    }

    public static function days(): FieldInt
    {
        return F::createInt('Days', [
            'name' => 'Число дней',
            'sqlSelect' => 'DATEDIFF(ValidUntil, ValidFrom) + 1 - (SELECT COUNT(*) FROM production_calendar WHERE WorkingDay = 0 AND uservacation.ValidFrom <= Date AND Date <= uservacation.ValidUntil)',
        ]);
    }

    public static function validFrom(): FieldDate
    {
        return CommonFields::validFrom()
            ->setAjax(true)
            ->setEditMandatory(true);
    }

    public static function validUntil(): FieldDate
    {
        return CommonFields::validUntil()
            ->setAjax(true)
            ->setGetDataFromRequestFunction(null)
            ->setEditMandatory(true);
    }

    public static function availableRemote(): \Light\Field\FieldCheckbox
    {
        return F::createCheckbox('AvailableRemote', [
            'name' => 'Удалённый доступ',
        ]);
    }

    public static function availableMobile(): \Light\Field\FieldCheckbox
    {
        return F::createCheckbox('AvailableMobile', [
            'name' => 'Мобильный',
            'tableWidhtExcel' => 20,
            'getHtmlFunction' => static function ($value, array $data, $outHtml) {
                if ($value && isset($data['UserID'])) {
                    $mobile = self::getUserMobile($data['UserID']);
                    if ($mobile) {
                        return htmlspecialchars($mobile);
                    }
                }
                return $outHtml;
            },
            'getExcelFunction' => static function ($value, array $data, $out) {
                if ($value && isset($data['UserID'])) {
                    $mobile = self::getUserMobile($data['UserID']);
                    if ($mobile) {
                        return $mobile;
                    }
                }
                return $out;
            },
        ]);
    }

    public static function comments(): \Light\Field\FieldText
    {
        return CommonFields::comments(['name' => 'Замещающий/Комментарии']);
    }

    public static function idle()
    {
        return F::createString('Idle', [
            'name' => "Последняя\nактивность",
            'modes' => F::MODE_READONLY,
            'sqlSelect' => "SELECT LastRefreshDateTime FROM user_visit WHERE UserID = uservacation.UserID",
            'calcFunction' => static function ($value) {
                if ($value) {
                    $value = (new Carbon($value))->locale('ru')->diffForHumans();
                }
                return $value;
            },
        ]);
    }

    public static function getUserMobile(int $userId): string
    {
        static $mobilesCache;
        if ($mobilesCache === null) {
            $mobilesCache = Db::getAssoc("SELECT ID, Mobile FROM user");
        }
        return $mobilesCache[$userId] ?? '';
    }
}
