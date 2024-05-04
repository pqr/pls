<?php

namespace Forward\Field;

use Forward\Classes\Constant;
use Forward\Module\Shipment\Shipment;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\File;
use Light\FilesFolder;

class PaymentFields
{
    public static function name()
    {
        return F::create('Name', F::TYPE_STRING, ['name' => 'Reference']);
    }

    public static function paymentDate()
    {
        return F::createDate('PaymentDate', ['name' => 'Payment Date', 'default' => date('Y-m-d')]);
    }

    public static function cacheOverpayAmount()
    {
        return F::createMoney('CacheOverpayAmount', [
            'name' => 'Overpay Amount',
            'title' => 'Сумма переплаты',
            'modes' => F::MODE_READONLY,
            'nameTable' => "Overpay\nAmount",
            'sqlSelectRequires' => 'OverpayCleared',
            'viewCssClass' => function ($value, array $data) {
                if (isset($data['OverpayCleared']) && !$data['OverpayCleared'] && $value != '0.00') {
                    return 'bad';
                }

                return '';
            },

            'viewTitle' => function ($value, array $data) {
                if ($value != '0.00') {
                    if (isset($data['OverpayCleared']) && $data['OverpayCleared']) {
                        return 'Переплата погашена';
                    } else {
                        return 'Переплата НЕ погашена';
                    }
                }

                return '';
            },
            'zeroEmpty' => true
        ]);
    }

    public static function overpayCleared()
    {
        return F::createCheckbox(
            'OverpayCleared',
            [
                'name' => 'Overpay Cleared',
                'title' => 'Переплата погашена',
                'nameTable' => "Overpay\nCleared",
                'ajax' => true
            ]
        );
    }

    /**
     * Добавить в Customer Payments колонки (в Table layout settings) как в модуле
     * Shipments: "Файл: 3 Акт без подписи" и "Файл: 4 Акт подписанный".
     * Если оплата содержит в себе несколько счетов и по некоторым счетам акты есть, а по другим нет,
     * в этом случае нужно выводить слово "Частично".
     * @return \Light\Field\FieldSelect
     */
    public static function shipmentFileField(int $folderNo)
    {
        return F::createSelect("File$folderNo", array_merge(ShipmentFields::fileField($folderNo)->initialParams, [
            'empty' => true,
            'tableAlign' => 'center',
            'options' => [
                1 => ['text' => 'Нет', 'cssClass' => 'bad'],
                2 => ['text' => 'Частично', 'cssClass' => 'warning'],
                3 => ['text' => 'Да', 'cssClass' => 'good'],
            ],
            'calcFunction' => function ($value, array $data) use ($folderNo) {
                if (!isset($data['ID'])) {
                    return '';
                }

                $shipmentIds = Db::getCol("SELECT DISTINCT shipment.ID 
                      FROM cpayment_cinvoice cpi, cinvoice_file_amount cfa, shipment
                      WHERE cpi.CInvoiceID = cfa.CInvoiceID
                          AND cfa.FileNumber = shipment.Name
                          AND cpi.CPaymentID = @i"
                    , $data['ID']
                );
                if (!$shipmentIds) {
                    return '';
                }

                $folderNoName = Constant::$standardFolders[$folderNo];

                $somethingEmpty = null;
                $somethingNotEmpty = null;
                foreach ($shipmentIds as $shipmentId) {
                    $folderRelative = FilesFolder::getRelativeModule(Shipment::class, $shipmentId);
                    if (FilesFolder::hasFiles("$folderRelative/$folderNoName")) {
                        $somethingNotEmpty = true;
                    } else {
                        $somethingEmpty = true;
                    }

                    if ($somethingEmpty === true && $somethingNotEmpty === true) {
                        // Если что-то пусто, а что-то не пусто, то точно "Частично", дальше проверять нет смысла
                        return 2;
                    }
                }

                // Осталось два варианта: либо олностью пусто, либо полностью заполнено
                return $somethingEmpty ? 1 : 3;
            }
        ]));
    }
}
