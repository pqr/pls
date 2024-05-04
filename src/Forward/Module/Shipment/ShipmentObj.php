<?php

namespace Forward\Module\Shipment;

use Forward\Application;
use Forward\Classes\Constant;
use Forward\Classes\ShipmentCriteria\ShipmentCriteria;
use Forward\Field\GeneralFields;
use Forward\Field\ShipmentFields;
use Forward\Module\CInvoice\CInvoiceObj;
use Forward\Module\CPayment\CPaymentObj;
use Forward\Module\Customer\Customer;
use Forward\Module\Quotation\Quotation;
use Forward\Module\VInvoice\VInvoiceObj;
use Light\Db;
use Light\Field\CommonFields;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\Link;
use Light\Msg;
use Light\ObjAbstract;
use Light\User;

/**
 * @method int getName()
 * @method int getFileClosed()
 * @method int getColor()
 * @method int getTrafficID()
 * @method int getResponsibleSales()
 * @method int getResponsibleCS()
 * @method int getCompanyID()
 * @method int getCustomerID()
 * @method int getCustomerAccountID()
 * @method string getMAWBNumber()
 * @method string getMAWBDate()
 * @method string getConsigneeReference()
 * @method int getPieces()
 * @method float getChargeableWeight()
 * @method float getGrossWeight()
 * @method float getVolume()
 * @method string getNatureOfGoods()
 * @method string getIncoterms()
 * @method string getIncotermsLocation()
 * @method string getAirlineAlphaCode()
 * @method string getPlaceOfReceipt()
 * @method string getDepartureIATA()
 * @method string getDepartureLocationName()
 * @method string getArrivalIATA()
 * @method string getArrivalLocationName()
 * @method int getPODTerminalID()
 * @method int getPortExpeditorID()
 * @method string getDestination()
 * @method string getVessel()
 * @method string getVoyageNbr()
 * @method string getConsignee()
 * @method string getComments()
 * @method int getVendorID()
 * @method string getMonth()
 * @method string getReadyForCarriageDate()
 * @method string getETD()
 * @method string getATD()
 * @method string getETA()
 * @method string getATA()
 * @method string getContainerName()
 * @method int getContainerType()
 * @method string getPUPDate()
 * @method string getERTDate()
 * @method int getTruckerID()
 * @method string getTruckNumber()
 * @method int getEmailNoticeSent()
 * @method string getBUIDs()
 * @method int getQuotationID()
 * @method float getCacheCSIncomeinUSD()
 * @method float getCacheCSExpensesinUSD()
 * @method float getCacheEGPinUSD()
 * @method float getCacheIncomeinUSD()
 * @method float getCacheExpensesinUSD()
 * @method float getCacheGPinUSD()
 * @method float getCacheGPDeviationinUSD()
 */
final class ShipmentObj extends ObjAbstract
{
    public const SHIPMENT_OPEN = 0;
    public const SHIPMENT_CLOSED = 1;

