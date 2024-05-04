<?php

namespace Light\Traits;

use Light\A;
use Light\Config;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldHtml;
use Light\Fieldset;
use Light\File;
use Light\FilesFolder;
use Light\Ico;
use Light\Link;
use Light\Response;

trait TableWithFoldersTrait
{
    public $folders = false;

    // Во-вторых, нужно указать

    /**
     * Ключ поля, которое искать в массиве $data - там должно храниться id для папки
     * @var string
     */
    public $folderIdFieldKey = 'ID';

    /** @var string */
    public $folderModule = '';

    protected $_cacheNotEmptyFolderIds;

    /**
     * Контракт на класс Table - должен существовать метод getFieldsetFirst()
     * @return Fieldset|F[]
     */
    abstract public function getFieldsetFirst();

    /**
     * Возвращает модуль имя которого будет использоваться в качестве папки. По умолчанию возвращает $this->folderModule
     * но можно переопределить в классе потомке, если нужно вернуть что-то особенное (зависящее от $data - текущего объекта)
     * @return string
     */
    protected function getFolderModule(array $data): string
    {
        return $this->folderModule;
    }

    /**
     * Возвращает ID папки на основе параметра $this->folderIdFieldKey, но может быть переопределено в классе потомке
     * если нужно вычислять ID каким-то хитрым способом
     * @return int
     */
    protected function getFolderId(array $data): int
    {
        // По умолчанию используется алгоритм основанный на ID текущего объекта (от котогоро массив $data)
        return (int)($data[$this->folderIdFieldKey] ?? 0);
    }

    /**
     * @param array|null $data опционально можно передать какие-то данные отличные от $this->_obj
     * Это нужно для вывода в режиме таблицы
     * @return null|string
     */
    protected function getFolderRelative(array $data = null): ?string
    {
        if ($data === null) {
            $data = $this->_obj->getData();
        }

        $module = $this->getFolderModule($data);
        $folderId = $this->getFolderId($data);
        if (!$module || !$folderId) {
            return null;
        }

        return FilesFolder::getRelativeModule($module, $folderId);
    }

    /**
     * Возвращает асболютный путь к папке текущего объекта $this->_obj
     * @param array|null $data
     * @return null|string
     */
    final protected function getFolderAbsolute(array $data = null): ?string
    {
        $relative = $this->getFolderRelative($data);
        if (!$relative) {
            return null;
        }
        return FilesFolder::getAbsolute($relative);
    }

    /**
     * Создаёт папку для текущего объекта (для $this->_obj)
     * @param array|null $data
     */
    protected function createFolder(array $data = null)
    {
        if ($data === null) {
            $data = $this->_obj->getData();
        }
        $relative = $this->getFolderRelative($data);
        if ($relative) {
            FilesFolder::mkdir($relative);
        }
    }

    protected function deleteFolder(array $data = null)
    {
        $relative = $this->getFolderRelative($data);
        if ($relative) {
            // Перемещаем папку в специальную директорию deleted
            FilesFolder::moveToDeleted($relative);
        }
    }

    protected function fieldFolder(): FieldHtml
    {
        return F::createHtml('Folder', [
                'nameTable' => '',
                'tableWidthHtml' => 25,
                'tableAlign' => 'center',
                'tableOrderBy' => '',
                'getHtmlFunction' => function ($value, array $data, $outHtml, $forTable) {

                    if ($forTable === 'total') {
                        return '';
                    }

                    $relative = $this->getFolderRelative($data);
                    if (!$relative) {
                        return '';
                    }

                    $innerHtml = '';
                    if (!$forTable) {
                        $innerHtml = Config::$lang === 'ru' ? 'Файлы' : 'Folder';
                    }

                    $hasFiles = FilesFolder::hasFiles($relative);
                    $icon = Ico::folder($hasFiles);
                    $innerHtml = trim("$icon $innerHtml");
                    $folderModule = $this->getFolderModule($data);
                    $folderId = $this->getFolderId($data);
                    return FilesFolder::getLinkModule($folderModule, $folderId, $innerHtml);
                },
            ]
        );
    }

