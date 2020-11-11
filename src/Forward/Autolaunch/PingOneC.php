<?php

namespace Forward\Autolaunch;

use Forward\Classes\Constant;
use Forward\ForwardConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Light\Module\Autolaunch\AutolaunchAbstract;

class PingOneC extends AutolaunchAbstract
{
    protected static $title = 'Проверить подключение к 1С';
    protected static $description = 'Отправляет пустой GET запрос на hs/CreateCustomerInvoice". В качестве ответа должна прийти диагностическая информация с настройками 1С!';

    protected function run(array $params)
    {
        $client = new Client(['verify' => false]);
        foreach (Constant::$onecCompany as $path) {
            $this->_msgBuff->addEmptyLine();
            $url = 'https://' . ForwardConfig::$onecIp . ":" . ForwardConfig::$onecPort . "/$path/hs/CreateCustomerInvoice";
            $this->_msgBuff->addInfo("<strong>$url</strong>");
            try {
                $res = $client->get($url);
                $resBody = (string)$res->getBody();
                if (!$resBody) {
                    $msg = "$path: не удалось прочитать ответ от 1С";
                    $this->_msgBuff->addError($msg);
                    $this->_msgBuffShort->addError($msg);
                } else {
                    $this->_msgBuff->addInfo(nl2br($resBody));
                    $this->_msgBuffShort->addSuccess("$path: " . explode("\n", $resBody, 2)[0]);
                }
            } catch (RequestException $e) {
                $msg = "$path: не удалось подключиться к серверу 1С<br>" . $e->getMessage();
                $this->_msgBuff->addError($msg);
                $this->_msgBuffShort->addError($msg);
            }
        }
    }
}
