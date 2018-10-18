<?php


namespace Light\ModuleBase;


use Light\Config;
use Light\Field\FieldAbstract;
use Light\Field\FieldAbstract as F;
use Light\Img;
use Light\Link;
use Light\ObjAbstract;
use Light\Response;
use Light\Table;
use Light\User;

trait FilesDialogTrait
{
    protected function filesDialogButtonText(): string
    {
        return Config::$lang === 'ru' ? 'Описания файлов' : 'Files Descriptions';
    }

    protected function filesDialogSubmenuItemHtml(): string
    {
        $btnText = $this->filesDialogButtonText();
        return Link::create($this->getModule(), Img::icon('fa-bars') . " $btnText")
            ->view($this->_obj->getId())
            ->func('FilesDialog')
            ->ajax()
            ->post()
            ->getHtml();
    }

    /**
     * Метод вызывается, когда пользователь кликает по кнопке "Описания файлов"
     */
    protected function viewPostFilesDialog()
    {
        /** @var ObjAbstract $masterObj */
        $masterObj = $this->_obj;

        if (!$this->hasWriteAccessRightToObj($masterObj)) {
            Response::abort(403);
            return;
        }

        // Перед тем как показывать диалог пользователю, синхронизируем файлы в базе и в папке
        // а отображение данных будет происходить уже исключительно по записям в базе
        $manager = $this->filesDialogManager($masterObj->getId());
        $manager->syncDbWithFolder();

        $formActionUrl = Link::create($this->getModule())
            ->view($masterObj->getId())
            ->func('FilesDialogSubmit')
            ->ajax()
            ->getHref();

        $html = '<form action="' . $formActionUrl . '" method="post">'
            . $this->filesDialogTableHtml($masterObj)
            . '</form>';

        $btnSaveText = Config::$lang === 'ru' ? 'Сохранить' : 'Save';
        Response::showDialog($html, $btnSaveText);
    }

    protected function massactionFilesDialog(array $ids)
    {
        if (!$this->allowEdit || !User::hasWriteAccess()) {
            MassactionUtils::sendErrorDialog('No access to edit');
            return;
        }

        $formActionUrl = Link::create($this->getModule())
            ->params(['p' => 'FilesDialogSubmitFromMassaction'])
            ->ajax()
            ->getHref();

        $html = '<form action="' . $formActionUrl . '" method="post">';
        foreach ($ids as $id) {
            /** @var ObjAbstract $masterObj */
            $masterObj = clone $this->_obj;
            $masterObj->setId($id);
            if (!$masterObj->loadData()) {
                continue;
            }

            if (!$this->hasWriteAccessRightToObj($masterObj)) {
                continue;
            }

            // Перед тем как показывать диалог пользователю, синхронизируем файлы в базе и в папке
            // а отображение данных будет происходить уже исключительно по записям в базе
            $manager = $this->filesDialogManager($masterObj->getId());
            $manager->syncDbWithFolder();

            $html .= $this->filesDialogTableHtml($masterObj);

        }
        $html .= '</form>';

        $btnSaveText = Config::$lang === 'ru' ? 'Сохранить' : 'Save';
        Response::showDialog($html, $btnSaveText, null, null, null, null, '95%', 10);
    }

    private function filesDialogTableHtml(ObjAbstract $masterObj): string
    {
        $folderLink = $this->fieldFolder()->getHtml('', $masterObj->getData());
        $table = $this->filesDialogTable($masterObj->getId());

        return '<h3>' . $folderLink . ' ' . $masterObj->getDescriptionHtml() . '</h3>'
            . $table->getHtmlTable(false);
    }

    protected function viewPostFilesDialogSubmit()
    {
        /** @var ObjAbstract $masterObj */
        $masterObj = $this->_obj;

        if (!$this->hasWriteAccessRightToObj($masterObj)) {
            Response::abort(403);
            return;
        }

        $this->filesDialogSubmitOneMaster($masterObj);

        $msg = $this->filesDialogSuccessMessage();
        Response::sendToast('success', $msg);
    }

    public function proceedFilesDialogSubmitFromMassaction()
    {
        $ids = array_keys($_POST['FilesDialogTable'] ?? []);

        foreach ($ids as $id) {
            /** @var ObjAbstract $masterObj */
            $masterObj = clone $this->_obj;
            $masterObj->setId($id);
            if (!$masterObj->loadData()) {
                continue;
            }

            if (!$this->hasWriteAccessRightToObj($masterObj)) {
                continue;
            }

            $this->filesDialogSubmitOneMaster($masterObj);
        }

        $msg = $this->filesDialogSuccessMessage();
        MassactionUtils::sendSuccessDialog($msg);
    }

    private function filesDialogSuccessMessage(): string
    {
        return Config::$lang === 'ru' ? 'Описания файлов сохранены' : 'Files Descriptions Saved';
    }

    /**
     * Этот метод вызывается как из Multiple диалога, так из карточки View
     * @param ObjAbstract $masterObj
     * @throws \Exception
     */
    private function filesDialogSubmitOneMaster(ObjAbstract $masterObj)
    {
        $masterId = $masterObj->getId();
        $rowsDataFromPost = $_POST['FilesDialogTable'][$masterId] ?? [];
        if (!$rowsDataFromPost) {
            return;
        }

        // Подготовим специальный объект для записи в базу, чтобы одновременно и логи записались правильно
        $manager = $this->filesDialogManager($masterId);
        foreach ($rowsDataFromPost as $base64 => $rowDataFromPost) {
            $relativePath = base64_decode($base64);
            $manager->updateFromDialog($relativePath, $rowDataFromPost);
        }
    }

    /** @var FilesManagerAbstract */
    private $filesManager = [];

    public function filesDialogManager(int $masterId): FilesManagerAbstract
    {
        if (!isset($this->filesManager[$masterId])) {
            // ['Basis', 'Module', 'Shipment', 'Shipment']
            $parts = explode('\\', get_class($this));
            $parts[3] = 'FilesManager';
            $class = implode('\\', $parts);
            $this->filesManager[$masterId] = new $class($masterId);
        }

        return $this->filesManager[$masterId];
    }

    private function filesDialogTable(int $masterId): Table
    {
        $manager = $this->filesDialogManager($masterId);

        $table = new Table("FilesDialogTable$masterId");
        $table->addFields(
            $manager->getFieldset()
        );

        foreach ($table->getFieldset() as $field) {
            if ($field->hasMode(F::MODE_EDIT)) {
                /** @var FieldAbstract $cloneField */
                $cloneField = clone $field;
                $field->setGetHtmlFunction(function ($value, array $data) use ($masterId, $cloneField) {
                    $base64 = base64_encode($data['RelativePath']);
                    $cloneField->setEditId("FilesDialogTable-$masterId-$base64-$cloneField->key");
                    $cloneField->setEditName("FilesDialogTable[$masterId][$base64][$cloneField->key]");
                    return $cloneField->getHtmlInput($value, $data);
                });
            }
        }

        $data = array_values($manager->loadFromDb());
        $table->setTableData($data);
        $table->tableEmptyText = Config::$lang === 'ru' ? 'В папке нет файлов' : 'No files';

        return $table;
    }
}