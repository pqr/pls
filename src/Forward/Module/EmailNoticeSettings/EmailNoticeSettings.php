<?php
namespace Forward\Module\EmailNoticeSettings;

use Light\ApplicationAbstract;
use Light\Field\FieldAbstract as F;
use Light\Fieldset;
use Light\Response;
use Light\Session;
use Light\Template;

class EmailNoticeSettings
{
    private function getFieldset()
    {
        return new Fieldset(

            F::createString('EmailNoticeSubject', ['name' => 'Subject']),

            F::createText('EmailNoticeBody', ['name' => 'Body', 'rows' => 20])

        );
    }

    public function proceedSubmit()
    {
        $fieldset = $this->getFieldset();
        $data = $fieldset->getDataFromRequest($_POST);

        ApplicationAbstract::setParam('EmailNoticeSubject', $data['EmailNoticeSubject']);
        ApplicationAbstract::setParam('EmailNoticeBody', $data['EmailNoticeBody']);

        Session::flashSuccess('Email notice settings updated');
        Response::redirect('EmailNoticeSettings');
    }

    public function __invoke()
    {
        Response::setPageHeadHtml('<strong>E-Mail Notice Settings</strong>');
        Response::setPageTitle('E-Mail Notice Settings');

        $data = [
            'EmailNoticeSubject' => ApplicationAbstract::getParam('EmailNoticeSubject'),
            'EmailNoticeBody' => ApplicationAbstract::getParam('EmailNoticeBody')
        ];

        $fieldset = $this->getFieldset();
        $tdata = $fieldset->getTplData($data, F::MODE_EDIT);
        Template::includeTpl('EmailNoticeSettings.tpl.php', $tdata);
    }
}


