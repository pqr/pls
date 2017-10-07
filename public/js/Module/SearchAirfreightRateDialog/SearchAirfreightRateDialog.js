var SearchAirfreightRateDialog;
(function (SearchAirfreightRateDialog) {
    var onChooseHandler;
    function sendRenderRequest(callback, vendorId, departureIata, arrivalIata, airlineAlphaCode, incoterms, validDate, chargeableWeight) {
        onChooseHandler = callback;
        var url = "?a=SearchAirfreightRateDialog&action=render";
        var filterValues = {
            VendorID: vendorId === "" ? "%" : vendorId,
            DepartureIATA: departureIata,
            ArrivalIATA: arrivalIata,
            AirlineAlphaCode: airlineAlphaCode === "" ? "%" : airlineAlphaCode,
            Incoterms: incoterms === "" ? "%" : incoterms,
            ValidDate: validDate,
            ChargeableWeight: chargeableWeight
        };
        $.post(url, { filterValues: JSON.stringify(filterValues) });
    }
    SearchAirfreightRateDialog.sendRenderRequest = sendRenderRequest;
    function buttonOkHandler(dialog, buttonId) {
        if (buttonId === "form") {
            dialog.unblockButtons();
            return;
        }
        var radioJson = Light.tables.get("SearchAirfreightRateDialogResultsTable").getSelectedRadio();
        if (!radioJson) {
            alert("Select Airfreight Rate");
            dialog.unblockButtons();
            return;
        }
        var rateData = JSON.parse(Light.tables.get("SearchAirfreightRateDialogResultsTable").getSelectedRadio());
        onChooseHandler(rateData);
        //Закрываем диалог после выбора тарифа
        dialog.close();
    }
    SearchAirfreightRateDialog.buttonOkHandler = buttonOkHandler;
})(SearchAirfreightRateDialog || (SearchAirfreightRateDialog = {}));
//# sourceMappingURL=SearchAirfreightRateDialog.js.map