<?php
namespace Forward\Classes\ShipmentCriteria;

use Forward\Application;
use Light\Db;
use Light\DbLog;

class ShipmentCriteria
{
    const NoVInv = 4;
    const NotAllVInv = 5;
    const NoCInv = 6;
    const NotAllCInv = 7;
    const AllInv = 8;
    const AllInvCleared = 9;
    const ToBeClosed = 10;
    const OpenCInv = 11;

    private static $criteria = [

        self::NoVInv => [
            'text' => 'No Vendor Invoices',
            'Description' => 'Нет входящих счетов',
            'class' => 'NoVInv'
        ],
        self::NotAllVInv => [
            'text' => 'Not All Vendor Inv. Received',
            'Description' => 'Получены не все входящие счета',
            'class' => 'NotAllVInv'
        ],
        self::NoCInv => [
            'text' => 'No Customer Invoices',
            'Description' => 'Нет исходящих счетов',
            'class' => 'NoCInv'
        ],
        self::OpenCInv => [
            'text' => 'Open Customer Invoices',
            'Description' => 'Есть исходящие счета в статусе Open или Partially Cleared',
            'class' => 'OpenCInv',
            'cssClass' => 'invopen'
        ],
        self::NotAllCInv => [
            'text' => 'Not All Customer Inv. Issued',
            'Description' => 'Не все счета выставлены на клиента',
            'class' => 'NotAllCInv'
        ],
        self::AllInv => [
            'text' => 'All Invoices Recevied & Issued',
            'Description' => 'Все счета получены и выпущены',
            'class' => 'AllInv',
            'cssClass' => 'good'
        ],
        self::AllInvCleared => [
            'text' => 'All Invoices Cleared',
            'Description' => 'Все счета получены, выпущены и оплачены',
            'class' => 'AllInvCleared',
            'cssClass' => 'good'
        ],
        self::ToBeClosed => [
            'text' => 'To be closed',
            'Description' => 'Все счета получены, выпущены и оплачены, но файл в статусе Open',
            'class' => 'ToBeClosed',
            'cssClass' => 'good bold'
        ]
    ];

    public static function getSelectOptions()
    {
        //Внутренний массив self::$criteria специально был спроектирован так, что он подходит в качетсве параметра options
        return self::$criteria;
    }

    public static function getCriterionInfo($criterionId)
    {
        return self::$criteria[$criterionId];
    }

    public static function getText($criterionId)
    {
        return self::$criteria[$criterionId]['text'];
    }

    /** @var array */
    private static $_buffer;

    /**
     * Обновляет указанный критерий по указанной перевозке
     * @param int $criterionId
     * @param int $shipmentId если ID перевозки не задан - обновление идёт по всем перевозкам
     * @param string $dbLogMsg
     */
    public static function update($criterionId, $shipmentId = 0, $dbLogMsg = '')
    {
        //Если буфер ещё не инициалирован, значит это вообще первое обращение к ShipmentCriteria::update()
        //Одновременно с этим регистрируем обработчик ApplicationAbstract::addFlushOnTerminate()
        if (self::$_buffer === null) {
            self::$_buffer = [];
            Application::$flushShipmentCriteria = true;
        }

        self::$_buffer[$criterionId][$shipmentId] = $dbLogMsg;

    }

    /**
     * @return array ['total' => int, 'inserted' => int, 'deleted' => int, 'perCriterion' => array]
     */
    public static function flush()
    {
        $result = ['total' => 0, 'inserted' => 0, 'deleted' => 0, 'perCriterion' => []];

        foreach (self::$_buffer as $criterionId => $shipments) {

            //Создадим объект специального класса, который поможет нам найти новый список шипментов подпадащих под
            //этот критерий
            /** @var \Forward\Classes\ShipmentCriteria\Rules\Base\RuleBase $className */
            $className = 'Forward\\Classes\\ShipmentCriteria\\Rules\\' . self::$criteria[$criterionId]['class'];
            $criterionName = self::$criteria[$criterionId]['text'];

            $queryBase = "SELECT ShipmentID FROM shipmentcriterion WHERE CriterionID = " . (int)$criterionId;

            //Пройдём в цикле по всем перевозкам
            /** @var array $shipments */
            foreach ($shipments as $shipmentId => $dbLogMsg) {

                //Узнаем старый список перевозок по этому критерию
                $query = $queryBase;
                if ($shipmentId) {
                    $query .= " AND ShipmentID = " . (int)$shipmentId;
                }
                $oldShipmentIds = Db::getCol($query);

                //Получим новый список шипментов подпадающих под этот критерий
                $newShipmentIds = [];
                if ($shipmentId) {

                    //Проверим, удовлетвояет ли данный шипмент данному критерию?
                    if ($className::checkShipment($shipmentId)) {
                        //Если да - добавляем его в массив - он там будет единственным
                        $newShipmentIds[] = $shipmentId;
                    }

                } else {

                    //Обновление по всем перевозкам
                    $newShipmentIds = $className::getShipmentIds();

                }

                //Для начала удалим те ShipmentID, которые были в старом массиве, но нет в новом
                $toBeDeleted = array_diff($oldShipmentIds, $newShipmentIds);
                if ($toBeDeleted) {
                    //Удаляем из таблицы shipmentcriterion одним запросом
                    Db::query(
                        "DELETE FROM shipmentcriterion WHERE CriterionID = @i AND ShipmentID IN (@a)",
                        $criterionId,
                        $toBeDeleted
                    );
                    //Запишем логи
                    foreach ($toBeDeleted as $toBeDeletedShipmentId) {
                        DbLog::write(
                            'Shipment',
                            $toBeDeletedShipmentId,
                            'shipment',
                            $toBeDeletedShipmentId,
                            '',
                            '',
                            [
                                'OldValue' => $criterionId,
                                'OldValueFormated' => $criterionName,
                                'text' => 'Criterion Removed. ' . $dbLogMsg
                            ]
                        );
                    }
                }

                //Теперь добавим те ShipmentID, которых нет в старом массиве, но они появились в новом
                $toBeInserted = array_diff($newShipmentIds, $oldShipmentIds);
                if ($toBeInserted) {
                    $insertSqlValues = [];
                    foreach ($toBeInserted as $toBeInsertedShipmentId) {
                        $insertSqlValues[] = '(' . $criterionId . ', ' . $toBeInsertedShipmentId . ')';
                    }
                    Db::query(
                        "REPLACE INTO shipmentcriterion (CriterionID, ShipmentID) VALUES " . implode(
                            ',',
                            $insertSqlValues
                        )
                    );
                    //Запишем логи
                    foreach ($toBeInserted as $toBeInsertedShipmentId) {
                        DbLog::write(
                            'Shipment',
                            $toBeInsertedShipmentId,
                            'shipment',
                            $toBeInsertedShipmentId,
                            '',
                            '',
                            [
                                'NewValue' => $criterionId,
                                'NewValueFormated' => $criterionName,
                                'text' => 'Criterion Added. ' . $dbLogMsg
                            ]
                        );
                    }
                }

                $total = count($newShipmentIds);
                $inserted = count($toBeInserted);
                $deleted = count($toBeDeleted);

                $result['total'] += $total;
                $result['inserted'] += $inserted;
                $result['deleted'] += $deleted;

                $result['perCriterion'][$criterionId] = [
                    'total' => $total,
                    'inserted' => $inserted,
                    'deleted' => $deleted
                ];

            }
        }

        //На всякий случай очистим буфер
        self::$_buffer = [];

        return $result;
    }


