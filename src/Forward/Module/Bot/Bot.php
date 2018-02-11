<?php

namespace Forward\Module\Bot;


use Forward\Module\Shipment\ShipmentObj;
use Light\Db;
use Light\Field\FieldAbstract;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\Field\FieldMoney;
use Light\Field\FieldMonth;
use Light\ModuleBase\ModuleBotAbstract;
use Light\User;

class Bot extends ModuleBotAbstract
{

    const LIMIT = 15;

    protected function getWizardMessageForCommand(string $command)
    {
        switch ($command) {
            case 'file':
                return 'Введите номер перевозки (File Number) для получения всей информации';

            case 'cont':
                return 'Введите номер контейнера';

            case 'truck':
                return 'Введите номер машины';

            case 'mawb':
                return 'Введите номер MAWB';

            case 'month':
                return 'Введите месяц в формате 12/2017 или просто номер месяца в текущем году';

            case 'cs':
                return 'Введите фрагмент имени или логина ответсвенного Customer Service. Можно ввести просто "me", чтобы показать все "свои" перевозки';

            case 'cust':
                return 'Введите фрагмент названия клиента (Customer или Customer Account)';

        }

        return false;
    }

    protected function runCommands(array $commands)
    {
        $descriptions = [];
        $sqlWhere = [];
        foreach ($commands as [$command, $arg]) {

            if (preg_match('/file(\d+)$/', $command, $matches)) {
                // специальный вид команды file1234, где 1234 номер перевозки
                $command = 'file';
                $arg = $matches[1];
            }

            $argSql = Db::esc($arg);
            switch ($command) {
                case '':
                    $mawb = str_replace(['-', ' '], '', $arg);
                    $descriptions[] = "Номер MAWB, контейнера или машины: $arg";
                    $sqlWhere[] = "(ContainerName LIKE '%$argSql%'
                                    OR TruckNumber LIKE '%$argSql%'
                                    OR REPLACE(REPLACE(MAWBNumber, ' ', ''), '-', '') LIKE '%$mawb%'
                                    )";
                    break;

                case 'file':
                    $descriptions[] = "Номер первозки (File Number): $arg";
                    $sqlWhere[] = "Name = " . (int)$arg;
                    break;

                case 'cont':
                    if ($arg === '') {
                        $descriptions[] = "После команды cont не указан номер контейнера, пропускаем";
                    } else {
                        $descriptions[] = "Номер контейнера: $arg";
                        $sqlWhere[] = "ContainerName LIKE '%$argSql%'";
                    }
                    break;

                case 'truck':
                    if ($arg === '') {
                        $descriptions[] = "После команды truck не указан номер машины, пропускаем";
                    } else {
                        $descriptions[] = "Номер машины: $arg";
                        $sqlWhere[] = "TruckNumber LIKE '%$argSql%'";
                    }
                    break;

                case 'mawb':
                    if ($arg === '') {
                        $descriptions[] = "После команды mawb не указан номер накладной, пропускаем";
                    } else {
                        $mawb = str_replace(['-', ' '], '', $arg);
                        $descriptions[] = "Номер MAWB: $arg";
                        $sqlWhere[] = "REPLACE(REPLACE(MAWBNumber, ' ', ''), '-', '') LIKE '%$mawb%'";

                    }
                    break;


                case 'month':
                    if ($arg === '') {
                        $descriptions[] = "После команды month не указан месяц, пропускаем";
                    } else {
                        $parts = explode('-', $arg);
                        $monthFrom = $this->parseMonth($parts[0]);
                        if (!$monthFrom) {
                            $descriptions[] = "Не удалось распознать месяц: " . $parts[0] . ", формат должен быть 12/2017 или просто 12, проускаем";
                        }

                        $monthUntil = '';
                        if (isset($parts[1])) {
                            $monthUntil = $this->parseMonth($parts[1]);
                            if (!$monthFrom) {
                                $descriptions[] = "Не удалось распознать месяц: " . $parts[1] . ", формат должен быть 12/2017 или просто 12, проускаем";
                                break;
                            }
                        }

                        if ($monthFrom && $monthUntil) {
                            $descriptions[] = "Месяц: с " . FieldMonth::beautify($monthFrom) . ' по ' . FieldMonth::beautify($monthUntil);
                            $sqlWhere[] = "Month >= " . Db::escString($monthFrom) . " AND Month <= " . Db::escString($monthUntil);
                        } elseif ($monthFrom) {
                            $descriptions[] = "Месяц: " . FieldMonth::beautify($monthFrom);
                            $sqlWhere[] = "Month = " . Db::escString($monthFrom);
                        }
                    }
                    break;

                case "cs":
                    if ($arg === '') {
                        $descriptions[] = "После команды cs не указн ответсвенный Customer Service";
                    } else {
                        if ($arg === 'me') {
                            $descriptions[] = "Customer Service: " . User::getName();
                            $sqlWhere[] = "ResponsibleCS = " . User::getId();
                        } else {
                            $userIds = Db::getCol("SELECT ID FROM user WHERE Name LIKE '%$argSql%' OR Login LIKE '%$argSql%'");
                            if (!$userIds) {
                                $descriptions[] = "Customer Service: $arg - НЕ НАЙДЕН";
                                $sqlWhere[] = "FALSE";
                            } else {
                                $descriptions[] = "Customer Service: $arg";
                                $sqlWhere[] = "ResponsibleCS IN (" . implode(',', $userIds) . ")";
                            }
                        }
                    }
                    break;

                case "cust":
                    if ($arg === '') {
                        $descriptions[] = "После команды cust не указно название клиента, пропускаем";
                    } else {
                        $customerIds1 = Db::getCol("SELECT ID FROM customer WHERE Name LIKE '%$argSql%'");
                        $customerIds2 = Db::getCol(
                            "SELECT cca.CustomerID
                        FROM customer_customeraccount cca, customeraccount ca
                        WHERE cca.CustomerAccountID = ca.ID
                            AND ca.Name LIKE '%$argSql%'"
                        );
                        if (!$customerIds1 && !$customerIds2) {
                            $descriptions[] = "Клиент: $arg - НЕ НАЙДЕН";
                            $sqlWhere[] = "FALSE";
                        } else {
                            $descriptions[] = "Клиент: $arg";
                            $customeIdsSql = implode(',', array_merge($customerIds1, $customerIds2));
                            $sqlWhere[] = "CustomerID IN ($customeIdsSql)";
                        }
                    }
                    break;

                default:
                    $descriptions[] = "Команда $command не распознана, пропускаем вместе с аргументом $arg";

            }
        }