    public function __construct()
    {
        $this->setDbTable('shipment');

        //Сформируем списки перевозчиков
        $query = "SELECT ID, Name, isAirline, isCarrier, isPortExpeditor, isTrucker,
                      IF(isAirline, 'Airlines', IF(isCarrier, 'Ocean Carriers', '')) AS optgroup
                  FROM vendor
                  ORDER BY optgroup, Name";
        $vendors = Db::getAssoc($query);

        $portExpeditorOptions = $vendors;
        foreach ($portExpeditorOptions as &$portExpeditor) {
            $portExpeditor['Active'] = $portExpeditor['isPortExpeditor'];
        }
        unset($portExpeditor);

        $truckerOptions = $vendors;
        foreach ($truckerOptions as &$trucker) {
            $trucker['Active'] = $trucker['isTrucker'];
        }
        unset($trucker);

        $this->addFields(

            ShipmentFields::name(),
            ShipmentFields::fileClosed(),
            CF::color(),
            GeneralFields::trafficId()->setParam('editWidth', 'medium'),
            F::createSelect('ResponsibleSales', [
                'name' => 'Sales Manager',
                'nameTableSettings' => 'Responsible Sales',
                'options' => "SELECT ID, Name, IF(Active AND LOCATE('|" . Application::ROLE_SALES . "|', ResponsibleRoles), 1, 0) AS Active FROM user ORDER BY Name",
                'tableWidthExcel' => 20
            ]),
            F::createSelect('ResponsibleCS', [
                'name' => 'Customer Service',
                'nameTableSettings' => 'Responsible Customer Service',
                'options' => "SELECT ID, Name, IF(Active AND LOCATE('|" . Application::ROLE_CS . "|', ResponsibleRoles), 1, 0) AS Active FROM user ORDER BY Name",
                'tableWidthExcel' => 20,
                'editMandatory' => true
            ]),
            GeneralFields::companyId()->setParam('editWidth', 'medium'),
            GeneralFields::customerId(true),
            GeneralFields::customerAccountId(),
            GeneralFields::consigneeId(),

            F::createString('Consignee', [
                'name' => 'Consignee (old)',
                F::PARAM_AC_SOURCE => 'list_consignee',
                'upper' => true
            ]),
            GeneralFields::consigneeReference(),

            F::createString('MAWBNumber', [
                'name' => 'MAWB Number',
                'sqlSelectMandatory' => true
            ]),
            F::createDate('MAWBDate', ['name' => 'MAWB Date', 'ajax' => true]),

            ShipmentFields::flightNo(),
            ShipmentFields::flightDate(),


            CF::pieces(),
            CF::chargeableWeight(),
            CF::grossWeight(),
            CF::volume(),
            GeneralFields::natureOfGoods(),
            CommonFields::incoterms(),
            GeneralFields::incotermsLocation(),
            GeneralFields::airlineAlphaCode(),
            F::createSelect('OceanCarrierID', [
                'name' => 'Ocean Carrier',
                'options' => 'oceancarrier',
                'editWidth' => 'default'
            ]),
            F::createString('PlaceOfReceipt', [
                'name' => 'Place of Receipt',
                F::PARAM_AC_SOURCE => 'list_placeofreceipt',
                'upper' => true
            ]),
            GeneralFields::departureIata(),
            GeneralFields::departureLocationName(),
            F::createSelect('POLID', [
                'name' => 'POL',
                'title' => 'Port of Loading',
                'options' => "SELECT ID, Name, isPOL AS Active FROM port ORDER BY Name",
                'editWidth' => 'default'
            ]),
            GeneralFields::arrivalIata(),
            GeneralFields::arrivalLocationName(),
            F::createSelect('PODID', [
                'name' => 'POD',
                'title' => 'Port of Discharge',
                'options' => "SELECT ID, Name, isPOD AS Active FROM port ORDER BY Name",
                'editWidth' => 'default'
            ]),
            F::createSelect('PODTerminalID', [
                'name' => 'Terminal',
                'options' => 'portterminal'
            ]),
            F::createSelect('PortExpeditorID', [
                'name' => 'Port Expeditor',
                'options' => $portExpeditorOptions
            ]),
            F::createString('Destination', [
                F::PARAM_AC_SOURCE => 'list_destination',
                'upper' => true
            ]),
            F::createString('Vessel', [
                F::PARAM_AC_SOURCE => 'list_vessel',
                'upper' => true
            ]),
            F::createString('VoyageNbr', [
                'name' => 'Voyage',
                'upper' => true
            ]),

            CF::comments(),
            GeneralFields::vendorId(false, false),
            ShipmentFields::month(),
            F::createDate('ReadyForCarriageDate', [
                'name' => 'Ready For Carriage',
                'nameTable' => "Ready For\nCarriage",
                'editMandatory' => true,
                'ajax' => true
            ]),
            F::createDate('ETD', ['ajax' => true]),
            F::createDate('ATD', ['ajax' => true]),
            F::createDate('ETA', ['ajax' => true]),
            F::createDate('ATA', ['ajax' => true]),
            ShipmentFields::docsPrintedDate(),
            ShipmentFields::docsSentToAccountingDate(),
            F::createString('ContainerName', [
                'name' => 'Container №',
                'nameTable' => "Container №",
                'title' => 'Container №',
                'tableWidthExcel' => 20,
                'upper' => true
            ]),
            F::createSelect('ContainerType', [
                'name' => 'Container Type',
                'nameTable' => "Container\nType",
                'options' => "SELECT ID, Name, TEU FROM containertype ORDER BY OrderID",
                'empty' => true,
                'tableAlign' => 'center'
            ]),
            F::createDate('PUPDate', [
                'name' => 'Pick-Up',
                'nameTable' => "Pick-Up\nDate"
            ]),
            F::createDate('ERTDate', [
                'name' => 'Empty Rtn.',
                'nameTable' => "Empty Rtn.\nDate"
            ]),
            F::createSelect('TruckerID', [
                'name' => 'Trucker',
                'options' => $truckerOptions
            ]),
            F::createString('TruckNumber', [
                'name' => 'Truck №',
                'upper' => true
            ]),
            F::createCheckbox('EmailNoticeSent', [
                'name' => 'Email Notice sent'
            ]),
            GeneralFields::buIds(),
            F::createHtml('Trace', [
                'tableOrderBy' => false,
                'tableAlign' => 'center',
                'tableWidthExcel' => 0,
                'modes' => F::MODE_READONLY,
                'sqlSelectRequires' => ['TrafficID', 'MAWBNumber'],
                'sqlSelect' => '',
                'nameTableSettings' => 'Trace via quicktracing.com',
                'title' => 'Trace via quicktracing.com',
                'getHtmlFunction' => function ($value, $data) {
                    if (!isset($data['TrafficID'], $data['MAWBNumber'])) {
                        return false;
                    }

                    static $trafficTypes;
                    if ($trafficTypes === null) {
                        $trafficTypes = Db::getAssoc("SELECT ID, Type FROM traffic");
                    }

                    /*
                    static $ttAccount;
                    if ($ttAccount === null) {
                        $ttAccount = ApplicationAbstract::getParam('track-trace');
                    }

                    if ($ttAccount && isset($trafficTypes[$data['TrafficID']]) &&
                        $trafficTypes[$data['TrafficID']] == 'avia' && $data['MAWBNumber'] != ''
                    ) {
                        return '<a href="http://connect.track-trace.com/for/' . $ttAccount . '/aircargo/' .
                        rawurlencode($data['MAWBNumber']) .
                        '" target="_blank" alt="Trace" title="Trace via track-trace.com">' .
                        Img::png('tracktrace16') . '</a>';
                    }
                    */

                    if (isset($trafficTypes[$data['TrafficID']]) &&
                        $trafficTypes[$data['TrafficID']] === 'avia' && $data['MAWBNumber'] !== ''
                    ) {
                        return '<a href="http://quicktracing.com/api/air.php?token=tVRtbXgXHxE71UJTTkbPOE0qZrj6sMYF&awb=' .
                            rawurlencode((string) $data['MAWBNumber']) .
                            '" target="_blank" alt="Trace" title="Trace via quicktracing.com"><i class="fa fa-location-arrow"></i>' .
                            /*Img::png('tracktrace16') .*/
                            '</a>';
                    }

                    return false;

                }
            ]),

            F::createInt('QuotationID')
                ->setName('Quotation / Rate ID')
                ->setEditAlign('left')
                ->setEditWidth('medium')
                ->setZeroEmpty(true)
                ->setZeroEmptyExcel(true)
                ->setGetHtmlFunction(function ($value, array $data, $outHtml, $forTable) {
                    if (!$forTable && $value) {
                        if ($value > 700000) {
                            //Узнаем к какому клиенту относится этот Standart Rate
                            $customerId = Db::getOne("SELECT CustomerID FROM customer_standartrates WHERE ID = @i",
                                $value);
                            if ($customerId) {
                                //Формируем ссылку, только если такой тариф найден!
                                $outHtml = Link::create(Customer::class, $outHtml)
                                    ->view($customerId)
                                    ->title('View Standart Rate')
                                    ->findId('StandartRates', $value)
                                    ->getHtml();
                            } else {
                                $outHtml = '<span class="bad" title="Standart Rate ' . $value . ' not found">' . $outHtml . '</span>';
                            }
                        } else {
                            $outHtml = Link::create(Quotation::class, $outHtml)
                                ->view($value)
                                ->title('View Quotation')
                                ->getHtml();
                        }
                    }

                    return $outHtml;
                }),

            F::createMoneyWithCurrency('InsuranceAmount', ['name' => 'Insurance Amount', 'zeroEmpty' => true]),

            ShipmentFields::shipmentRequestNo(),

            F::createMoney('CacheCSIncomeinUSD', [
                'name' => 'Estimated<br>Income (USD)',
                'nameTableSettings' => 'Finance: Est. Income (USD)',
                'cssClassNegative' => 'bad'
            ]),
            F::createMoney('CacheCSExpensesinUSD', [
                'name' => 'Estimated<br>Expenses (USD)',
                'nameTableSettings' => 'Finance: Est. Expenses (USD)',
                'cssClassNegative' => 'bad'
            ]),
            F::createMoney('CacheEGPinUSD', [
                'name' => "Estimated\nGP (USD)",
                'nameTableSettings' => 'Finance: Estimated GP (USD)',
                'cssClassNegative' => 'bad'
            ]),
            F::createMoney('CacheIncomeinUSD', [
                'name' => 'Income (USD)',
                'nameTableSettings' => 'Finance: Income (USD)',
                'cssClassNegative' => 'bad'
            ]),
            F::createMoney('CacheExpensesinUSD', [
                'name' => 'Expenses (USD)',
                'nameTableSettings' => 'Finance: Expenses (USD)',
                'cssClassNegative' => 'bad'
            ]),
            F::createMoney('CacheGPinUSD', [
                'name' => 'GP (USD)',
                'nameTableSettings' => 'Finance: GP (USD)',
                'cssClassNegative' => 'bad'
            ]),
            F::createMoney('CacheGPDeviationinUSD', [
                'name' => 'Deviation (USD)',
                'nameTableSettings' => 'Finance: Deviation (USD)',
                'cssClassNegative' => 'bad'
            ]),

            ShipmentFields::cacts(),

            ShipmentFields::docsInventory()

        );

        $this->addFields(...ShipmentFields::fileFields());

        if (User::getGroupId() == Constant::USERGROUP_CS) {
            $this->getField('ResponsibleCS')->setParam('default', User::getId());
        } elseif (User::getGroupId() == Constant::USERGROUP_SALES) {
            $this->getField('ResponsibleSales')->setParam('default', User::getId());
        }
    }

