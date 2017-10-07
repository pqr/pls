var Shipment;
(function (Shipment) {
    var customersInfo = Light.getPageParam("customersInfo");
    var trafficTypes = Light.getPageParam("trafficTypes");
    var airlineCodes = Light.getPageParam("airlineCodes");
    Light.module.onFieldChange("CustomerID", function (newCustomerId) {
        var $prepaidDiv = $("#prepaid-div");
        if (typeof customersInfo[newCustomerId] !== "undefined") {
            var customer = customersInfo[newCustomerId];
            Light.module.setFieldValue("ResponsibleSales", customer.s);
            Light.module.setFieldValue("ResponsibleCS", customer.c);
            //Если это prepaidCustomer - нужно отобразить блок 'Attention: Prepaid Customer'
            $prepaidDiv.toggleClass("display-none", customer.p == "1");
        }
        else {
            //Неизвестный клиент, заодно скрываем блок prepaid customer
            $prepaidDiv.removeClass("display-none");
        }
    });
    $("#MAWBNumber1").on("keyup", function () {
        var newValue = this.value;
        if (newValue.length === 3) {
            var airlineAplhaCode = airlineCodes[newValue];
            if (airlineAplhaCode) {
                Light.module.setFieldValue("AirlineAlphaCode", airlineAplhaCode);
                Light.module.setFieldValue("AirlineAlphaCodeInput", airlineAplhaCode);
            }
            $("#MAWBNumber2").focus();
        }
    });
    function onTrafficChange() {
        var trafficId = Light.module.getFieldValue("TrafficID");
        var hideFullMawb = trafficTypes[trafficId] === "avia";
        $("#full-mawb-span").toggleClass("display-none", hideFullMawb);
        $("#split-mawb-span").toggleClass("display-none", !hideFullMawb);
        //В зависимости от типа Ocean скроем или отобразим Ocean поля
        var hideOcean = trafficTypes[trafficId] !== "ocean";
        var hideAvia = !hideOcean;
        $("#ocean-div").toggleClass("display-none", hideOcean);
        $("#OceanCarrierID-tr").toggleClass("display-none", hideOcean);
        $("#POLID-tr").toggleClass("display-none", hideOcean);
        $("#PODID-tr").toggleClass("display-none", hideOcean);
        $("#AirlineAlphaCode-tr").toggleClass("display-none", hideAvia);
        $("#DepartureIATA-tr").toggleClass("display-none", hideAvia);
        $("#ArrivalIATA-tr").toggleClass("display-none", hideAvia);
    }
    Light.module.onFieldChange("TrafficID", onTrafficChange);
    onTrafficChange();
    function onCompanyChange() {
        var companyId = Light.module.getFieldValue("CompanyID");
        // Отобразим или скроем поле Заявка
        var hideShipmentRequest = companyId != 4 && companyId != 5 && companyId != 11 && companyId != 12;
        $("#shipmentrequestno-tr").toggleClass("display-none", hideShipmentRequest);
    }
    Light.module.onFieldChange("CompanyID", onCompanyChange);
    onCompanyChange();
})(Shipment || (Shipment = {}));
//# sourceMappingURL=ShipmentEdit.js.map