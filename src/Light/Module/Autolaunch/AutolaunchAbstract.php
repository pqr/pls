<?php

namespace Light\Module\Autolaunch;

use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Fieldset;
use Light\File;
use Light\Msg;
use Light\Timer;
use Light\Traits\FieldsetAwareTrait;
use Light\Traits\SubmoduleTrait;
use Light\User;

abstract class AutolaunchAbstract
{
    use SubmoduleTrait, FieldsetAwareTrait;

    protected $_logId;

    protected $_writeTextLog = false;

    /** @var Msg */
    protected $_msgBuff;

    /** @var Msg */
    protected $_msgBuffShort;

    /** @var \Light\Timer */
    protected $_timer;

    /** @var Fieldset|F[] */
    private $_fieldset;

    public function __construct()
    {
        $this->_msgBuff = new Msg();
        $this->_msgBuffShort = new Msg();
        $this->_timer = new Timer();

        $this->_fieldset = new Fieldset();
        //Вызываем метод, который должен быть переопределён в классах потомках - он заполняет $this->_fieldset полями
        $this->initFieldset();
    }

    protected function createLogDbRecord()
    {
        //Запишем в таблицу логов строку с базовыми данными, чтобы получить ID строки
        //Этот ID потом будет использоваться при формировании отчёта импорта/автозапуска для ссылок на этот лог
        $initialLogData = [
            'ClassName' => get_class($this),
            'CreatedBy' => User::getId()
        ];

        $this->_logId = Db::insertRow($initialLogData, 'autolaunchlog');
    }

    protected function saveLog()
    {
        $workTime = $this->_timer->get();

        //Записываем логи в текстовый файл только если это указано явно в настройках этого импорта или автоланча
        if ($this->_writeTextLog) {
            $this->saveLogToFile($workTime);
        }

        $this->saveLogToDb($workTime);
    }

    private function saveLogToDb($workTime)
    {
        $fieldsData = [
            'Report' => $this->_msgBuff->getHtml(),
            'ShortMessage' => $this->_msgBuffShort->getHtml(),
            'WorkTime' => $workTime
        ];

        Db::updateRow($fieldsData, 'autolaunchlog', $this->_logId);
    }

    private function saveLogToFile($workTime)
    {
        $logText = $this->_msgBuff->getText();
        $shortLogText = $this->_msgBuffShort->getText();

        $report = "Run at: " . date('Y-m-d H:i:s')
            . "\tby: " . User::getName()
            . "\n\nWorking Time: " . $workTime . " sec"
            . "\n\n" . $shortLogText
            . "\n\n" . $logText
            . "\n\n\n\n";

        $folder = str_replace('\\', '/', get_class($this));

        $folderpathAbs = LIGHT_DIR_LOGS . "/autolaunch/$folder";
        File::mkdir($folderpathAbs);

        $date = date('Y-m-d');
        $filepathAbs = "$folderpathAbs/{$this->_logId} $date.txt";

        file_put_contents($filepathAbs, $report);
    }

    /**
     * Возвращает короткий лог в текстовом формате - это нужно для вывода в консольк при запуске autolaunch
     * @return string
     */
    protected function getLogText()
    {
        return $this->_msgBuff->getText();
    }

    final public function getFieldset()
    {
        return $this->_fieldset;
    }

    public static function getIconFile()
    {
        return '../Light/i/autolaunch.png';
    }

    /**
     * Функция для переорпделения в классах потомках.
     * Делает всю работу, пишет логи во внутренний массив вызовами $this->putMsg
     * @abstract
     * @param array $params параметры для запуска - ассоциативный массив
     */
    abstract protected function run(array $params);

    /**
     * Метод для переопределения в классах потомках.
     * Здесь можно задать Fieldset, который будет работать как выбор опций запуска
     */
    protected function initFieldset()
    {
    }

    protected function validateMandatory(array $params)
    {
        $errors = [];
        if ($this->_fieldset !== null) {
            $errors = $this->_fieldset->validateMandatory($params);
        }

        return $errors;
    }

    /**
     * Возвращает массив параметров в том порядке в котором их нужно передать в функцию run
     * @return array
     */
    private function getParamsFromPost()
    {
        return $this->getDataFromRequest($_POST);
    }

    private function proceedRun($params)
    {
        $this->createLogDbRecord();

        $errors = $this->validateMandatory($params);
        if ($errors) {

            foreach ($errors as $error) {
                $this->_msgBuff->addError($error['msg']);
            }
            $this->_msgBuffShort->addError('Some mandotry fields missed');

        } else {

            try {
                $this->run($params);
            } catch (\Throwable $e) {
                $this->_msgBuff->addError($e->getMessage());
            }
        }

        $this->saveLog();
    }

    public function proceedRemoteRun($params)
    {
        $this->createLogDbRecord();

        $errors = $this->validateMandatory($params);
        if ($errors) {
            foreach ($errors as $error) {
                $this->_msgBuff->addError($error['msg']);
            }
            $this->_msgBuffShort->addError('Some mandotry fields missed');
        } else {
            $this->run($params);
        }

        $this->saveLog();
        return $this->_msgBuff->getHtml();
    }

    /**
     * Фукнция вызывается при ручном запуске скрипта
     */
    public function __invoke()
    {
        //Ручной запуск, необходимо правильно сформировать параметры из POST запроса
        $params = $this->getParamsFromPost();
        $this->proceedRun($params);
        return $this->_logId;
    }

    /**
     * Запуск из консоли
     */
    public function proceedAutomaticRun()
    {
        if (!$this->getFieldset()->isEmpty()) {

            $dataFromConsole = [];
            //Параметры передаваемые в консоли должны идти в том порядке в котором они описаны в $this->_fieldset
            $i = 2;
            foreach ($this->getFieldset()->getKeys() as $fieldKey) {
                if (isset($GLOBALS['argv'][$i])) {
                    $dataFromConsole[$fieldKey] = $GLOBALS['argv'][$i];
                }
                $i++;
            }

            $params = $this->getFieldset()->getDataFromRequest($dataFromConsole);

            //Добавим default значения параметров, которые не были переданы
            /** @var F $field */
            foreach ($this->getFieldset() as $field) {
                if (!isset($params[$field->key])) {
                    $params[$field->key] = $field->default;
                }
            }

        } else {

            $params = [];

        }

        $this->proceedRun($params);

        print $this->getLogText();
    }
}
