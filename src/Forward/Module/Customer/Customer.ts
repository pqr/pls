namespace Customer {
    export function onApplyAddonDialogSubmit(dialog) {
        //Возьмём все значения из диалога и применим их к блоку selling
        //На для начала узнаем курс по которому пересчитывать
        var form = Light.forms.get("StandartRateDialog-form");
        [
            "MinAmount",
            "AmountN",
            "Amount45",
            "Amount100",
            "Amount200",
            "Amount300",
            "Amount500",
            "Amount750",
            "Amount1000",
            "Amount2000"
        ].forEach(function(fieldKey) {
            var addonValue = Light.getFieldValue(dialog.$divContent, "Addon" + fieldKey);
            var oldSellingValue = form.getFieldValue("Selling" + fieldKey);
            var newSellingValue = oldSellingValue + addonValue;
            form.setFieldValue("Selling" + fieldKey, newSellingValue);
        });

        dialog.close();
    }

    if (Light.module.getState() === "edit" || Light.module.getState() === "addnew") {
        Light.module.onFieldChange("UseDefaultAddon", function(newValue) {
            $("#addons-table").toggleClass("display-none", newValue);
        });
    }
}

$(function() {
    //При двойном клике на строку таблицы нужно её подсветить и отобразить диалоговое окно для редактирования этой ставки
    var $srTableDiv = $("#StandartRates-table-div");
    var ajaxInProgress = false;
    $srTableDiv.on("dblclick", "table.table > tbody > tr", function(e) {
        if (ajaxInProgress) {
            return;
        }

        var $tr = $(this);
        var $table = $srTableDiv.children("table.table");
        //Снимаем окраску findid внутри данной таблицы
        $table.find("tr.findid").removeClass("findid");
        //Перемещаем окраску findid на строку, по которой был совершен двойной клик
        $tr.addClass("findid");

        var srid = $tr.attr("data-linkid");
        var url = "?a=Customer&id=" + Light.module.getObjectId() + "&func=StandartRateDialog&action=edit&srid=" + srid;

        $.ajax(url).always(function() {
            //После завершения ajax запроса убираем со ссылки специальный класс, который предовтвращал двойное нажатие
            ajaxInProgress = false;
        });
    });

    //При нажатии на ссылку "Copy to Selling", которая находится в блоке диалоговом окне
    $("body")
        .on("click", ".copy-to-selling-link", function(e) {
            e.preventDefault();

            var form = Light.forms.get("StandartRateDialog-form");

            [
                "MinAmount",
                "AmountN",
                "Amount45",
                "Amount100",
                "Amount200",
                "Amount300",
                "Amount500",
                "Amount750",
                "Amount1000",
                "Amount2000",
                "FSCAmount",
                "SSCAmount",
                "CurrencyISO"
            ].forEach(function(fieldKey) {
                form.setFieldValue("Selling" + fieldKey, form.getFieldValue("Buying" + fieldKey));
            });

            var buyingSurchargesSubtalbe = Light.subtables.get("BuyingSurcharges");
            var sellingSurchargesSubtalbe = Light.subtables.get("SellingSurcharges");

            var data = buyingSurchargesSubtalbe.getData();
            sellingSurchargesSubtalbe.empty();
            sellingSurchargesSubtalbe.addRows(data);
        })
        .on("click", ".search-airfreightrate-link", function(e) {
            e.preventDefault();

            //При клике на ссылку "Search Arifreight Rate" нужно отправить ajax запрос на сервер, который вернёт диалоговое
            //окно для поиска покупной ставки
            //Обратимся к форме, чтобы получить её данные
            var form = Light.forms.get("StandartRateDialog-form");
            SearchAirfreightRateDialog.sendRenderRequest(
                onSearchAirfreightRateDialogChoose,
                form.getFieldValue("VendorID"),
                form.getFieldValue("DepartureIATA"),
                form.getFieldValue("ArrivalIATA"),
                form.getFieldValue("AirlineAlphaCode"),
                form.getFieldValue("Incoterms"),
                form.getFieldValue("ValidFrom")
            );
        })
        .on("click", ".apply-addon", function(e) {
            e.preventDefault();

            //При клике на ссылку "Apply Addon" нужно отобразить дилог наложения Addon
            showApplyAddonDialog();
        });

    //Метод вызывается когда в диалоговом окне поиска покупных ставок пользователь сделал какой-то выбор
    var onSearchAirfreightRateDialogChoose = function(rateData) {
        var form = Light.forms.get("StandartRateDialog-form");
        [
            "MinAmount",
            "AmountN",
            "Amount45",
            "Amount100",
            "Amount200",
            "Amount300",
            "Amount500",
            "Amount750",
            "Amount1000",
            "Amount2000",
            "FSCAmount",
            "SSCAmount",
            "CurrencyISO"
        ].forEach(function(fieldKey) {
            form.setFieldValue("Buying" + fieldKey, rateData[fieldKey]);
            form.setFieldValue("Selling" + fieldKey, rateData[fieldKey]);
        });

        //Теперь скопируем все надбавки и в selling и в buying часть
        var buyingSurchargesSubtalbe = Light.subtables.get("BuyingSurcharges");
        var sellingSurchargesSubtalbe = Light.subtables.get("SellingSurcharges");

        sellingSurchargesSubtalbe.empty();
        buyingSurchargesSubtalbe.empty();

        sellingSurchargesSubtalbe.addRows(rateData.Surcharges);
        buyingSurchargesSubtalbe.addRows(rateData.Surcharges);

        //Отдельно заполним остальные поля
        form.setFieldValue("AirlineAlphaCode", rateData.AirlineAlphaCode);
        form.setFieldValue("TransitTime", rateData.TransitTime);
        form.setFieldValue("VendorID", rateData.VendorID);

        //Самое хитрое поле - это комментарии.
        //Нужно взять комментарии из покупного тарифа и приклеить к нему комментарии из всех надбавок
        var comments = [];
        if (rateData.Comments) {
            comments.push(rateData.Comments);
        }

        rateData.Surcharges.forEach(function(surcharge) {
            if (surcharge.Comments) {
                comments.push(surcharge.Name + ": " + surcharge.Comments);
            }
        });

        form.setFieldValue("CommentsInternal", comments.join("\n"));

        //После того, как все данные скопированы в обе части - buying и selling нужно наложить addon
        showApplyAddonDialog();
    };

    var showApplyAddonDialog = function() {
        var form = Light.forms.get("StandartRateDialog-form");
        var url =
            "?a=Customer&id=" +
            Light.module.getObjectId() +
            "&func=StandartRateDialog&action=applyaddon&CurrencyISO=" +
            form.getFieldValue("SellingCurrencyISO");
        $.ajax(url);
    };
});
