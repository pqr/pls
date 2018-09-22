<?php


namespace Light\Module\SettingsBackup;


use Light\Field\FieldAbstract as F;
use Light\Link;
use Light\ModuleBase\SettingsPageAbstract;

class SettingsBackup extends SettingsPageAbstract
{
    const PARAM_DIR = 'BackupDir';
    const PARAM_PASS = 'BackupPass';
    const PARAM_MAIL_TO = 'BackupMailTo';

    public function __construct()
    {
        $this->addFields(
            F::createString(self::PARAM_DIR, [
                'name' => 'Backup Dir',
                'placeholder' => '/path/to/folder'
            ]),
            F::createString(self::PARAM_PASS, [
                'name' => 'Password for 7z file',
                'editTagParams' => 'autocomplete="off"'
            ]),
            F::createString(self::PARAM_MAIL_TO, [
                'name' => 'E-mail To',
                'placeholder' => 'to_backup@example.com',
            ])
        );
    }

    protected function getDescription(): string
    {
        return Link::create('Autolaunch', 'Run Backup >>')
            ->params(['class' => 'Light\Autolaunch\BackupMysqlData'])
            ->getHtml();
    }


}