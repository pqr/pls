declare const Light: any;

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
        .on("click", ".apply-addon", function(e) {
            e.preventDefault();

            //При клике на ссылку "Apply Addon" нужно отобразить дилог наложения Addon
            showApplyAddonDialog();
        });

    var showApplyAddonDialog = function() {
        var form = Light.forms.get("StandartRateDialog-form");
        var url =
            "?a=Customer&id=" +
            Light.module.getObjectId() +
            "&func=StandartRateDialog&action=applyaddon&CurrencyISO=" +
            form.getFieldValue("SellingCurrencyISO");
        $.ajax(url);
    };

    $(".standartrates__exporttoexcel").on("click", function(e) {
        e.preventDefault();

        if (!Light.table.has("StandartRates")) {
            return;
        }

        const standartRatesTable = Light.table.get("StandartRates");
        if (standartRatesTable.getDivNode().find("tr").length === 0) {
            alert('Для данного клиента нет ставок. Нажмите кнопку "+ New Standart Rate", чтобы создать.');
            return;
        }

        const selectedRateIds = standartRatesTable.getSelectedValues();
        if (selectedRateIds.length === 0) {
            alert("Отметьте нужные ставки галочками");
            return;
        }

        const url =
            "?a=Customer&id=" +
            Light.module.getObjectId() +
            "&func=StandartRatesExcel&RateIDs=" +
            selectedRateIds.join(",");

        document.location.href = url;
    });
});