    /**
     * Обновляет все критерии по указанной перевозке
     * @param int $shipmentId если ID перевозки не задан - обновление идёт по всем перевозкам
     * @param string $dbLogMsg
     */
    public static function updateAll($shipmentId = 0, $dbLogMsg = '')
    {
        self::updateSeveral(array_keys(self::$criteria), $shipmentId, $dbLogMsg);
    }

    /**
     * Обновляет несколько критериев по указанной перевозке
     * @param array $criteriaIds
     * @param int $shipmentId если ID перевозки не задан - обновление идёт по всем перевозкам
     * @param string $dbLogMsg
     */
    public static function updateSeveral(array $criteriaIds, $shipmentId = 0, $dbLogMsg = '')
    {
        foreach ($criteriaIds as $criterionId) {
            self::update($criterionId, $shipmentId, $dbLogMsg);
        }
    }

    /**
     * @desc обновляет критерии связанные с основными данни шипмента (поля таблицы shipments и shipment.Container*)
     * должно вызываться при сохранении шипмента
     * @param int $shipmentId
     * @param string $dbLogMsg
     */
    public static function updateRelatedBasicData($shipmentId = 0, $dbLogMsg = '')
    {
        self::updateSeveral(
            [self::ToBeClosed],
            $shipmentId,
            $dbLogMsg
        );
    }

    /**
     * @desc обвновляет критерии связанные с Customer Invoices
     * вызывается при сохранении инвойсов
     * @param array $fileNumbers
     * @param string $dbLogMsg
     */
    public static function updateRelatedCInvByFileNumbers(array $fileNumbers, $dbLogMsg = '')
    {
        if ($fileNumbers) {

            $criteriaIds = [
                self::NoCInv,
                self::NotAllCInv,
                self::OpenCInv,
                self::AllInv,
                self::AllInvCleared,
                self::ToBeClosed
            ];

            $res = Db::query("SELECT ID FROM shipment WHERE Name IN (@a)", $fileNumbers);
            while ($row = $res->fetch_assoc()) {
                self::updateSeveral($criteriaIds, $row['ID'], $dbLogMsg);
            }
        }
    }

    /**
     * @desc обновляет критерии связанные с Vendor Invoices
     * вызывается при сохранении инвойсов
     * @param array $fileNumbers
     * @param string $dbLogMsg
     */
    public static function updateRelatedVInvByFileNumbers(array $fileNumbers, $dbLogMsg = '')
    {
        if ($fileNumbers) {

            $criteriaIds = [
                self::NoVInv,
                self::NotAllVInv,
                self::AllInv,
                self::AllInvCleared,
                self::ToBeClosed
            ];

            $res = Db::query("SELECT ID, Name FROM shipment WHERE Name IN (@a)", $fileNumbers);
            while ($row = $res->fetch_assoc()) {
                self::updateSeveral($criteriaIds, $row['ID'], $dbLogMsg);
            }
        }
    }

    /**
     * @desc обновляет критерии связанные с любыми инвойсами
     * вызывается при сохранении инвойсов
     * @param int $shipmentId
     * @param string $dbLogMsg
     */
    public static function updateRelatedInv($shipmentId = 0, $dbLogMsg = '')
    {
        self::updateSeveral(
            [
                self::NoCInv,
                self::NotAllCInv,
                self::NoVInv,
                self::NotAllVInv,
                self::OpenCInv,
                self::AllInv,
                self::AllInvCleared,
                self::ToBeClosed
            ],
            $shipmentId,
            $dbLogMsg
        );
    }

    public static function getShipmentCriteriaArray($shipmentId)
    {
        $criteriaIds = Db::getCol("SELECT CriterionID FROM shipmentcriterion WHERE ShipmentID = @i", $shipmentId);
        $outArr = [];
        //Добавим описания в массив
        foreach ($criteriaIds as $id) {
            if (isset(self::$criteria[$id])) {
                $outArr[$id] = self::$criteria[$id];
            }
        }
        return $outArr;
    }
}