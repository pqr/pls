<?php

namespace Light;


class InvStatus
{
    // Open process
    const DRAFT = 14;
    const OPEN = 11;
    const DISPUTE = 12;
    const ACCEPTED = 13;

    // Part Cleared/Cleared
    const PAYMENT_ON_CHEKCING = 24;
    const PAYMENT_CONFIRMED = 23;
    const PAYMENT_SENT_TO_BANK = 25;
    const PARTCLEARED = 21;
    const CLEARED = 22;

    // Canceled/Deleted
    const CREDITNOTED = 31;
    const DELETED = 32;

    // Something Strange
    const OPENTOXIC = 41;
    const ACCEPTED_NOTFORPAYMENT = 42;

    const DOCTYPE_INVOICE = 1;
    const DOCTYPE_CREDITNOTE = -1;

    public static $docType = [
        self::DOCTYPE_INVOICE => 'Счёт',
        self::DOCTYPE_CREDITNOTE => 'Кредти-нота'
    ];

    public static $cinvStatuses = [
        self::DRAFT => [
            'text' => 'Черновик',
            'cssClass' => 'invstatus14',
            'Active' => 0
        ],
        self::OPEN => [
            'text' => 'Не оплачен',
            'cssClass' => 'invstatus11',
            'Active' => 1
        ],
        self::PARTCLEARED => [
            'text' => 'Недоплата',
            'cssClass' => 'invstatus21',
            'Active' => 0
        ],
        self::CLEARED => [
            'text' => 'Оплачен',
            'cssClass' => 'invstatus22',
            'Active' => 0
        ],
        self::OPENTOXIC => [
            'text' => 'Токсичный',
            'cssClass' => 'invstatus41',
            'Active' => 1,
            'optgroup' => '---'
        ],
        self::CREDITNOTED => [
            'text' => 'Отменён кредит-нотой',
            'cssClass' => 'invstatus31',
            'Active' => 0,
            'optgroup' => '---'
        ],
        self::DELETED => [
            'text' => 'Удалён',
            'cssClass' => 'invstatus32',
            'Active' => 1,
            'optgroup' => '---'
        ]
    ];

    public static $vinvStatuses = [
        self::OPEN => [
            'text' => 'На проверке',
            'cssClass' => 'invstatus11',
            'Active' => 1
        ],
        self::DISPUTE => [
            'text' => 'Сомнительный',
            'cssClass' => 'invstatus12',
            'Active' => 1
        ],
        self::ACCEPTED => [
            'text' => 'Принят',
            'cssClass' => 'invstatus13',
            'Active' => 1
        ],
        self::PAYMENT_ON_CHEKCING => [
            'text' => 'Оплата на проверке',
            'cssClass' => 'invstatus24',
            'Active' => 0
        ],
        self::PAYMENT_CONFIRMED => [
            'text' => 'Подтверждён к оплате',
            'cssClass' => 'invstatus23',
            'Active' => 0
        ],
        self::PAYMENT_SENT_TO_BANK => [
            'text' => 'Поручение Банком исполнено',
            'cssClass' => 'invstatus25',
            'Active' => 0
        ],
        self::PARTCLEARED => [
            'text' => 'Недоплата',
            'cssClass' => 'invstatus21',
            'Active' => 0
        ],
        self::CLEARED => [
            'text' => 'Оплачен',
            'cssClass' => 'invstatus22',
            'Active' => 0
        ],

        self::ACCEPTED_NOTFORPAYMENT => [
            'text' => 'Не для оплаты',
            'cssClass' => 'invstatus42',
            'Active' => 1,
            'optgroup' => '---'
        ],
        self::CREDITNOTED => [
            'text' => 'Отменён кредит-нотой',
            'cssClass' => 'invstatus31',
            'Active' => 0,
            'optgroup' => '---'
        ],
        self::DELETED => [
            'text' => 'Удалён',
            'cssClass' => 'invstatus32',
            'Active' => 1,
            'optgroup' => '---'
        ]
    ];

    // Хеш таблицы открытых статусов, чтобы быстрее делать проверки
    public static $cinvOpen = [
        self::OPEN => self::OPEN,
        self::ACCEPTED => self::ACCEPTED,
        self::PARTCLEARED => self::PARTCLEARED
    ];

