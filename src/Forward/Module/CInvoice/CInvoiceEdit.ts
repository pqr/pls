namespace CInvoice {
    //При изменении валюты в шапке её нужно изменить в специальном span ниже в залоговке подтаблицы с линиями
    Light.module.onFieldChange("CurrencyISO", function(newValue) {
        $("#cinvoice-currency").html(newValue);
    });

    var updateInvoiceTo = function() {
        var customerAccountId = Light.module.getFieldValue("CustomerAccountID");
        if (customerAccountId != 0) {
            $.ajax("?a=CInvoice&ps=getCustomerAccountInvoiceTo&CustomerAccountID=" + customerAccountId).done(function(
                data
            ) {
                Light.module.setFieldValue("InvoiceTo", data);
            });
        } else {
            Light.module.setFieldValue("InvoiceTo", "");
        }
    };

    //При изменении аккаунта нужно подтянуть новый адрес Invoice To
    Light.module.onFieldChange("CustomerAccountID", updateInvoiceTo);
    //При изменении клиента по цепочке изменится и аккаунт (т.к. SelectChild) нужно подтянуть новый адрес Invoice To
    //Light.module.onFieldChange('CustomerID', updateInvoiceTo);

    //Метод для обновления поля Amount в шапке счёта, которое должно обновляться сразу после редактирования подтаблицы
    var $amount = $("#cinvoice-amount");
    var renderAmount = function(amount) {
        $amount.html(Light.getBeautyMoney(amount));
    };

    var linesSubtable = Light.subtables.get("Lines");

    //При пересчёте Total Amount в подтаблице, его также нужно записать и в шапку инвойса
    linesSubtable.onFieldCalcTotal("Amount", renderAmount);

    //В начальный момент после загрузки экрана редактирования обновим поле Amount исходя из подтаблицы
    renderAmount(linesSubtable.getTotalValue("Amount"));

    /**
     * Вычисляет Amount на основе Price и Quantity
     * @param {int} rowNum
     */
    function recalculateRow(rowNum) {
        var quantity = linesSubtable.getFieldValue(rowNum, "Quantity");
        var price = linesSubtable.getFieldValue(rowNum, "Price");
        var amount = price * quantity;
        linesSubtable.setFieldValue(rowNum, "Amount", amount);
    }

    linesSubtable.onFieldChange("Quantity", recalculateRow);
    linesSubtable.onFieldChange("Price", recalculateRow);
    linesSubtable.onAdd(recalculateRow);

    function showHideConvertToRub() {
        var companyId = Light.module.getFieldValue("CompanyID");
        var currencyIso = Light.module.getFieldValue("CurrencyISO");

        var hideButton = (companyId != 4 && companyId != 5) || currencyIso === "RUB";
        $("#converttorub-tr").toggleClass("display-none", hideButton);
    }

    function showHideOnlyOoo() {
        var companyId = Light.module.getFieldValue("CompanyID");

        var hideOoo = companyId != 4 && companyId != 5 && companyId != 11 && companyId != 12;
        $(".only-ooo").toggleClass("display-none", hideOoo);
        $(".only-not-ooo").toggleClass("display-none", !hideOoo);
    }


    Light.module.onFieldChange("CompanyID", showHideConvertToRub);
    Light.module.onFieldChange("CompanyID", showHideOnlyOoo);
    Light.module.onFieldChange("CurrencyISO", showHideConvertToRub);
    showHideConvertToRub();
    showHideOnlyOoo();

    function getConverRateTuRubResultRate() {
        var resultRate = Light.parseFloat($("#converttorub__rate").val());
        var percent = Light.parseFloat(
            $("#converttorub__ratepercent")
                .val()
                .replace("%", "")
        );
        if (percent > 0) {
            resultRate = resultRate * (1 + percent / 100);
        }
        return resultRate;
    }

    function converRateTuRubRenderResult() {
        var amountInCurrency = linesSubtable.getTotalValue("Amount");
        var resultRate = getConverRateTuRubResultRate();
        var amountInRub = Light.round(amountInCurrency * resultRate);
        $("#converttorub__result").html(Light.getBeautyMoney(amountInRub, false, "RUB"));
    }

    function loadCurrRateFromServer() {
        var date = $("#converttorub__date").val();
        var currencyIso = Light.module.getFieldValue("CurrencyISO");
        $.ajax("?a=CInvoice&ps=GetRate&date=" + date + "&currency=" + currencyIso)
            .done(function(rate) {
                $("#converttorub__rate").val(rate);
                Light.toastSuccess(
                    "Currency rate for " + currencyIso + " on " + date + ": <strong>" + rate + "</strong>"
                );
                converRateTuRubRenderResult();
            })
            .fail(function() {
                Light.toastError("Cant load currency rate for " + currencyIso + " on " + date);
            });
    }

    $("#converttorub-tr")
        .find("a")
        .on("click", function(e) {
            e.preventDefault();

            var dialog = new Light.Dialog(
                '<table class="form">' +
                    "<tr><th>From</th><td>" +
                    Light.getBeautyMoney(
                        linesSubtable.getTotalValue("Amount"),
                        false,
                        Light.module.getFieldValue("CurrencyISO")
                    ) +
                    "</td></tr>" +
                    '<tr><th><label for="converttorub__date">Date</label></th><td><input class="input-date" id="converttorub__date"></td></tr>' +
                    '<tr><th><label for="converttorub__rate">Rate</label></th><td><input class="input-float input-small" id="converttorub__rate"> <select id="converttorub__ratepercent" class="input-auto"><option value=""></option><option value="0%">+0%</option><option value="1,5%">+1,5%</option><option value="2%">+2%</option><option value="3%">+3%</option></select></td></tr>' +
                    '<tr><th>Result</th><td id="converttorub__result"></td></tr>' +
                    "</table>",
                {
                    buttonOkText: "Convert to RUB",
                    buttonOkHandler: function(dialog) {
                        var resultRate = getConverRateTuRubResultRate();

                        // Пройдём по всем строкам и увеличим суммы на выбранный курс
                        linesSubtable.eachRow(function(rowNum) {
                            var price = linesSubtable.getFieldValue(rowNum, "Price");
                            var amount = linesSubtable.getFieldValue(rowNum, "Amount");
                            linesSubtable.setFieldValue(rowNum, "Price", Light.round(price * resultRate));
                            linesSubtable.setFieldValue(rowNum, "Amount", Light.round(amount * resultRate));
                        });

                        Light.module.setFieldValue("CurrencyISO", "RUB");
                        Light.module.setFieldValue("RatePercent", $("#converttorub__ratepercent").val());

                        dialog.close();
                    }
                }
            );

            $("#converttorub__date")
                .val(Light.getBeautyDate(Light.module.getFieldValue("DocDate")))
                .on("change", function(e) {
                    loadCurrRateFromServer();
                });

            $("#converttorub__rate").on("change", function(e) {
                converRateTuRubRenderResult();
            });

            $("#converttorub__ratepercent")
                .val(Light.module.getFieldValue("RatePercent"))
                .on("change", function(e) {
                    converRateTuRubRenderResult();
                });

            dialog.open();

            loadCurrRateFromServer();
        });
}
