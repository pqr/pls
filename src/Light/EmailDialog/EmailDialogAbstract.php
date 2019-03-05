<?php


namespace Light\EmailDialog;


use Light\Config;
use Light\Field\FieldAbstract as F;
use Light\Fieldset;
use Light\FilesFolder;
use Light\Img;
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

    public $allowAttachementsFromFolder = true;
    public $allowAttachementsManual = true;

    public function __construct(ObjAbstract $obj, Msg $msgBuff)
    {
        $this->obj = $obj;
        $this->msgBuff = $msgBuff;
        $this->fieldset = new Fieldset();
    }

    public function getFieldset()
    {
        return $this->fieldset;
    }

    protected function getIcon(): string
    {
        return Img::icon('fa-email');
    }

    protected function getTitle(): string
    {
        return 'E-mail';
    }

    protected function initFieldset(): void
    {
        $emailAccountIdOptions = $this->getEmailAccountIdOptions();

        $this->addFields(

            F::createSelect('EmailAccountID', [
                'name' => Config::$lang === 'ru' ? 'От имени' : 'From',
                'editTagParams' => 'required',
                'editMandatory' => true,
                'nullable' => true,
                'options' => $emailAccountIdOptions,
                'default' => $this->getDefaultEmailAccountId($emailAccountIdOptions),
                'empty' => count($emailAccountIdOptions) !== 1,
            ]),

            F::createText('EmailTo', [
                'name' => Config::$lang === 'ru' ? 'Кому' : 'To',
                'default' => $this->getDefaultTo(),
                'editTagParams' => 'required',
                'editMandatory' => true,
                'editWidth' => 'full',
                'rows' => 2,
            ]),

            F::createCheckbox('EmailSeparateTo', [
                'name' => Config::$lang === 'ru' ? 'Каждому получателю отдельное письмо (адреса получателей должны быть разделены запятыми)' : 'Separate E-mail for each "To" (separated by coma)',
                'default' => $this->getDefaultEmailSeparateTo(),
            ]),

            F::createText('EmailCc', [
                'name' => Config::$lang === 'ru' ? 'Копия' : 'Cc',
                'default' => $this->getDefaultCc(),
                'editWidth' => 'full',
                'rows' => 2,
            ]),

            F::createString('EmailReplyTo', [
                'name' => Config::$lang === 'ru' ? 'Адрес для ответа' : 'Reply to',
                'default' => $this->getDefaultReplyTo(),
            ]),

            F::createText('EmailSubject', [
                'name' => Config::$lang === 'ru' ? 'Тема' : 'Subject',
                'default' => $this->getDefatulSubject(),
                'editTagParams' => 'required',
                'editMandatory' => true,
                'editWidth' => 'full',
                'rows' => 2,
            ]),

            F::createText('EmailBody', [
                'name' => Config::$lang === 'ru' ? 'Сообщение' : 'Body',
                'default' => $this->getDefaultBody(),
                'rows' => 15,
                'editTagParams' => 'style="width: 800px"',
            ])
        );

        $this->initFieldsetFilesCheckboxes();
    }

    protected function getEmailAccountIdOptions(): array
    {
        return [
            0 => Config::$swiftmailerUser,
        ];
    }

    protected function getDefaultEmailAccountId(array $emailAccountIdOptions)
    {
        return count($emailAccountIdOptions) === 1 ? key($emailAccountIdOptions) : '';
    }

    protected function initFieldsetFilesCheckboxes(): void
    {
        if (!$this->allowAttachementsFromFolder) {
            return;
        }

        $files = $this->getFiles();
        if (!$files) {
            return;
        }

        $this->addField(
            F::createCheckboxSet('EmailAttachementsFromFolder', [
                'name' => Config::$lang === 'ru' ? 'Вложения' : 'Attachements',
                'options' => function () use ($files) {
                    $options = [];
                    foreach ($files as $file) {
                        $options[$file] = $file
                            . ' '
                            . Link::create($this->obj->getMasterModuleForDbLog(),
                                '(посмотреть ' . Img::iconExternalLink() . ')')
                                ->filePreview($this->obj->getId(), $file)
                                ->cssClass('small')
                                ->getHtml();
                    }
                    return $options;
                },
            ])
        );
    }

    protected function getFolderRelative(): string
    {
        return FilesFolder::getRelativeModule($this->obj->getMasterModuleForDbLog(), $this->obj->getId());
    }

    protected function getFiles(): array
    {
        $relative = $this->getFolderRelative();
        return FilesFolder::scandir($relative);
    }

    protected function getDefaultTo(): string
    {
        return '';
    }

    protected function getDefaultEmailSeparateTo(): bool
    {
        return true;
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

    protected function getFormAction(): string
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

    public function proceedShowDialog(): void
    {
        $this->initFieldset();
        $dialogBody = $this->getDialogHtml();
        $sendBtnText = $this->getSendBtnText();

        Response::showDialog($dialogBody, $sendBtnText);
    }

    protected function getDialogHtml(): string
    {
        $action = $this->getFormAction();
        return '<form action="' . $action . '" method="post" enctype="multipart/form-data">'
            . $this->getFormInnerHtml()
            . '</form>';
    }

    protected function getFormInnerHtml(): string
    {
        $tdata = $this->getTplData();
        $template = $this->getTemplate();
        return Template::parseTpl($template, $tdata);
    }

    protected function getTplData(): array
    {
        $tdata = $this->fieldset->getTplData([], F::MODE_EDIT);
        $tdata['action'] = $this->getFormAction();

        if ($this->allowAttachementsFromFolder && !$this->hasField('EmailAttachementsFromFolder')) {
            // Если нет поля отвечающего за аттачменты, значит либо папка пуста, либо папки вообще нет
            $relative = $this->getFolderRelative();
            if ($relative && is_dir(FilesFolder::getAbsolute($relative))) {
                // Если папка на самом есть, выведем сообщение, что "папка пуста"
                $emptyFolderMsg = Config::$lang === 'ru'
                    ? 'Папка с файлами пуста'
                    : 'Empty folder';
                $tdata['emptyFolderMsg'] = FilesFolder::getLinkWithIcon($relative, $emptyFolderMsg);
            }
        }

        $tdata['allowAttachementsManual'] = $this->allowAttachementsManual;

        return $tdata;
    }

    protected function getTemplate(): string
    {
        return __DIR__ . '/EmailDialogForm.tpl.php';
    }

    protected function getSendBtnText(): string
    {
        return Config::$lang === 'ru' ? 'Отправить' : 'Send';
    }

    protected function getDataFromDialog(): array
    {
        return $this->fieldset->getDataFromRequest($_POST);
    }

    public function proceedSendSubmit(): bool
    {
        $this->initFieldset();
        $formData = $this->getDataFromDialog();

        $attachementsFullPaths = [];

        if ($this->allowAttachementsManual) {
            // Возможно, пользователь вручную загрузил какие-то файлы для отправки?
            $attachementsFullPaths = $this->getManualAttachementsFullPaths($this->msgBuff);
        }

        if ($this->allowAttachementsFromFolder) {
            $attachementsFullPaths = array_merge(
                $attachementsFullPaths,
                $this->getAttachementsFromFolderFullPaths($formData)
            );
        }

        $sentCount = 0;

        $emailTo = trim($formData['EmailTo']);
        $toArr = $formData['EmailSeparateTo'] ? Mailer::getEmailsAsArray($emailTo) : [$emailTo];

        if (!$toArr) {
            $msg = Config::$lang === 'ru' ? 'Не заполнено поле "Кому"' : 'Empty "To"';
            $this->msgBuff->addError($msg);
        }

        // Возможно, при загрузке файлов вложений были какие-то ошибки? Тогда не будем отправлять E-mail
        if ($this->msgBuff->hasError()) {
            return false;
        }

        foreach ($toArr as $to) {

            if (!$to) {
                continue;
            }

            try {

                $oneMsgBuff = new Msg();

                $success = $this->sendOneEmail(
                    $to,
                    $formData,
                    $attachementsFullPaths,
                    $oneMsgBuff
                );

                if ($success) {
                    $sentCount++;
                }

                $this->msgBuff->merge($oneMsgBuff);

                $dbLogMsg = nl2br($oneMsgBuff->getText());
                $this->obj->writeDbLogText($dbLogMsg, Misc::getClass($this));

            } catch (\Throwable $e) {

                $this->msgBuff->addError($e->getMessage());
            }
        }

        return $sentCount > 0;
    }

    protected function sendOneEmail(string $to, array $formData, array $attachementsFullPaths, Msg $oneMsgBuff): bool
    {
        $queueId = Mailer::create($to, $formData['EmailSubject'], $formData['EmailBody'])
            ->emailAccountId($formData['EmailAccountID'])
            ->cc($formData['EmailCc'])
            ->replyTo($formData['EmailReplyTo'])
            ->attachements($attachementsFullPaths)
            ->asText()
            ->send($oneMsgBuff);

        return (bool)$queueId;
    }

    protected function getAttachementsFromFolderFullPaths(array $formData): array
    {
        if (!isset($formData['EmailAttachementsFromFolder']) || !$formData['EmailAttachementsFromFolder']) {
            return [];
        }
        $attachementsFromFolder = explode('|', $formData['EmailAttachementsFromFolder']);
        $validFiles = $this->getFiles();
        $validAttachements = array_intersect($attachementsFromFolder, $validFiles);
        if ($validAttachements) {

            $relative = $this->getFolderRelative();
            foreach ($validAttachements as &$attachement) {
                // Сделаем пути абсолютными
                $attachement = FilesFolder::getAbsolute("$relative/$attachement");
            }
        }

        return $validAttachements;

    }

    protected function getManualAttachementsFullPaths(Msg $oneMsgBuff): array
    {
        if (!isset($_FILES['ManualAttachements']) || !$_FILES['ManualAttachements']) {
            return [];
        }

        $queueDirRelative = 'EmailQueue/' . time() . '_' . User::getId();
        if (!FilesFolder::mkdir($queueDirRelative)) {
            $msg = Config::$lang === 'ru' ? 'Не удалось загрузить вложения' : 'Can\'t upload attachements';
            $oneMsgBuff->addError($msg);
            return [];
        }

        $out = [];

        $queueDirAbs = FilesFolder::getAbsolute($queueDirRelative);
        foreach ($_FILES['ManualAttachements']['name'] as $index => $name) {

            if (!$name) {
                continue;
            }

            $filepathAbs = "$queueDirAbs/" . $name;
            if (move_uploaded_file($_FILES['ManualAttachements']['tmp_name'][$index], $filepathAbs)) {

                $out[] = $filepathAbs;

            } else {

                $err = error_get_last();
                if ($err) {
                    $errMsg = $err['message'] ?? '';
                    \Light\reportException(new \Exception('Ошибка при перемещении загруженного файла через диалог отправки E-mail ' . $filepathAbs . ' ' . $errMsg));
                }
                $msg = Config::$lang === 'ru' ? 'Не удалось загрузить вложение' : 'Can\'t upload attachement';
                $msg .= ' ' . htmlspecialchars($name);
                $oneMsgBuff->addError($msg);

            }

        }

        return $out;
    }
}
