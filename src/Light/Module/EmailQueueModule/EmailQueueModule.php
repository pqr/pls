<?php

namespace Light\Module\EmailQueueModule;


use Light\ApplicationAbstract;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;

class EmailQueueModule extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->setDbTable('queue_email');

        $this->setNameFieldKey('ID');

        $this->allowAdd = false;
        $this->allowEdit = false;
        $this->allowLog = false;
        $this->allowDelete = false;

        $this->addFieldsCreatedBy();

        $this->addFields(

            CF::id(),

            F::createString('PID', [
                'viewCssClass' => function ($value) {
                    return $value ? 'bad' : '';
                },
            ]),
            F::createString('CustomFrom', ['name' => 'Custom From']),
            F::createSelect('EmailAccountID', [
                'name' => 'Email Account',
                'options' => "SELECT ID, Username FROM emailaccount",
            ]),
            F::createText('To'),
            F::createText('Cc'),
            F::createText('Bcc'),
            F::createString('ReplyTo'),
            F::createString('Subject'),
            F::createText('Attachements'),
            F::createString('Body', ['modes' => F::MODE_VIEW]),
            F::createString('ContentType'),
            F::createInt('RetryCount', [
                'name' => 'Кол-во попыток отправки',
                'tableAlign' => 'center',
                'cssClassZero' => 'bad',
            ]),
            F::createHtml('Report', [
                'name' => 'Отчёт о попытках отправки',
                'modes' => F::MODE_VIEW,
            ])

        );

    }

    protected function viewGetShowBody()
    {
        $body = $this->_obj['Body'];
        if ($this->_obj['ContentType'] !== 'text/html') {
            $body = '<pre>' . nl2br(htmlspecialchars($body)) . '</pre>';
        }
        print $body;
        ApplicationAbstract::terminate();
    }

}
