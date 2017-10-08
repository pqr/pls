<?php

namespace Forward\Autolaunch;

use Forward\Classes\Constant;
use Forward\ForwardConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Light\Module\Autolaunch\AutolaunchAbstract;

class PingOneC extends AutolaunchAbstract
{
    protected static $_title = 'Проверить подключение к 1С';
    protected static $_description = 'Отправляет пустой запрос на создание счёта в 1С. Если получен ответ с сообщением об ошибке создания счёта - значит подключение есть! Этот метод в качестве мониторинга можно запускать каждые 10 минут.';

    protected function run(array $params)
    {
        $client = new Client();
        foreach(Constant::$onecCompany as $path) {
            $this->_msgBuff->addEmptyLine();
            $url = 'http://' . ForwardConfig::$onecIp . "/$path/hs/CreateCustomerInvoice";
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
                    $this->_msgBuffShort->addSuccess('От 1С получен ответ длиной ' . strlen($resBody) . ' байт');
                }

            } catch (RequestException $e) {

                $msg = "$path: Не удалось подключиться к серверу 1С.<br>" . $e->getMessage();
                $this->_msgBuff->addError($msg);
                $this->_msgBuffShort->addError($msg);

            }
        }


    }

}
