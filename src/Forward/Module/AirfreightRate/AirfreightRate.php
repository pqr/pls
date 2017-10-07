<?php

namespace Forward\Module\AirfreightRate;

use Forward\Field\GeneralFields;
use Light\ModuleBase\ModuleAbstract;
use Light\Msg;
use Light\Session;
use Light\User;

class AirfreightRate extends ModuleAbstract
{
    /**
     * Метод для переопределения в классах потомках - здесь определяются поля и настройки модуля
     */
    public function __construct()
    {
        parent::__construct();

        $this->_textObjectName = 'Airfreight Rate';
    }

    protected function initFilters()
    {
        $this->addFilters(
            GeneralFields::vendorId(),
            GeneralFields::departureIata(),
            GeneralFields::departureLocationName(),
            GeneralFields::arrivalIata(),
            GeneralFields::arrivalLocationName(),
            GeneralFields::airlineAlphaCode()
        );

        $this->addFiltersDatesFromUntil('Valid', ['name' => 'Validity', 'asInterval' => true]);

        $this->_filtersDefaultTemplateArray = [
            ['VendorID'],
            ['Valid'],
            [
                'AirlineAlphaCode',
                'DepartureLocationName' => GeneralFields::filterTemplateLocation('Departure'),
                'ArrivalLocationName' => GeneralFields::filterTemplateLocation('Arrival'),
            ]
        ];
    }

    protected function getListButtons()
    {
        $out = parent::getListButtons();

        $out[] = [
            'text' => 'Upload Excel',
            'linkCssClass' => 'upload-excel',
            'url' => '#'
        ];

        return $out;
    }

    public function proceedRatesUploadExcel()
    {
        if ($_FILES['ratesfile']['tmp_name'] != '') {
            $msgBuff = new Msg();
            $ext = pathinfo($_FILES['ratesfile']['name'], PATHINFO_EXTENSION);
            $newTmpFile = LIGHT_DIR_TEMP . '/AirfreightRate_User' . User::getId() . '_' . date('Y_m_d_H_i_s') . '.' . $ext;
            move_uploaded_file($_FILES['ratesfile']['tmp_name'], $newTmpFile);
            $importObj = new AirfreightRateExcelImport();
            $importObj($newTmpFile, $msgBuff);

            Session::flashNormal($msgBuff->getHtml());
        } else {
            Session::flashError('Error uploading file');
        }

        $this->redirectToList();
    }
}