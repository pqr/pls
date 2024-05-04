<?php


namespace Light\Module\UserVacation;


use Light\ObjAbstract;

class UserVacationObj extends ObjAbstract
{
    protected $nameFieldKey = 'UserID';

    public function __construct()
    {
        $this->addFields(
            UserVacationFields::userId(),
            UserVacationFields::vacationType()->setDefault(UserVacationFields::VACATION_VACATION),
            UserVacationFields::validFrom(),
            UserVacationFields::validUntil(),
            UserVacationFields::availableRemote(),
            UserVacationFields::availableMobile(),
            UserVacationFields::comments()
        );
    }

    public function getDescriptionHtml()
    {
        return htmlspecialchars((string) $this->getFieldText('UserID'));
    }
}
