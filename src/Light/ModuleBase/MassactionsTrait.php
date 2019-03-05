<?php


namespace Light\ModuleBase;

use Light\Config;
use Light\Db;
use Light\Misc;
use Light\ModuleBase\Massactions\MassactionCollectPdfs;
use Light\ModuleBase\Massactions\MassactionDelete;
use Light\ModuleBase\Massactions\MassactionDownloadAsZip;
use Light\ModuleBase\Massactions\MassactionMultipleUpdate;
use Light\ModuleBase\Massactions\MassactionUpload;
use function Light\reportException;

/**
 * Этот trait безусловно подключается к ModuleAbstract.
 * Код вынесен в этот trait исключительно чтобы облегчить файл ModuleAbstract.php
 */
trait MassactionsTrait
{
    use MassactionMultipleUpdate, MassactionCollectPdfs, MassactionDelete, MassactionUpload, MassactionDownloadAsZip;

    protected $massactions = [];

    /**
     * Метод для переопределения в классах потомках - здесь должен заполняться массив $this->massactions
     * Для более удобного заполнения массива $this->massactions можно использовать метод $this->addMassaction()
     */
    protected function initMassactions()
    {
    }

    /**
     * @param string $action уникальный ключ этого massaction
     * @param string $text текст отображаемый в выпадающем списке
     * @param string $cssClass класс которым подсветить элемент в выпадающем списке
     * @param string $optgroup
     * @param string|null $class
     * @param bool $blank открывать при сабмите в новом окне браузера
     */
    protected function addMassaction(
        string $action,
        string $text,
        string $cssClass = '',
        string $optgroup = '',
        string $class = null,
        bool $blank = false
    ) {
        //Если передан флаг blank, значит нужно откывать новое окно браузера при сабмите massaction
        //Это происходит в js окружении, поэтому нужно как-то передать информацию в js окружение, что данный massaction
        //следует открывать в новом окне. Используем для этого css class
        if ($blank) {
            $cssClass = trim($cssClass . ' massaction-target-blank');
        }

        if (!$class && strpos($action, PROJECT_NAMESPACE . '\\') === 0) {
            // В качестве имени $action может передаваться fully qualified class name
            // это удобно для избежания опечаток.
            // Сохраним указатель на этот класс в свойстве "class"
            $class = $action;
        }

        $this->massactions[$action] = [
            'text' => $text,
            'cssClass' => $cssClass,
            'optgroup' => $optgroup,
            'class' => $class,
        ];
    }

    /**
     * Читает переменную $_POST['CheckedIds'] и проверяет каждый id на предмет доступа к этому объекту
     * @return array
     */
    protected function getMassactionIdsFromPost(): array
    {
        if (!isset($_POST['CheckedIds'])) {
            return [];
        }

        $ids = Misc::idsFromComaSeparatedString($_POST['CheckedIds']);
        if (!$ids) {
            return [];
        }

        // На этот массив наложим банальный SQL WHERE, который проверяет на чтение
        try {

            $accessSqlWhere = $this->getAccessSqlWhere();
            if ($accessSqlWhere) {
                $ids = Db::getCol(
                    "SELECT ID 
                    FROM {$this->getDbTable()} 
                    WHERE $accessSqlWhere 
                        AND ID IN (@a)"
                    , $ids
                );
            }

            return $ids;

        } catch (\Exception $e) {

            reportException($e);
            return [];

        }
    }

    /**
     * Метод вызывается напрямую из браузера через url `?a=${_lightModule}&p=massaction&action=${action}`;
     */
    public function proceedMassaction()
    {
        $this->initMassactions();

        $action = $_GET['action'];
        $ids = $this->getMassactionIdsFromPost();
        if (!$ids) {
            $msg = Config::$lang === 'ru' ? 'Не выбраны строки' : 'No selected rows';
            MassactionUtils::sendErrorDialog($msg);
            return;
        }

        if ($action === 'FilesDialog' && method_exists($this, 'massactionFilesDialog')) {
            // Особый massaction которого нет в массиве $this->massactions
            $this->massactionFilesDialog($ids);
            return;
        }

        if ($action === 'Print') {
            // Особый massaction которого нет в массиве $this->massactions
            $this->massactionPrint($ids);
            return;
        }

        if ($action === 'EmailDialog' && method_exists($this, 'massactionEmailDialog')) {
            // Особый massaction которого нет в массиве $this->massactions
            $this->massactionEmailDialog($ids);
            return;
        }

        if (!isset($this->massactions[$action])) {
            throw new \InvalidArgumentException("Unknown massaction $action");
        }

        $class = $this->massactions[$action]['class'];
        if ($class) {
            // Если внутри Massaction определён какой-то калсс для запуска - инициализируем и запустим его!
            /** @var MassactionAbstract $massactionObj */
            $massactionObj = new $class;
            $massactionObj($ids);

        } else {

            // В противном случае попробуем запустить метод с определённым именем в текущем классе
            $handler = 'massaction' . ucfirst($action);
            if (!method_exists($this, $handler)) {
                $this->{$handler}($ids);
                throw new \BadMethodCallException("Unknown function $handler in class " . get_class($this));
            }

            $this->{$handler}($ids);

        }
    }
}
