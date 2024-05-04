<?php


namespace Light\ModuleBase;


use Light\Config;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\FilesFolder;
use Light\Link;
use Light\Misc;
use Light\ObjAbstract;
use Light\Pdf;
use Light\SimpleObj;
use Light\Traits\FieldsetAwareTrait;
use Symfony\Component\Filesystem\Filesystem;

class FilesManagerAbstract
{
    use FieldsetAwareTrait;

    /** @var int */
    protected $masterId;

    /** @var string */
    private $module;

    /** @var Filesystem */
    protected $fs;

    /** @var ObjAbstract */
    protected $obj;

    public function __construct(int $masterId)
    {
        $this->masterId = $masterId;
        $this->module = $this->getModule();
        $this->fs = new Filesystem();
        $this->obj = $this->createObj();
    }

    protected function createObj(): ObjAbstract
    {
        $obj = new SimpleObj();
        $obj->addFields(
            F::createString('RelativePath', [
                'name' => Config::$lang === 'ru' ? 'Файл' : 'File',
                'modes' => F::MODE_READONLY,
                'getHtmlFunction' => fn($value, array $data, $outHtml) => Link::create($this->module, $outHtml)
                    ->filePreview($this->masterId, $value)
                    ->getHtml()
            ]),
            F::createString('Name', [
                'name' => Config::$lang === 'ru' ? 'Описание' : 'Description',
            ]),
            F::createInt('Pages', [
                'name' => Config::$lang === 'ru' ? 'Страниц' : 'Pages',
                'tableAlign' => 'center',
                'modes' => F::MODE_READONLY,
            ]),
            F::createCheckbox('FileDeleted', [
                'name' => 'Удалён с диска',
                'modes' => F::MODE_EDITSUBMIT | F::MODE_LOG
            ])
        );

        $obj->setDbTable($this->getDbTable());
        $obj->setMasterIdForDbLog($this->masterId);
        $obj->setMasterModuleForDbLog($this->module);
        $obj->setNameFieldKey('RelativePath');

        return $obj;
    }

    public function getFieldset()
    {
        return $this->obj->getFieldset();
    }

    /**
     * Можно переопределить в классе потомке, если он лежит в каком-то особом неймспейсе и стандартный алгоритм не подходит
     * @return string
     * @throws \Exception
     */
    protected function getModule(): string
    {
        return Misc::getModule($this);
    }

    protected function getDbTable(): string
    {
        return strtolower($this->module . 'file');
    }

    protected function getMasterFieldKey(): string
    {
        return $this->module . 'ID';
    }

    public function syncDbWithFolder()
    {
        $filesFromDb = $this->loadFromDb();
        $filesFromFolder = $this->loadFromFolder();

        $inserted = 0;
        $deleted = 0;

        $newFilesFromFolder = array_diff_key($filesFromFolder, $filesFromDb);
        if ($newFilesFromFolder) {
            $inserted += $this->saveNewFilesFromFolder($newFilesFromFolder);
        }

        $toBeDeletedFilesFromDb = array_diff_key($filesFromDb, $filesFromFolder);
        if ($toBeDeletedFilesFromDb) {
            $deleted += $this->deleteFilesFromDb($toBeDeletedFilesFromDb);
        }

        return [$inserted, $deleted];
    }

    public function loadFromDb(): array
    {
        $table = $this->getDbTable();
        $masterFieldKey = $this->getMasterFieldKey();
        return Db::getAssoc(
            "SELECT RelativePath AS k
                      , $table.* 
                   FROM $table 
                   WHERE $masterFieldKey = $this->masterId 
                      AND FileDeleted = 0
                   ORDER BY RelativePath"
        );
    }

    protected function loadFromFolder(): array
    {
        $masterFolderAbs = $this->getMasterFolderAbs();
        if (!is_dir($masterFolderAbs)) {
            return [];
        }

        $out = [];

        $flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_FILEINFO;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($masterFolderAbs, $flags),
            \RecursiveIteratorIterator::SELF_FIRST);
        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $filename = $file->getFilename();
            if ($filename[0] === '.') {
                // это какой-то странный скрытый файл, начинающийся с точки
                continue;
            }

            $filepathAbs = $file->getRealPath();

            $filepathRelative = trim($this->fs->makePathRelative($filepathAbs, $masterFolderAbs), '/');

