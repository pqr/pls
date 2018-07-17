<?php

namespace Forward\Classes\ImportFromOneC;

use Forward\Classes\ImportFromOneC\Importers\ImportCAct;
use Forward\Classes\ImportFromOneC\Importers\ImportCInvoice;
use Forward\Classes\ImportFromOneC\Importers\ImportCPayment;
use Forward\Classes\ImportFromOneC\Importers\ImporterAbstract;
use Forward\Classes\ImportFromOneC\Importers\ImportVInvoice;
use Forward\Classes\ImportFromOneC\Importers\ImportVPayment;
use Forward\ForwardConfig;
use Light\Db;
use Light\Field\FieldDate;
use Light\FilesFolder;
use Light\Msg;
use Light\Request;
use Light\Response;

class ImportFromOneC
{
    /** @var  Msg */
    protected $msgBuff;
    protected $importId;

    public function __invoke()
    {
        Db::query("INSERT INTO log_onecimport () VALUES ()");
        $this->importId = Db::getInsertId();

        $authErr = null;
        if (!Request::isPost()) {
            $authErr = 'Запрос долежн быть POST';
        } elseif (!isset($_SERVER['HTTP_X_AUTH'])) {
            $authErr = 'Нет http заголовка x-auth';
        } else {
            if ($_SERVER['HTTP_X_AUTH'] !== ForwardConfig::$onecAuthToken) {
                $authErr = 'Не верное значение x-auth ' . $_SERVER['HTTP_X_AUTH'];
            }
        }
        if ($authErr) {
            Db::updateRow(['Log' => htmlspecialchars($authErr)], 'log_onecimport', $this->importId);
            Response::abort(403, $authErr);
            return;
        }

        $text = file_get_contents('php://input');

        $relativePath = "log_onecimport/" . (floor($this->importId / 1000)) . "/$this->importId";
        FilesFolder::mkdir($relativePath);
        $filepathAbs = FilesFolder::getAbsolutePath($relativePath) . "/" . time() . ".txt";
        file_put_contents($filepathAbs, $text);

        $this->msgBuff = new Msg();
        $data = $this->parseJson($text);
        if (!$data) {
            $log = $this->msgBuff->getText();
            Db::updateRow(['Log' => $log], 'log_onecimport', $this->importId);
            Response::abort(400, $log);
            return;
        }

        $logData = [
            'Name' => $data['DocNumber'] ?? $data['Name'] ?? '',
            'DocDate' => FieldDate::parse($data['DocDate'] ?? $data['PaymentDate'] ?? ''),
            'Type' => $data['type'] ?? '',
            'CompanyID' => $data['CompanyID'] ?? 0,
        ];
        Db::updateRow($logData, 'log_onecimport', $this->importId);

        $knownTypes = '"cinvoice", "cact", "cpayment", "vinvoice", "vpayment"';

        if (!isset($data['type'])) {
            $this->msgBuff->addError("Отсутствует обязательное поле type ($knownTypes)");
            $log = $this->msgBuff->getText();
            Db::updateRow(['Log' => $log], 'log_onecimport', $this->importId);
            Response::abort(400, $log);
            return;
        }

        $importer = null;
        switch ($data['type']) {
            case 'cinvoice':
                $importer = new ImportCInvoice($this->importId, $this->msgBuff);
                break;

            case 'cact':
                $importer = new ImportCAct($this->importId, $this->msgBuff);
                break;

            case 'cpayment':
                $importer = new ImportCPayment($this->importId, $this->msgBuff);
                break;

            case 'vpayment':
                $importer = new ImportVPayment($this->importId, $this->msgBuff);
                break;

            case 'vinvoice':
                $importer = new ImportVInvoice($this->importId, $this->msgBuff);
                break;

        }

        if (!$importer) {
            $this->msgBuff->addError('Неизвестный тип документа ' . ($data['type'] ?? '') . ', должно быть ' . $knownTypes);
            $log = $this->msgBuff->getText();
            Db::updateRow(['Log' => $log], 'log_onecimport', $this->importId);
            Response::abort(400, $log);
            return;
        }

        /** @var ImporterAbstract $importer */
        $successId = $importer->import($data);
        $log = $this->msgBuff->getHtml();
        Db::updateRow(['SuccessID' => $successId, 'Log' => $log], 'log_onecimport', $this->importId);

        if (!$successId) {
            // Что-то пошло не так
            Response::abort(500, $this->msgBuff->getText());
            return;
        }
    }

    protected function parseJson($text)
    {
        $json = json_decode(str_replace("\xEF\xBB\xBF", '', $text), true);
        if (!$json) {
            $this->msgBuff->addError('Не удалось распознать JSON структуру');
            switch (json_last_error()) {
                case JSON_ERROR_DEPTH:
                    $this->msgBuff->addError('Достигнута максимальная глубина стека');
                    return false;
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $this->msgBuff->addError('Некорректные разряды или не совпадение режимов');
                    return false;
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $this->msgBuff->addError('Некорректный управляющий символ');
                    return false;
                    break;
                case JSON_ERROR_SYNTAX:
                    $this->msgBuff->addError('Синтаксическая ошибка, не корректный JSON');
                    return false;

                case JSON_ERROR_UTF8:
                    $this->msgBuff->addError('Некорректные символы UTF-8, возможно неверная кодировка');
                    return false;

                default:
                    $this->msgBuff->addError('Неизвестная ошибка');
                    return false;
            }
        }

        if (!is_array($json)) {
            $this->msgBuff->addError('Корневая JSON структура не является объектом {...}');
            return false;
        }

        return $json;
    }
}
