<?php


namespace Light\ModuleBase\Actions;


use Light\Config;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Fieldset;
use Light\FilesFolder;
use Light\Ico;
use Light\Link;
use Light\Mailer;
use Light\Misc;
use Light\Msg;
use Light\ObjAbstract;
use Light\Request;
use Light\Response;
use Light\Session;
use Light\Template;
use Light\Traits\FieldsetAwareTrait;
use Light\User;

/**
 * Абстрактный класс для формирования диалога отправки E-mail из режима просмотра какого-то объекта
 */
abstract class EmailDialogActionAbstract extends ActionAbstract
{
    use FieldsetAwareTrait;
    /** @var Fieldset */
    protected $fieldset;

    public const ICON_DEFAULT = 'icon-mail';
    public const ICON_RED = 'icon-red';
    public const ICON_BLUE = 'icon-blue';
    public const ICON_GREEN = 'icon-green';

    public $onlyAjax = true;
    public $submenuEmailIconColor = self::ICON_DEFAULT;

    public $allowAttachementsFromFolder = true;
    public $allowAttachementsManual = true;

    public function __construct(ObjAbstract $obj, Msg $msgBuff)
    {
        parent::__construct($obj, $msgBuff);
        $this->fieldset = new Fieldset();
    }

    public function getSubmenuHtml(): string
    {
        return Ico::i('fa-envelope ' . $this->submenuEmailIconColor) . ' ' . $this->submenuTitle;
    }

    public function __invoke(): void
    {
        if (!Request::isAjax()) {
            Response::redirect(Misc::getModule($this->obj), ['id' => $this->obj->getId()]);
            return;
        }

        if (Request::isPost()) {
            Session::$showAsToast = true;
            $this->proceedSendSubmit();
            return;
        }

        $this->proceedShowDialog();
    }

    public function getFieldset()
    {
        return $this->fieldset;
    }

    protected function initFieldset(): void
    {
        $emailAccountIdOptions = $this->getEmailAccountIdOptions();

        $this->addFields(

            F::createSelect('EmailAccountID', [
                'name' => Config::$lang === 'ru' ? 'От имени' : 'From',
                'editTagParams' => 'required',
                'editMandatory' => true,
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
        $userEmail = User::getEmail();
        if ($userEmail) {
            $emailAccountId = Db::getOne("SELECT ID FROM emailaccount WHERE Username = @s", $userEmail);
            if ($emailAccountId) {
                // Для данного пользователя существует E-mail аккаунт, его и вернём в качестве
                // единственно правильной опции
                return [
                    $emailAccountId => $userEmail,
                ];
            }

            // Если же для данного пользователя не существует E-mail аккаунта, то, возможно в рамках данного
            // приложения можно подставлть любой From даже без наличия E-mail аккаунта?
            if (Config::$emailAllowCustomFrom) {
                // В качестве ключа используем не числовое значение, а строковое - сам Email
                return [
                    $userEmail => $userEmail,
                ];
            }
        }

        // Не удалось сформировать список из E-mail'а самого пользователя, подставляем аккаунт по умолчанию
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
                                '(посмотреть ' . Ico::externalLink() . ')')
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
        $module = Misc::getModule($this);
        $actionClass = Misc::getClass($this);
        $id = $this->obj->getId();
        return Link::create($module)
            ->view($id)
            ->action($actionClass)
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

        $emailAccountId = $formData['EmailAccountID'];
        // Проверим, допустим ли такой EmailAccountID? Чтобы на форме не подменяли на чужой
        $availableEmailAccoutnIds = $this->getEmailAccountIdOptions();
        if (!isset($availableEmailAccoutnIds[$emailAccountId])) {
            $this->msgBuff->addError('Не допустимое значение EmailAccountID = ' . htmlspecialchars((string) $emailAccountId));
        }

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

        $emailTo = trim((string) $formData['EmailTo']);
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

                $dbLogMsg = nl2br((string) $oneMsgBuff->getText());
                $this->obj->writeDbLogText($dbLogMsg, Misc::getClass($this));

            } catch (\Throwable $e) {

                $this->msgBuff->addError($e->getMessage());
            }
        }

        $this->afterSendAll($sentCount);

        return $sentCount > 0;
    }

    /**
     * Метод для переопределения в классах потомках
     */
    protected function afterSendAll(int $sentCount)
    {
    }

    protected function sendOneEmail(string $to, array $formData, array $attachementsFullPaths, Msg $oneMsgBuff): bool
    {
        $mail = Mailer::create($to, $formData['EmailSubject'], nl2br(htmlspecialchars((string) $formData['EmailBody'])))
            ->cc($formData['EmailCc'])
            ->replyTo($formData['EmailReplyTo'])
            ->attachements($attachementsFullPaths);

        // В качестве E-mail аккаунта может прийти и строковый вариант - это значит отправляем от имени пользователя
        // без авторизации на smtp.
        $emailAccountId = $formData['EmailAccountID'];
        if (is_numeric($emailAccountId)) {
            $mail->emailAccountId($emailAccountId);
        } else {
            $mail->customFrom($emailAccountId);
        }

        $queueId = $mail->send($oneMsgBuff);

        return (bool)$queueId;
    }

    protected function getAttachementsFromFolderFullPaths(array $formData): array
    {
        if (!isset($formData['EmailAttachementsFromFolder']) || !$formData['EmailAttachementsFromFolder']) {
            return [];
        }
        $attachementsFromFolder = explode('|', (string) $formData['EmailAttachementsFromFolder']);
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
                    \Light\ErrorHandler::reportException(new \Exception('Ошибка при перемещении загруженного файла через диалог отправки E-mail ' . $filepathAbs . ' ' . $errMsg));
                }
                $msg = Config::$lang === 'ru' ? 'Не удалось загрузить вложение' : 'Can\'t upload attachement';
                $msg .= ' ' . htmlspecialchars((string) $name);
                $oneMsgBuff->addError($msg);

            }

        }

        return $out;
    }
}
