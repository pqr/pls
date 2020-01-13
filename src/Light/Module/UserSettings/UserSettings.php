<?php


namespace Light\Module\UserSettings;


use Light\Config;
use Light\Db;
use Light\Ico;
use Light\Js;
use Light\Misc;
use Light\Module\UserModule\UserModule;
use Light\ModuleBase\SettingsPageAbstract;

use Light\Field\FieldAbstract as F;
use Light\Response;
use Light\Session;
use Light\SimpleObj;
use Light\Translate;
use Light\User;

class UserSettings extends SettingsPageAbstract
{
    public function __invoke()
    {
        Js::addLight('UserSettings.js');

        parent::__invoke();
    }
    public function __construct()
    {
        $this->addFields(

            F::createString('Name', [
                'name' => Config::$lang === 'ru' ? 'Имя, Фамилия' : 'Name',
                'editMandatory' => true,
                'editTagParams' => 'required',
            ]),

            /*
            F::createString('Login', [
                'name' => Config::$lang === 'ru' ? 'Логин' : 'Login',
                'editMandatory' => true,
            ]),
            */

            F::createString('Email', [
                'name' => 'E-mail',
                'getHtmlFunction' => function ($value, array $data, $outHtml) {
                    if (!$value) {
                        return '';
                    }

                    $href = 'mailto:' . $value;
                    $params = [];
                    if (isset($_GET['MailSubject'])) {
                        $params[] = 'subject=' . rawurlencode($_GET['MailSubject']);
                    }
                    if (isset($_GET['MailBody'])) {
                        $params[] = 'body=' . rawurlencode($_GET['MailBody']);
                    }
                    if ($params) {
                        $href .= '?' . implode('&', $params);
                    }
                    return '<a href="' . $href . '">' . $outHtml . '</a>';
                },
            ]),

            F::createInt('InnerPhone', [
                'name' => Config::$lang === 'ru' ? 'Добавочный' : 'Desk Phone Ext.',
            ]),

            F::createString('Mobile', [
                'name' => Config::$lang === 'ru' ? 'Мобильный' : 'Mobile Phone',
            ]),

            F::createString('Position', [
                'name' => Config::$lang === 'ru' ? 'Должность' : 'Position',
            ]),

            F::createText('EmailSign', [
                'name' => Config::$lang === 'ru' ? 'Подпись в почте' : 'E-mail Sign',
                'editWidth' => 'full',
                'rows' => 8,
            ]),

            F::createInt('BirthDay', [
                'name' => Config::$lang === 'ru' ? 'День (число) рождения' : 'Day of Birth',
                'placeholder' => Config::$lang === 'ru' ? 'Число' : 'Day',
                'inputType' => 'number',
            ]),

            F::createSelect('BirthMonth', [
                'name' => Config::$lang === 'ru' ? 'Месяц рождения' : 'Month of Birth',
                'emptyText' => Config::$lang === 'ru' ? 'Месяц' : 'Month',
                'options' => function () {
                    $options = [];
                    for ($m = 1; $m <= 12; $m++) {
                        $text = Config::$lang === 'ru' ? Translate::monthRu($m) : (new \DateTime(date('Y') . "-$m-01"))->format('M');
                        $options[$m] = $text;
                    }
                    return $options;
                },

            ])
        );
    }

    protected function loadData()
    {
        $sqlSelect = $this->fieldset->getSqlSelect();
        $this->data = Db::getRow("SELECT $sqlSelect FROM user WHERE ID = @i", User::getId());
    }

    protected function getDataFromPost(): array
    {
        $dataFromRequest = parent::getDataFromPost();
        if (empty($dataFromRequest['Name'])) {
            $msg = Config::$lang === 'ru' ? 'Не заполнено имя' : 'Empty name';
            Session::flashError($msg);
            Response::abort(400);
            return [];
        }
        return $dataFromRequest;
    }

    protected function updateDb(array $dataFromRequest)
    {
        $obj = new SimpleObj();
        $obj->addFields($this->getFieldset());
        $obj->setDbTable('user');
        $obj->setMasterModuleForDbLog(UserModule::class);
        $obj->setId(User::getId());
        $obj->updateDb($dataFromRequest, 'Обновление из экрана настроек пользователя');
    }

    protected function getTemplateData(): array
    {
        $tdata = parent::getTemplateData();
        $avatar = User::avatarImgHtml(User::getId());
        $tdata['Avatar'] = $avatar;
        return $tdata;
    }
}
