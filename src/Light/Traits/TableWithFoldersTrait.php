<?php

namespace Light\Traits;

use Light\Config;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldHtml;
use Light\Fieldset;
use Light\FilesFolder;

trait TableWithFoldersTrait
{
    protected $folders = false;

    // Во-вторых, нужно указать ключ поля, которое искать в массиве $data - там должно храниться id внешнего элемента
    protected $folderNameFieldKey = 'ID';

    protected $_cacheNotEmptyFolderIds;

    /**
     * Возвращает название базовой директории к которой потом будет добавлено имя папки конкретного объекта
     */
    abstract protected function getFolderBaseDir();

    /**
     * Контракт на класс Table - должен существовать метод getFieldsetFirst()
     * @return Fieldset|F[]
     */
    abstract public function getFieldsetFirst();

    /**
     * Возвращает имя папки на основе переданного id объекта
     * По умолчанию это будет id записи, но можно переопределить в классах потомках, загрузив данные об объекте
     * @param array $data
     * @return string|null имя папки
     */
    protected function getFolderName(array $data)
    {
        //По умаолчанию используется алгоритм основанный на ID текущего объекта (от котогоро массив $data)
        if (!isset($data[$this->folderNameFieldKey])) {
            return null;
        }

        $sub = floor($data[$this->folderNameFieldKey] / 1000);
        return "$sub/" . $data[$this->folderNameFieldKey];
    }

    protected function getFolderRelativePath(array $data)
    {
        $folder = $this->getFolderName($data);
        if ($folder !== null) {
            return $this->getFolderBaseDir() . '/' . $folder;
        }

        return null;
    }

    protected function createFolder(array $data)
    {
        if ($folderRelativePath = $this->getFolderRelativePath($data)) {
            FilesFolder::mkdir($folderRelativePath);
        }
    }

    protected function deleteFolder(array $data)
    {
        $folderRelativePath = $this->getFolderRelativePath($data);
        if ($folderRelativePath !== null) {
            // Перемещаем папку в специальную директорию deleted
            $destRelativePath = 'deleted/' . $folderRelativePath;
            FilesFolder::rename($folderRelativePath, $destRelativePath);
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

                    $folderBaseDir = $this->getFolderBaseDir();
                    if (!$folderBaseDir) {
                        return '';
                    }
                    $folderRelativePath = $this->getFolderRelativePath($data);
                    if ($folderRelativePath === null) {
                        return '';
                    }
                    //При выводе иконок папок в таблицу проверим по массиву закешированных ID по которым папки точно
                    //не пустые
                    if (isset($this->_cacheNotEmptyFolderIds[$data[$this->folderNameFieldKey]])) {
                        $isNotEmpty = true;
                    } else {
                        //Для текущей строки в кеше непустых ничего нет, значит запустим проверку на пустоту вручную,
                        //сохраним в базу
                        $isNotEmpty = FilesFolder::updateCacheNotEmpty($folderRelativePath, $folderBaseDir,
                            $data[$this->folderNameFieldKey]);
                    }

                    $innerHtml = FilesFolder::getImgHtml($isNotEmpty);
                    if (!$forTable) {
                        $innerHtml .= ' ' . (Config::$lang === 'ru' ? 'Файлы' : 'Folder');
                    }

                    return FilesFolder::getLink($folderRelativePath, $innerHtml);
                }
            ]
        );
    }

    protected function fieldFolderIsEmpty()
    {
        return F::createSelect('FolderIsEmpty', [
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
                if (!isset($data[$this->folderNameFieldKey])) {
                    return 0;
                }

                if (!isset($this->_cacheNotEmptyFolderIds[$data[$this->folderNameFieldKey]])) {
                    return 0;
                }

                return FilesFolder::isNotEmpty($this->getFolderRelativePath($data)) ? 1 : 0;
            }
        ]);
    }

    protected function fieldFolderFilesList()
    {
        return F::createText('FolderFilesList', [
            'modes' => F::MODE_LIST,
            'name' => 'Файлы: список',
            'sqlSelect' => '',
            'tableWidthExcel' => 20,
            'calcFunction' => function ($value, array $data) {
                if (!isset($data[$this->folderNameFieldKey])) {
                    return '';
                }

                return str_replace(
                    DIRECTORY_SEPARATOR
                    , ' / '
                    , implode("\n", FilesFolder::recursivelyScanDir($this->getFolderRelativePath($data)))
                );
            }
        ]);
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
                if (!isset($data[$this->folderNameFieldKey])) {
                    return 0;
                }

                if (!isset($this->_cacheNotEmptyFolderIds[$data[$this->folderNameFieldKey]])) {
                    return 0;
                }

                return FilesFolder::filesCount($this->getFolderRelativePath($data));
            }
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
                if (!isset($data[$this->folderNameFieldKey])) {
                    return '';
                }

                if (!isset($this->_cacheNotEmptyFolderIds[$data[$this->folderNameFieldKey]])) {
                    return 0;
                }

                $sizeBite = FilesFolder::size($this->getFolderRelativePath($data));
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
            }
        ]);
    }

    protected function loadCacheNotEmptyFolderIds($idsString)
    {
        if ($this->folders && $idsString) {
            //подгрузим заранее все значения из кэширующей таблицы с данными о заполненности папок
            $this->_cacheNotEmptyFolderIds = Db::getAssoc(
                "SELECT RecordID, 1
                 FROM `cache_notemptyfilesfolder`
                 WHERE `ModuleName` = @s AND RecordID IN ($idsString)",
                $this->getFolderBaseDir()
            );
        }
    }
}
