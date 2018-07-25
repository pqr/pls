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
                }
            ]),
            F::createString('From'),
            F::createText('To', ['sqlSelect' => "REPLACE(`To`, ',', '\\n')"]),
            F::createText('Cc', ['sqlSelect' => "REPLACE(`Cc`, ',', '\\n')"]),
            F::createText('Bcc', ['sqlSelect' => "REPLACE(`Bcc`, ',', '\\n')"]),
            F::createString('ReplyTo'),
            F::createString('Subject'),
            F::createText('Attachements'),
            F::createString('Body', ['modes' => F::MODE_VIEW]),
            F::createString('ContentType'),
            F::createInt('RetryCount', ['tableAlign' => 'center', 'cssClassZero' => 'bad'])

        );

    }

    protected function viewGetShowBody()
    {
        $body = $this->_obj['Body'];
        if ($this->_obj['ContentType'] !== 'text/html') {
            $body = nl2br($body);
        }
        print $body;
        ApplicationAbstract::terminate();
    }

}
