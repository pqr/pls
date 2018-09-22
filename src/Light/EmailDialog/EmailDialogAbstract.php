<?php


namespace Light\EmailDialog;


use Light\Config;
use Light\Field\FieldAbstract as F;
use Light\Fieldset;
use Light\FilesFolder;
use Light\Link;
use Light\Mailer;
use Light\Misc;
use Light\Msg;
use Light\ObjAbstract;
use Light\Response;
use Light\Template;
use Light\Traits\FieldsetAwareTrait;
use Light\User;

/**
 * Абстрактный класс для формирования диалога отправки E-mail из режима просмотра какого-то объекта
 */
abstract class EmailDialogAbstract
{
    use FieldsetAwareTrait;

    /** @var Fieldset */
    protected $fieldset;

    /** @var ObjAbstract */
    protected $obj;
    /**
     * @var Msg
     */
    private $msgBuff;

    public function __construct(ObjAbstract $obj, Msg $msgBuff)
    {
        $this->obj = $obj;
        $this->msgBuff = $msgBuff;

        $this->fieldset = new Fieldset();
        $this->initFieldset();
    }

    public function getFieldset()
    {
        return $this->fieldset;
    }

    protected function initFieldset()
    {
        $this->addFields(

            F::createString('EmailTo', [
                'name' => Config::$lang === 'ru' ? 'Кому' : 'To',
                'default' => $this->getDefaultTo(),
                'editTagParams' => 'required',
            ]),

            F::createCheckbox('EmailSeparateTo', [
                'name' => Config::$lang === 'ru' ? 'Каждому получателю отдельное письмо (адреса получателей должны быть разделены запятыми)' : 'Separate E-mail for each "To" (separated by coma)',
                'default' => true,
            ]),

            F::createString('EmailCc', [
                'name' => Config::$lang === 'ru' ? 'Копия' : 'Cc',
                'default' => $this->getDefaultCc(),
            ]),

            F::createString('EmailReplyTo', [
                'name' => Config::$lang === 'ru' ? 'Адрес для ответа' : 'Reply to',
                'default' => $this->getDefaultReplyTo(),
            ]),

            F::createString('EmailSubject', [
                'name' => Config::$lang === 'ru' ? 'Тема' : 'Subject',
                'default' => $this->getDefatulSubject(),
                'editMandatory' => true,
            ]),

            F::createText('EmailBody', [
                'name' => Config::$lang === 'ru' ? 'Сообщение' : 'Body',
                'default' => $this->getDefaultBody(),
                'rows' => 10,
            ])

        );

        $this->initFieldsetFilesCheckboxes();
    }

    protected function initFieldsetFilesCheckboxes()
    {
        $files = $this->getFiles();
        if (!$files) {
            return;
        }

        $this->addField(
            F::createCheckboxSet('EmailAttahements', [
                'name' => Config::$lang === 'ru' ? 'Вложения' : 'Attahements',
                'options' => array_combine($files, $files)
            ])
        );
    }

    protected function getFolderRelative()
    {
        return FilesFolder::getModuleRelative($this->obj->getMasterModuleForDbLog(), $this->obj->getId());
    }

    protected function getFiles(): array
    {
        $relative = $this->getFolderRelative();
        if (!FilesFolder::isDir($relative)) {
            return [];
        }

        return FilesFolder::scandir($relative);
    }

    protected function getDefaultTo(): string
    {
        return '';
    }

    protected function getDefaultCc(): string
    {
        return User::getEmail();
    }

    protected function getDefaultReplyTo(): string
    {
        return User::getEmail();
    }

    protected function getDefatulSubject(): string
    {
        return '';
    }

    protected function getDefaultBody(): string
    {
        return '';
    }

    protected function getFormAction()
    {
        $module = $this->obj->getMasterModuleForDbLog();
        $id = $this->obj->getId();
        // Подразумеваем, что в модуле будет фунция viewPost{имя класса для отправки E-mail без неймспейса}
        $class = Misc::getClass($this);
        return Link::create($module)
            ->view($id)
            ->func($class)
            ->ajax()
            ->getHref();
    }