    protected function fieldFolderHasFiles()
    {
        return F::createSelect('FolderHasFiles', [
            'modes' => F::MODE_LIST,
            'name' => 'Файлы: есть/нет?',
            'sqlSelect' => '',
            'tableAlign' => 'center',
            'tableWidthExcel' => 11,
            'options' => [
                0 => ['text' => 'Нет файлов', 'cssClass' => 'bad'],
                1 => ['text' => 'Есть файлы', 'cssClass' => 'good'],
            ],
            'calcFunction' => function ($value, array $data) {
                if (!isset($data[$this->folderIdFieldKey])) {
                    return 0;
                }

                if (!isset($this->_cacheNotEmptyFolderIds[$data[$this->folderIdFieldKey]])) {
                    return 0;
                }

                return FilesFolder::hasFiles($this->getFolderRelative($data)) ? 1 : 0;
            },
        ]);
    }

    protected function fieldFolderFilesList()
    {
        return F::createText('FolderFilesList', [
            'modes' => F::MODE_READONLY,
            'name' => 'Файлы: список',
            'sqlSelect' => '',
            'tableWidthExcel' => 20,
            'groupconcat' => true,
            'viewMaxWidth' => 150,
            'calcFunction' => function ($value, array $data) {
                $folderAbs = $this->getFolderAbsolute($data);
                if (!$folderAbs) {
                    return '';
                }

                $splFiles = File::scandirRecurcive($folderAbs);
                $filenamesRelative = array_map(fn($splFileInfo) =>
                    /** @var \SplFileInfo $splFileInfo */
                    rtrim(File::fs()->makePathRelative($splFileInfo->getPathname(), $folderAbs), '/'), $splFiles);

                return implode("\n", $filenamesRelative);
            },
            'getHtmlFunction' => function ($value, array $data, $outHtml) {
                if (!isset($data['ID'])) {
                    return $outHtml;
                }
                return Link::create($this->folderModule, htmlspecialchars($value))
                    ->filePreview((int)$data['ID'], $value)
                    ->getHtml();
            },
        ]);
    }

    protected function viewGetFilePreview()
    {
        if (!$this->folders) {
            Response::abort(400);
            return;
        }

        $file = $_GET['file'] ?? '';
        if ($file === '') {
            Response::abort(400);
            return;
        }

        $folderAbs = $this->getFolderAbsolute();
        if (!$folderAbs) {
            Response::abort(404);
            return;
        }

        $filepathAbs = "$folderAbs/$file";

        $splFiles = File::scandirRecurcive($folderAbs);
        $found = A::some($splFiles, fn($splFileInfo) =>
            /** @var \SplFileInfo $splFileInfo */
            $splFileInfo->getPathname() === $filepathAbs);
        if (!$found) {
            Response::abort(404);
            return;
        }

        Response::streamFileFromFilesystem($filepathAbs, null, true);
    }

    protected function fieldFolderFilesCount()
    {
        return F::createInt('FolderFilesCount', [
            'modes' => F::MODE_LIST,
            'name' => "Файлы: кол-во",
            'sqlSelect' => '',
            'zeroEmpty' => true,
            'zeroEmptyExcel' => true,
            'tableAlign' => 'center',
            'tableWidthHtml' => 50,
            'calcFunction' => function ($value, array $data) {
                if (!isset($data[$this->folderIdFieldKey])) {
                    return 0;
                }

                if (!isset($this->_cacheNotEmptyFolderIds[$data[$this->folderIdFieldKey]])) {
                    return 0;
                }

                return FilesFolder::filesCount($this->getFolderRelative($data));
            },
        ]);
    }

    protected function fieldFolderSize()
    {
        return F::createFloat('FolderSize', [
            'modes' => F::MODE_LIST,
            'name' => "Файлы: объём",
            'uom' => 'Мб',
            'sqlSelect' => '',
            'zeroEmpty' => true,
            'zeroEmptyExcel' => true,
            'tableAlign' => 'center',
            'tableWidthHtml' => 70,
            'calcFunction' => function ($value, array $data) {
                if (!isset($data[$this->folderIdFieldKey])) {
                    return '';
                }

                if (!isset($this->_cacheNotEmptyFolderIds[$data[$this->folderIdFieldKey]])) {
                    return 0;
                }

                $sizeBite = FilesFolder::size($this->getFolderRelative($data));
                if ($sizeBite === 0) {
                    return 0;
                }

                return round($sizeBite / 1024 / 1024, 1);
            },
            'getHtmlFunction' => function ($value) {
                if (!$value) {
                    return '';
                }
                return $value . ' Мб';
            },
        ]);
    }
}