    public static $vinvOpen = [
        self::OPEN => self::OPEN,
        self::DISPUTE => self::DISPUTE,
        self::ACCEPTED => self::ACCEPTED,
        self::PARTCLEARED => self::PARTCLEARED,
        self::PAYMENT_CONFIRMED => self::PAYMENT_CONFIRMED,
        self::PAYMENT_ON_CHEKCING => self::PAYMENT_ON_CHEKCING
    ];

    public static $invDeleted = [
        self::DELETED => self::DELETED,
        self::CREDITNOTED => self::CREDITNOTED
    ];

    public static function getOpenStatusesSql(string $firstLetter): string
    {
        $openStatuses = $firstLetter === 'C' ? self::$cinvOpen: self::$vinvOpen;
        return implode(',', $openStatuses);
    }

    public static function getDeletedStatusesSql(): string
    {
        return implode(',', self::$invDeleted);
    }

    public static function isOpenStatus(string $firstLetter, int $status): bool
    {
        $openStatuses = $firstLetter === 'C' ? self::$cinvOpen: self::$vinvOpen;
        return isset($openStatuses[$status]);
    }

    public static function isClearedStatus(int $status): bool
    {
        return $status == self::CLEARED || $status == self::PARTCLEARED;
    }

    public static function isDeletedStatus(int $status): bool
    {
        return isset(self::$invDeleted[$status]);
    }

    public static function getIconHtml(string $firstLetter, int $status): string
    {
        $statuses = $firstLetter === 'C' ? self::$cinvStatuses : self::$vinvStatuses;
        if (!isset($statuses[$status])) {
            return '';
        }

        $title = $statuses[$status]['text'];

        $iconColorClass = "invicon$status";
        $title = ' title="' . htmlspecialchars($title) . '"';

        if ($status === self::DRAFT) {
            return "<i class=\"fa fa-question-circle-o $iconColorClass\"$title></i>";
        }

        if ($status === self::OPEN) {
            return "<i class=\"fa fa-exclamation-triangle $iconColorClass\"$title></i>";
        }

        if ($status === self::DISPUTE) {
            return "<i class=\"fa fa-times-circle $iconColorClass\"$title></i>";
        }

        if ($status === self::ACCEPTED) {
            return "<i class=\"fa fa-check $iconColorClass\"$title></i>";
        }

        if ($status === self::PAYMENT_ON_CHEKCING) {
            return "<i class=\"$iconColorClass\"$title></i>";
        }

        if ($status === self::PAYMENT_CONFIRMED) {
            return "<i class=\"$iconColorClass\"$title></i>";
        }

        if ($status === self::PAYMENT_SENT_TO_BANK) {
            return "<i class=\"$iconColorClass\"$title></i>";
        }

        if ($status === self::CLEARED) {
            return "<i class=\"$iconColorClass\"$title></i>";
        }



        if ($status === self::PARTCLEARED) {
            return "<span class=\"nobr $iconColorClass\"$title><i></i><i></i></span>";
        }

        if ($status === self::CREDITNOTED) {
            return "<i class=\"fa fa-minus-circle $iconColorClass\"$title></i>";
        }

        if ($status === self::DELETED) {
            return "<i class=\"fa fa-trash $iconColorClass\"$title></i>";
        }

        if ($status == self::OPENTOXIC) {
            return "<i class=\"fa fa-exclamation-triangle $iconColorClass\"$title></i>";
        }

        if ($status === self::ACCEPTED_NOTFORPAYMENT) {
            return "<i class=\"fa fa-check $iconColorClass\"$title></i>";
        }

        return '';
    }

    /**
     * Возвращает подходящий cssClass для полей Overdue и DueDate. Используется в VInvoiceObj, CInvoiceObj
     * @param string $firstLetter
     * @param int $status
     * @param int $overdue
     * @return string
     */
    public static function getOverdueViewCssClass(string $firstLetter, int $status, int $overdue)
    {
        if (!self::isOpenStatus($firstLetter, $status)) {
            return '';
        }

        if ($overdue > 0) {
            return 'bad';
        }

        if ($overdue >= -3) {
            return 'warning';
        }

        return '';
    }

    public static function getRowCssClass(int $status): string
    {
        if ($status == self::DELETED || $status == self::CREDITNOTED) {
            // Только для двух статусов определён особый css класс для строк таблицы
            return "invrow$status";
        }

        return '';
    }
}