    public function getDescriptionHtml()
    {
        return htmlspecialchars((string) $this->_data['MAWBNumber']) . ' / Shipment ' . $this->_data['Name'];
    }

    public function insertDb(array $fieldsData, $logMsg = '<em>Creation</em>'): void
    {
        //Попробуем определить номер файла (Name) исходя из трафика
        if (isset($fieldsData['TrafficID']) && !isset($fieldsData['Name'])) {
            $fieldsData['Name'] = $this->getAvailableFileNumber($fieldsData['TrafficID']);
        }
        parent::insertDb($fieldsData, $logMsg);

        //Если менялся номер файла, обновим кеширующее поле CacheUsedFileNumbersJSON
        if (isset($fieldsData['Name'])) {
            $this->updateUsedFileNumbers();
        }
    }

    /**
     * @desc проверяет возможность создать новый файл в указанном трафике и возвращает новый номер
     * @param int $trafficId
     * @return string
     */
    public function getAvailableFileNumber($trafficId)
    {
        //Проверим существование этого traffic
        $tr = Db::getRow("
              SELECT FileNumberFrom, FileNumberUntil, NextFileNumber 
              FROM traffic WHERE ID=@i
              FOR UPDATE", $trafficId
        );
        if (!$tr) {
            return false;
        }

        $newFileNumber = $tr['NextFileNumber'];
        //Провеним, укладывается ли новый номер файла в интервал трафика
        if ($newFileNumber > $tr['FileNumberUntil'] || $newFileNumber < $tr['FileNumberFrom']) {
            return false;
        }

        //Новый номер файла удовлетворяет условиям, применим его и передвинем внутренний указатель в traffic на 1 вперёд
        Db::query("UPDATE traffic SET NextFileNumber = NextFileNumber + 1 WHERE ID=" . (int)$trafficId);
        return $newFileNumber;
    }