    public function proceedShowDialog()
    {
        $dialogBody = $this->getDialogBody();
        $sendBtnText = $this->getSendBtnText();

        Response::showDialog($dialogBody, $sendBtnText);
    }

    protected function getDialogBody()
    {
        $tdata = $this->getTplData();
        $template = $this->getTemplate();
        return Template::parseTpl($template, $tdata);
    }

    protected function getTplData()
    {
        $tdata = $this->fieldset->getTplData([], F::MODE_EDIT);
        $tdata['action'] = $this->getFormAction();

        if (!$this->hasField('EmailAttahements')) {
            // Если нет поля отвечающего за аттачменты, значит либо папка пуста, либо папки вообще нет
            $folderRelative = $this->getFolderRelative();
            if (FilesFolder::isDir($folderRelative)) {
                // Если папка на самом есть, выведем сообщение, что "папка пуста"
                $emptyFolderMsg = Config::$lang === 'ru'
                    ? 'Папка с файлами пуста, поэтому нет вложений'
                    : 'Empty folder, no attachements';
                $tdata['emptyFolderMsg'] = FilesFolder::getLinkWithIcon($folderRelative, $emptyFolderMsg);
            }
        }

        return $tdata;
    }

    protected function getTemplate()
    {
        return __DIR__ . '/EMailDialog.tpl.php';
    }

    protected function getSendBtnText()
    {
        return Config::$lang === 'ru' ? 'Отправить' : 'Send';
    }

    public function proceedSendSubmit()
    {
        $data = $this->fieldset->getDataFromRequest($_POST);

        if (!$data['EmailTo']) {
            $msg = Config::$lang === 'ru' ? 'Не заполнено поле "Кому"' : 'Empty "To"';
            $this->msgBuff->addError($msg);
            return false;
        }

        $sentCount = 0;

        $emailTo = trim($data['EmailTo']);
        $toArr = $data['EmailSeparateTo'] ? Mailer::getEmailsAsArray($emailTo) : [$emailTo];
        foreach ($toArr as $to) {
            if (!$to) {
                continue;
            }

            try {

                $oneMsgBuff = new Msg();

                $sentCount += $this->sendOneEmail($to, $data, $oneMsgBuff);

                $this->msgBuff->merge($oneMsgBuff);

                $dbLogMsg = nl2br($oneMsgBuff->getText());
                $this->obj->writeDbLogText($dbLogMsg, Misc::getClass($this));

            } catch (\Throwable $e) {
                $this->msgBuff->addError($e->getMessage());
            }
        }

        return $sentCount > 0;
    }

    protected function emailsStringToArray(string $emails): array
    {
        return array_values(array_unique(array_filter(array_map('strtolower',
            array_map('trim', explode(',', $emails))))));
    }

    protected function getAttachementsFullPaths(array $formData): array
    {
        if (!isset($formData['EmailAttahements']) || !$formData['EmailAttahements']) {
            return [];
        }
        $formAttachements = explode('|', $formData['EmailAttahements']);
        $validFiles = $this->getFiles();
        $validAttachements = array_intersect($formAttachements, $validFiles);
        if ($validAttachements) {

            $relative = $this->getFolderRelative();
            foreach ($validAttachements as &$attachement) {
                // Сделаем пути абсолютными
                $attachement = FilesFolder::getAbsolutePath($relative . '/' . $attachement);
            }
        }

        return $validAttachements;

    }

    protected function sendOneEmail(string $to, array $formData, Msg $oneMsgBuff): int
    {
        $email = Mailer::create($to, $formData['EmailSubject'], $formData['EmailBody'])
            ->cc($formData['EmailCc'])
            ->replyTo($formData['EmailReplyTo'])
            ->asText();

        $validAttachements = $this->getAttachementsFullPaths($formData);
        if ($validAttachements) {
            $email->attachements($validAttachements);
        }


        $email->send($oneMsgBuff);
        return 1;
    }
}