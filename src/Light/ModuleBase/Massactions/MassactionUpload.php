<?php


namespace Light\ModuleBase\Massactions;


use Light\Config;
use Light\FilesFolder;
use Light\Link;
use Light\ModuleBase\MassactionUtils;
use Light\Msg;
use Light\ObjAbstract;
use Light\Response;
use Light\Session;
use Light\Template;
use Light\User;

trait MassactionUpload
{
    protected function addMassactionUpload()
    {
        if ($this->allowEdit && $this->folders && User::hasWriteAccess()) {
            $this->addMassaction('Upload',
                Config::$lang === 'ru' ? 'Загрузить файлы в папки' : 'Upload files into folders');
        }
    }

    protected function massactionUpload(array $ids)
    {
        $chooseMsg = Config::$lang === 'ru' ? 'Выберите один или несколько файлов' : 'Choose one or many files to upload:';

        $formAction = Link::create($this->getModule())->proceed('MassactionUploadSubmit')->getHref();
        $html = '<form action="' . $formAction . '" method="post" enctype="multipart/form-data">
                     ' . MassactionUtils::getHtmlHiddenCheckedIds($ids) . '
                     ' . Template::csrfHidden() . '
                     <p>' . $chooseMsg . ':</p>
                      <input type="file" name="massactionupload[]" multiple>
                     </form>';

        $buttonOkText = Config::$lang === 'ru' ? 'Загрузить во все папки' : 'Upload into all folders';
        $buttonOkText .= ' (' . count($ids) . ')';

        Response::showDialog($html, $buttonOkText, null, null, null, 500);
    }

    public function proceedMassactionUploadSubmit()
    {
        if (!$this->allowEdit || !$this->folders || !User::hasWriteAccess()) {
            Session::flashError('No access to upload');
            $this->redirectToList();
        }

        $msgBuff = new Msg();
        $uploaded = 0;

        $ids = $this->getMassactionIdsFromPost();

        $objBase = $this->getObj();
        foreach ($ids as $id) {

            /** @var ObjAbstract $obj */
            $obj = clone $objBase;
            $obj->setId($id);
            if (!$obj->loadData()) {
                $msgBuff->addError("Object with ID = $id not found");
                continue;
            }

            // Проверим, что у пользователя есть права доступа к этому объекту
            if (!$this->hasAccessRightToObj($obj)
                || !$this->hasWriteAccessRightToFolder($obj)
            ) {
                $msgBuff->addError("No access to edit object with ID = $id");
                continue;
            }

            $objData = $obj->getData();
            $this->createFolder($objData);
            $abs = $this->getFolderAbsolute($objData);
            foreach ($_FILES['massactionupload']['tmp_name'] as $i => $tmpName) {
                if ($tmpName === '') {
                    continue;
                }
                $dest = "$abs/" . $_FILES['massactionupload']['name'][$i];
                if (!copy($tmpName, $dest)) {
                    $msg = Config::$lang === 'ru' ? 'Ошибка при копировании файла в' : 'Unable to copy into';
                    $msg .= ': ' . htmlspecialchars($dest);
                    $msgBuff->addError($msg);
                } else {
                    $uploaded++;
                }
            }

        }

        foreach ($_FILES['massactionupload']['tmp_name'] as $tmpName) {
            unlink($tmpName);
        }

        // Стандартное сообщение о числе загруженных копий файлов
        $msg = Config::$lang === 'ru' ? 'Загружено копий файлов' : 'Uploaded';
        $msg .= ": $uploaded";
        $msgBuff->addSuccessOrInfo($msg, $uploaded);

        $msgBuff->putIntoSessionFlash();
        $this->redirectToList();
    }
}