    /**
     * Обновляет кеширующее поле CacheUsedFileNumbersJSON
     */
    private function updateUsedFileNumbers(): void
    {
        //Обновим JSON с номерами использованных номеров файлов по трафикам
        //Сначала узнаем текущий JSON и текущий номер файла
        //он же = новый номер файла, т.к. функция updateUsedFileNumbers вызывается после insert или update
        $shInfo = Db::getRow("SELECT TrafficID, Name, CacheUsedFileNumbersJSON FROM shipment WHERE ID = " . $this->_id);

        $usedFileNumbersJson = json_decode((string) $shInfo['CacheUsedFileNumbersJSON'], true);
        if (!is_array($usedFileNumbersJson)) {
            $usedFileNumbersJson = [];
        }
        $usedFileNumbersJson[$shInfo['TrafficID']] = $shInfo['Name'];

        Db::updateRow(
            ['CacheUsedFileNumbersJSON' => json_encode($usedFileNumbersJson)], 'shipment', $this->_id
        );
    }

    public function updateDb(array $fieldsData, $logMsg = '')
    {
        // Перед тем как сделать обновление перевозки, нужно проверить, меняется ли TrafficID?
        if (isset($fieldsData['TrafficID']) && !isset($fieldsData['Name'])) {
            //Узнаем текущую информацию по перевозке (перед обновлением)
            $shInfo =
                Db::getRow("SELECT Name, TrafficID, CacheUsedFileNumbersJSON FROM shipment WHERE ID = " . $this->_id);
            if ($shInfo['TrafficID'] != $fieldsData['TrafficID']) {
                //Поскольку поменялся трафик, нужно поменять и номер файла
                //Возможно, трафик который мы сейчас пытаемся включить уже использовался ранее для этого файла?
                //В этом случае нужно вернуть ранее использованный номер файла
                $usedFileNumbersJson = json_decode((string) $shInfo['CacheUsedFileNumbersJSON'], true);
                if (!is_array($usedFileNumbersJson)) {
                    $usedFileNumbersJson = [];
                }

                if (isset($usedFileNumbersJson[$fieldsData['TrafficID']])) {
                    $fieldsData['Name'] = $usedFileNumbersJson[$fieldsData['TrafficID']];
                } else {
                    $fieldsData['Name'] = $this->getAvailableFileNumber($fieldsData['TrafficID']);
                }

                //Изменили номер файла в $fieldsData['Name'], теперь перепривяжем инвойсы и переименуем папку
                Db::query(
                    "UPDATE vinvoiceline SET FileNumber = '" . $fieldsData['Name'] . "' WHERE FileNumber = " .
                    $shInfo['Name']
                );
                Db::query(
                    "UPDATE vinvoice_file_amount SET FileNumber = '" . $fieldsData['Name'] . "' WHERE FileNumber = " .
                    $shInfo['Name']
                );
                Db::query(
                    "UPDATE cinvoiceline SET FileNumber = '" . $fieldsData['Name'] . "' WHERE FileNumber = " .
                    $shInfo['Name']
                );
                Db::query(
                    "UPDATE cinvoice_file_amount SET FileNumber = '" . $fieldsData['Name'] . "' WHERE FileNumber = " .
                    $shInfo['Name']
                );

            }
        }

        $affectedRows = parent::updateDb($fieldsData, $logMsg);
        if ($affectedRows) {
            ShipmentCriteria::updateRelatedBasicData($this->_id);

            //Если менялся номер файла, обновим кеширующее поле CacheUsedFileNumbersJSON
            if (isset($fieldsData['Name'])) {
                $this->updateUsedFileNumbers();
            }
        }
        return $affectedRows;
    }