            $out[$filepathRelative] = $filepathRelative;
        }

        asort($out);

        return $out;
    }

    /**
     * Можно переопределить в классе потомке, если файлы этого модуля лежат по какому-то особому пути
     * @return string
     */
    protected function getMasterFolderAbs(): string
    {
        return FilesFolder::getAbsoluteModule($this->module, $this->masterId);
    }

    protected function saveNewFilesFromFolder(array $newFilesFromFolder)
    {
        $masterFolderAbs = $this->getMasterFolderAbs();
        $inserted = 0;
        foreach ($newFilesFromFolder as $fileRelativePath) {

            $obj = clone $this->obj;

            $masterFieldKey = $this->getMasterFieldKey();

            $fieldsData = [
                $masterFieldKey => $this->masterId,
                'RelativePath' => $fileRelativePath,
                'Pages' => $this->getNumberOfPages($masterFolderAbs . '/' . $fileRelativePath),
                'FileDeleted' => 0,
            ];

            // Сначала проверим, возможно этот файл уже есть в таблице (с флагом FileDeleted)?
            // Если есть, будем делать обновление
            $existId = Db::getOne(
                "SELECT ID 
                FROM {$this->getDbTable()} 
                WHERE $masterFieldKey = {$this->masterId}
                    AND RelativePath = ".Db::escString($fileRelativePath)
            );

            if ($existId) {

                $obj->setId($existId);
                $obj->updateDb($fieldsData, 'Автоматичесное обновление на основе файла из папки');

            } else {

                $fieldsData += $this->autodetectNewFileInfo($fileRelativePath);
                $obj->insertDb($fieldsData, 'Автоматическое добавление нового найденного файла');
                $inserted++;

            }

            $this->afterSubmit($obj);
        }

        return $inserted;
    }

    /**
     * Метод для переопределения в классах потомках - при обнаружении нового файла в папке запускается этот метод
     * чтобы определить какие-то параметры файла (описание, тип документа, различные галочки) по его имени
     * @param string $newFileRelativePath
     * @return array
     */
    protected function autodetectNewFileInfo(string $newFileRelativePath): array
    {
        return [];
    }

    protected function getNumberOfPages(string $filepathAbs): int
    {
        $ext = strtolower(pathinfo($filepathAbs, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            // Для всех типов, кроме PDF считаем, что страница всего одна
            return 1;
        }

        try {
            return Pdf::getNumberOfPages($filepathAbs);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Метод для переопределения в классе потомке
     * @param ObjAbstract $fileObj
     */
    protected function afterSubmit(ObjAbstract $fileObj)
    {

    }

    protected function deleteFilesFromDb(array $toBeDeletedFilesFromDb): int
    {
        $deleted = 0;
        foreach ($toBeDeletedFilesFromDb as $fileRelativePath => $infoFromDb) {
            $id = $infoFromDb['ID'];
            $obj = clone $this->obj;
            $obj->setId($id);
            $affectedRows = $obj->updateDb(['FileDeleted' => 1], 'Автоматическая пометка на удаление файла из таблицы с описаниями, т.к. файл отсутсвует на диске');
            if ($affectedRows) {
                $this->afterDelete($obj);
                $deleted++;
            }
        }
        return $deleted;
    }

    /**
     * Метод для переопределения в классе потомке
     * @param ObjAbstract $fileObj
     */
    protected function afterDelete(ObjAbstract $fileObj)
    {

    }

    /**
     * @param string $relative
     * @param array $dataFromPost сырые данные по текущему файлу из post запроса
     * @return int affectedRows
     * @throws \Exception
     */
    public function updateFromDialog(string $relative, array $dataFromPost): int
    {
        $id = Db::getOne(
            "SELECT ID 
              FROM {$this->getDbTable()} 
              WHERE RelativePath = @s 
                  AND {$this->getMasterFieldKey()} = @i"
            , $relative
            , $this->masterId
        );
        if (!$id) {
            return 0;
        }

        $obj = clone $this->obj;
        $obj->setId($id);
        $fieldsData = $obj->getDataFromRequest($dataFromPost);

        // Оставим только те поля, у которых есть F::MODE_SUBMIT
        $fieldsData = $obj->filterDataByModeSubmit($fieldsData);

        // Проверим по оставшимся полям функцию editAccessFunction
        $fieldsData = $obj->filterDataByEditAccessFunction($fieldsData);

        $affectedRows = $obj->updateDb($fieldsData, 'Обновление из диалога');
        $this->afterSubmit($obj);
        return $affectedRows;
    }
}
