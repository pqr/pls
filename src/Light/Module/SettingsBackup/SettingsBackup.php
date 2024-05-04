<?php


namespace Light\Module\SettingsBackup;


use Light\Field\FieldAbstract as F;
use Light\Link;
use Light\Module\Autolaunch\Autolaunch;
use Light\ModuleBase\SettingsPageAbstract;

class SettingsBackup extends SettingsPageAbstract
{
    public const PARAM_DIR = 'BackupDir';
    public const PARAM_PASS = 'BackupPass';
    public const PARAM_MAIL_TO = 'BackupMailTo';

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

    #[\Override]
    protected function getDescription(): string
    {
        return Link::create(Autolaunch::class, 'Run Backup >>')
            ->params(['class' => \Light\Autolaunch\BackupMysqlData::class])
            ->getHtml();
    }


}