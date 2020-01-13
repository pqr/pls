<?php

namespace Light\Module\EmailLogModule;


use Light\ApplicationAbstract;
use Light\Db;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;

class EmailLogModule extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->setDbTable('log_email');

        $this->setNameFieldKey('ID');

        $this->allowAdd = false;
        $this->allowEdit = false;
        $this->allowLog = false;
        $this->allowDelete = false;

        $this->addFieldsCreatedBy();

        $this->addFields(

            CF::id(),
            F::createDateTime('SentDateTime'),
            F::createString('From'),
            F::createSelect('EmailAccountID', [
                'name' => 'Email Account',
                'options' => "SELECT ID, Username FROM emailaccount",
            ]),
            F::createText('To', ['sqlSelect' => "REPLACE(`To`, ',', '\\n')"]),
            F::createText('Cc', ['sqlSelect' => "REPLACE(`Cc`, ',', '\\n')"]),
            F::createText('Bcc', ['sqlSelect' => "REPLACE(`Bcc`, ',', '\\n')"]),
            F::createString('ReplyTo'),
            F::createString('Subject'),
            F::createString('ContentType'),
            F::createInt('RetryCount', [
                'name' => 'Кол-во попыток отправки',
                'tableAlign' => 'center'
            ]),
            F::createText('Attachements'),
            F::createHtml('Report', [
                'name' => 'Отчёт о попытках отправки',
                'modes' => F::MODE_VIEW,
                'sqlSelect' => "SELECT Report FROM log_email_text let WHERE let.ID = log_email.ID",
            ])

        );

    }

    public static function proceedGetBody()
    {
        $id = (int)$_GET['id'];
        $body = Db::getOne("SELECT Body FROM log_email_text WHERE ID = $id");
        $contentType = Db::getOne("SELECT ContentType FROM log_email WHERE ID = $id");
        if ($contentType !== 'text/html') {
            $body = nl2br($body);
        }
        print $body;
        ApplicationAbstract::terminate();
    }

}
