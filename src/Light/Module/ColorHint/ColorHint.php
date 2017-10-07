<?php
namespace Light\Module\ColorHint;

use Light\ApplicationAbstract;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldColor;
use Light\Fieldset;
use Light\Response;
use Light\Session;
use Light\Template;

class ColorHint
{
    private function getFieldset()
    {
        $fs = new Fieldset();
        for ($i = 1; $i <= 11; $i++) {
            $fs->addField(
                F::create(
                    'ColorHint' . $i,
                    F::TYPE_STRING,
                    ['name' => '<span class="color-span color' . $i . '">' . $i . '</span>', 'editWidth' => 'full']
                )
            );
        }

        return $fs;
    }

    public function __invoke()
    {
        Response::setPageHeadHtml('Color Hints');
        Response::setPageTitle('Color Hints');

        $fs = $this->getFieldset();
        $data = FieldColor::getColorHints();
        $tdata = $fs->getTplData($data, F::MODE_EDIT);
        Template::includeTpl('ColorHint.tpl.php', $tdata);
    }

    public function proceedSave()
    {
        $fs = $this->getFieldset();
        $hints = $fs->getDataFromRequest($_POST);
        ApplicationAbstract::setParam('ColorHints', json_encode($hints));
        Session::flashSuccess('Color Hints saved');
        Response::redirect('ColorHint');
    }
}