    public function deleteDb($dbLogMsg = ''): void
    {
        parent::deleteDb();

        // После удаления нужно удалить инвойсы, в которых указан этот файл, и только этот файл!
        $shipmentDescription = $this->getDescriptionHtml();
        $fileNumber = $this['Name'];

        $cinvIds = Db::getCol(
            "SELECT DISTINCT CInvoiceID 
             FROM cinvoiceline cil 
             WHERE FileNumber = @s
                 AND NOT EXISTS (SELECT * 
                                 FROM cinvoiceline cil2 
                                 WHERE FileNumber != @s
                                     AND cil2.CInvoiceID = cil.CInvoiceID)"
            , $fileNumber, $fileNumber
        );

        if ($cinvIds) {
            $cinvObjBase = new CInvoiceObj();
            foreach ($cinvIds as $cinvId) {
                $cinvObj = clone $cinvObjBase;
                $cinvObj->setId($cinvId);
                $cinvObj->loadData();
                $cinvObj->deleteDb('Deleted while shipment ' . $shipmentDescription . ' was deleted');
            }
        }

        $vinvIds = Db::getCol(
            "SELECT DISTINCT VInvoiceID 
             FROM vinvoiceline vil 
             WHERE FileNumber = @s
                  AND NOT EXISTS (SELECT * 
                                  FROM vinvoiceline vil2 
                                  WHERE FileNumber != @s
                                       AND vil2.VInvoiceID = vil.VInvoiceID)"
            , $fileNumber, $fileNumber
        );

        if ($vinvIds) {
            $vinvObjBase = new VInvoiceObj();
            foreach ($vinvIds as $vinvId) {
                $vinvObj = clone $vinvObjBase;
                $vinvObj->setId($vinvId);
                $vinvObj->loadData();
                $vinvObj->deleteDb('Deleted while shipment ' . $shipmentDescription . ' was deleted');
            }
        }
    }