        $outMsg = implode("\n", $descriptions);

        if ($sqlWhere) {

            $shipments = Db::getAll("
                SELECT SQL_CALC_FOUND_ROWS ID
                  , Name
                  , (SELECT Name FROM customer WHERE ID = shipment.CustomerID) AS CustomerName
                  , MAWBNumber
                  , ETD 
                FROM shipment
                WHERE " . implode(" AND ", $sqlWhere) . "
                ORDER BY ID DESC
                LIMIT " . self::LIMIT
            );

            $foundRows = Db::getOne("SELECT FOUND_ROWS()");
            $outMsg .= "\nНайдено: $foundRows";
            if ($foundRows === 1) {

                $outMsg .= "\n" . $this->getOneShipmentText($shipments[0]['ID']);

            } else {

                if ($foundRows > self::LIMIT) {
                    $outMsg .= ", показаны " . self::LIMIT . " самых свежих";
                }

                $outMsg .= "\n" . $this->getShipmentsListText($shipments);


            }

        } else {

            $outMsg .= "Поисковый запрос не задан\n\n" . $this->getStartMessageAuthorized();
        }

        $this->sendResponse($outMsg);
    }

    private function parseMonth(string $monthText)
    {
        if (is_numeric($monthText) && $monthText <= 12) {
            $monthText .= '/' . date('Y');
        }
        return FieldMonth::parse($monthText);
    }

    protected function getStartMessageAuthorized()
    {
        return strip_tags(file_get_contents(__DIR__ . '/BotStartMessageAuthorized.html'));
    }

    private function getShipmentsListText(array $shipments)
    {
        $lines = [];
        foreach ($shipments as $shipment) {
            $lines[] = "/file" . $shipment['Name']
                . " " . $shipment['CustomerName']
                . " " . $shipment['MAWBNumber']
                . ", ETD " . FieldDate::beautify($shipment['ETD']);
        }

        return implode("\n", $lines);
    }

    private function getOneShipmentText(int $shipmentId)
    {
        $shipmentObj = new ShipmentObj();
        $shipmentObj->setId($shipmentId);
        if (!$shipmentObj->loadData()) {
            return "Перевозка $shipmentId не найдена";
        }

        $data = $shipmentObj->getData();

        /** @var FieldAbstract $field */
        $lines = [];
        foreach ($shipmentObj->getFieldset()->filterByMode(F::MODE_VIEW) as $field) {
            if ($field instanceof FieldMoney) {
                continue;
            }

            if ($field->key === 'CreatedBy' || $field->key === 'CreatedDateTime') {
                continue;
            }

            if (!isset($data[$field->key])) {
                continue;
            }
            // Передаём флаг forTable=true, чтобы не выводить всяких дополнительных элементов ссылок "написать" напротив имени клиента
            $text = $field->getText($data[$field->key], $data, true);
            if ($text === '') {
                continue;
            }
            $lines[] = strip_tags($field->getName()) . ' ' . $text;
        }

        return implode("\n", $lines);

    }


}