    public function updateCacheAfterSubmit(array $oldData, ?Msg $msgBuff): void
    {
        $oldPrintedDate = $oldData['DocsPrintedDate'] ?? '0000-00-00';
        $newPrintedDate = $this->_data['DocsPrintedDate'];

        if ($oldPrintedDate !== $newPrintedDate && $newPrintedDate !== '0000-00-00') {
            // Нужно обновить поле cpayment.PrintedDate
            $cpaymentIds = Db::getCol(
                "SELECT DISTINCT cp.ID
                FROM cpayment cp
                    JOIN cpayment_cinvoice cpi ON cpi.CPaymentID = cp.ID
                    JOIN cinvoice_file_amount cfa ON cfa.CInvoiceID = cpi.CInvoiceID
                WHERE cfa.FileNumber = @i"
                , $this->_data['Name']
            );
            if ($cpaymentIds) {
                $cpaymentObjBase = new CPaymentObj();
                $updateData = ['PrintedDate' => $newPrintedDate];
                $dbLogMsg = 'Обновление даты печати документов на основе оплаты ' . $this->getLinkViewDialog();
                foreach ($cpaymentIds as $cpaymentId) {
                    $cpaymentObj = clone $cpaymentObjBase;
                    $cpaymentObj->setId($cpaymentId);
                    $affectedRows = $cpaymentObj->updateDb($updateData, $dbLogMsg);
                    if ($affectedRows && $msgBuff) {
                        $msgBuff->addInfo(
                            'В Customer Payment '
                            . $cpaymentObj->getLinkView()
                            . ' обновлена дата печати документов, новое значение: '
                            . FieldDate::beautify($newPrintedDate)
                        );
                    }
                }
            }
        }
    }
}
