namespace Quotation {
    var cargoSubtable = Light.subtables.get("Cargo");
    var incomeSubtable = Light.subtables.get("Income");
    var expensesSubtable = Light.subtables.get("Expenses");

    var recalculateCargoRow = function(rowNum) {
        var length = cargoSubtable.getFieldValue(rowNum, "Length");
        var width = cargoSubtable.getFieldValue(rowNum, "Width");
        var height = cargoSubtable.getFieldValue(rowNum, "Height");
        var pieces = cargoSubtable.getFieldValue(rowNum, "Pieces");

        var volume = length * width * height * pieces / 1000000;

        var cargoUom = Light.module.getFieldValue("CargoUOM");
        if (cargoUom == "inch") {
            volume = volume * 2.54 * 2.54 * 2.54;
        }

        //Записываем новое значение в подтаблицу. Метод calcTotal для этого столбца будет вызван автоматически внутри
        cargoSubtable.setFieldValue(rowNum, "Volume", volume);
    };

    cargoSubtable.onFieldChange("Length", recalculateCargoRow);
    cargoSubtable.onFieldChange("Width", recalculateCargoRow);
    cargoSubtable.onFieldChange("Height", recalculateCargoRow);
    cargoSubtable.onFieldChange("Pieces", recalculateCargoRow);

    var reacalculateChargeableWeight = function() {
        //Исходя из объёма расчитаем соответствующий ему newChargeableWeight - округление вверх до целого
        var totalVolume = cargoSubtable.getTotalValue("Volume");
        var newChargeableWeight = Math.ceil(totalVolume * 166.66);

        //Теперь нужно сравнить получившийся newChargeableWeight c GrossWeight, который заполняется вручную
        var grossWeight = Light.module.getFieldValue("GrossWeight");

        //Суть такова, что в поле Chargeable Weight всегда должно быть большее значение, между введённым вручную
        //GrossWeight и значением расчитанным на основе объёма
        if (newChargeableWeight < grossWeight) {
            newChargeableWeight = grossWeight;
        }

        //Обновим значение ChargeableWeight
        Light.module.setFieldValue("ChargeableWeight", newChargeableWeight);

        //Обнови все суммы в котировке
        recalculateAmounts();
    };

    /**
     * Метод вызывается при изменении Total Volume или при измении Gross Weight
     */
    var recalculateDensity = function() {
        var totalVolume = cargoSubtable.getTotalValue("Volume");
        var grossWeight = Light.module.getFieldValue("GrossWeight");

        var densityKgPerM3 = totalVolume == 0 ? 0 : Light.round(grossWeight / totalVolume, 0);
        var densityM3PerTon = grossWeight == 0 ? 0 : Light.round(totalVolume / grossWeight * 1000, 2);

        $("#DensityKgPerM3-span").html(densityKgPerM3);
        $("#DensityM3PerTon-span").html(densityM3PerTon);
    };

    /**
     * Пересчитывает все суммы в котировке. Это нужно при изменении веса и при вставке данных через функцию Search Airfreight Rate
     */
    var recalculateAmounts = function() {
        incomeSubtable.eachRow(function(rowNum) {
            //Передаём параметр recalculateTotal = false, чтобы при кажном обновлении строки не пересчитывать весь total
            recalculateIncomeExpensesRow.call(this, rowNum, false);
        });
        incomeSubtable.calcTotal("Amount");

        expensesSubtable.eachRow(function(rowNum) {
            //Передаём параметр recalculateTotal = false, чтобы при кажном обновлении строки не пересчитывать весь total
            recalculateIncomeExpensesRow.call(this, rowNum, false);
        });
        expensesSubtable.calcTotal("Amount");
    };

    //При обновлении Volume нужно пересчитать ChargeableWeight и затем пересчитать все суммы
    cargoSubtable.onFieldCalcTotal("Volume", reacalculateChargeableWeight);
    cargoSubtable.onFieldCalcTotal("Volume", recalculateDensity);

    //При ручном изменении GrossWeight нужно опять же пересчитать ChargeableWeight и затем пересчитать все суммы
    Light.module.onFieldChange("GrossWeight", reacalculateChargeableWeight);
    Light.module.onFieldChange("GrossWeight", recalculateDensity);

    //При ручном изменении ChargeableWeight нужно пересчитать все суммы
    Light.module.onFieldChange("ChargeableWeight", recalculateAmounts);

    /**
     * Этот метод вызывается в контексте Subtable
     * @param {int} rowNum
     */
    var recalculateIncomeExpensesRow = function(rowNum) {
        var subtable = this;
        var price = subtable.getFieldValue(rowNum, "Price");
        var per = subtable.getFieldValue(rowNum, "Per");
        var multiplier = 1;
        switch (per) {
            case "GW":
                multiplier = Light.module.getFieldValue("GrossWeight");
                break;
            case "CW":
                multiplier = Light.module.getFieldValue("ChargeableWeight");
                break;
            case "MAWB":
                multiplier = 1;
                break;
            case "Piece":
                multiplier = cargoSubtable.getTotalValue("Pieces");
                break;
        }

        var amount = price * multiplier;
        var minAmount = subtable.getFieldValue(rowNum, "MinAmount");
        if (amount < minAmount) {
            amount = minAmount;
        }

        subtable.setFieldValue(rowNum, "Amount", amount);
    };

    var renderGp = function() {
        var income = incomeSubtable.getTotalValue("Amount");
        var expenses = expensesSubtable.getTotalValue("Amount");
        var gp = income - expenses;
        $("#gp-span").html(Light.getBeautyMoneyShort(gp));
    };

    incomeSubtable.onFieldChange("Price", recalculateIncomeExpensesRow);
    incomeSubtable.onFieldChange("Per", recalculateIncomeExpensesRow);
    incomeSubtable.onFieldChange("MinAmount", recalculateIncomeExpensesRow);
    incomeSubtable.onFieldCalcTotal("Amount", renderGp);
    expensesSubtable.onFieldChange("Price", recalculateIncomeExpensesRow);
    expensesSubtable.onFieldChange("Per", recalculateIncomeExpensesRow);
    expensesSubtable.onFieldChange("MinAmount", recalculateIncomeExpensesRow);
    expensesSubtable.onFieldCalcTotal("Amount", renderGp);

    Light.module.onFieldChange("CurrencyISO", function(newValue) {
        $("#currency-span").text(newValue);
    });

    Light.module.onFieldChange("Status", function(newValue) {
        if (newValue == "merged") {
            $("#mergedid-span").removeClass("display-none");
        } else {
            $("#mergedid-span").addClass("display-none");
        }
    });

    Light.module.onFieldChange("CargoUOM", function(newValue) {
        //При смене единиц измерения для подтаблицы cargo для начала обновим заголовки таблицы
        var $cargoDiv = cargoSubtable.getDivNode();
        $cargoDiv.find("th[data-fieldkey=Length]").html("Length<br>(" + newValue + ")");
        $cargoDiv.find("th[data-fieldkey=Width]").html("Width<br>(" + newValue + ")");
        $cargoDiv.find("th[data-fieldkey=Height]").html("Height<br>(" + newValue + ")");

        //Пересчитаем все строки в новую размерность
        cargoSubtable.eachRow(function(rowNum) {
            recalculateCargoRow(rowNum);
        });
    });

    $("#search-airfreightrate-link").on("click", function(e) {
        e.preventDefault();

        //При клике на ссылку "Search Arifreight Rate" нужно отправить ajax запрос на сервер, который вернёт диалоговое
        //окно для поиска покупной ставки
        SearchAirfreightRateDialog.sendRenderRequest(
            onSearchAirfreightRateDialogChoose,
            Light.module.getFieldValue("VendorID"),
            Light.module.getFieldValue("DepartureIATA"),
            Light.module.getFieldValue("ArrivalIATA"),
            Light.module.getFieldValue("AirlineAlphaCode"),
            Light.module.getFieldValue("Incoterms"),
            Light.module.getFieldValue("ValidFrom"),
            Light.module.getFieldValue("ChargeableWeight")
        );
    });

    var onSearchAirfreightRateDialogChoose = function(rateData) {
        expensesSubtable.empty();

        var quotationCurrencyIso = Light.module.getFieldValue("CurrencyISO");

        //А здесь мы заполняем только расходную часть на основе диалога Search Airfreight Rate
        var weight = Light.module.getFieldValue("ChargeableWeight");
        //Какую цену применить как основную?
        var interval = _([2000, 1000, 750, 500, 300, 200, 100, 45]).find(function(interval) {
            return weight >= interval;
        });
        var amountFieldKey = interval ? "Amount" + interval : "AmountN";

        //Добавим обсновную строку Airfreight
        expensesSubtable.addRow(
            {
                Name: "Airfreight",
                Price: Light.currency.convertValue(
                    rateData[amountFieldKey],
                    rateData.CurrencyISO,
                    quotationCurrencyIso
                ),
                Per: "CW",
                MinAmount: rateData.MinAmount
            },
            true
        );

        //Добавим FSC и SSC
        ["FSC", "SSC"].forEach(function(fscOrSsc) {
            var price = Light.parseFloat(rateData[fscOrSsc + "Amount"]);
            if (price) {
                expensesSubtable.addRow(
                    {
                        Name: fscOrSsc,
                        Price: Light.currency.convertValue(price, rateData.CurrencyISO, quotationCurrencyIso),
                        Per: "CW"
                    },
                    true
                );
            }
        });

        //Теперь добавим все надбавки
        rateData.Surcharges.forEach(function(surcharge) {
            var baseAmount = Light.parseFloat(surcharge.BaseAmount);
            var perKgAmount = Light.parseFloat(surcharge.PerKgAmount);

            if (baseAmount != 0) {
                //Если есть какая-то база, то нужно добавить дополнительную строку Per MAWB
                expensesSubtable.addRow(
                    {
                        Name: surcharge.Name + (perKgAmount ? " Base Amount" : ""),
                        Price: Light.currency.convertValue(
                            baseAmount + weight * Light.parseFloat(surcharge.PerKgAmount),
                            surcharge.CurrencyISO,
                            quotationCurrencyIso
                        ),
                        Per: "MAWB"
                    },
                    true
                );
            }

            if (perKgAmount) {
                expensesSubtable.addRow(
                    {
                        Name: surcharge.Name,
                        Price: Light.currency.convertValue(perKgAmount, surcharge.CurrencyISO, quotationCurrencyIso),
                        Per: "CW",
                        MinAmount: surcharge.MinAmount
                    },
                    true
                );
            }
        });

        //Теперь можно пересчитать все строки и итоговый total
        recalculateAmounts();
    };

    //При нажатии на ссылку "Copy to Selling", которая находится в блоке Buying нужно скопировать
    $("#copy-to-selling-link").on("click", function(e) {
        e.preventDefault();
        var data = expensesSubtable.getData();
        incomeSubtable.empty();
        incomeSubtable.addRows(data);
    });

    $("#apply-addon").on("click", function(e) {
        e.preventDefault();

        //Для начала проверим, что Selling части есть строка Airfreight
        var hasAirfreight = _.some(incomeSubtable.getFieldValues("Name"), function(name) {
            return name.toLowerCase().indexOf("airfreight") !== -1;
        });

        if (!hasAirfreight) {
            alert('"Airfreight" for not found in Selling table');
            return;
        }

        //При клике на ссылку "Apply Addon" нужно отобразить дилог наложения Addon
        var url = "?a=Quotation&ps=ApplyAddonDialog";
        $.ajax(url, {
            data: {
                CustomerID: Light.module.getFieldValue("CustomerID"),
                CurrencyISO: Light.module.getFieldValue("CurrencyISO"),
                ChargeableWeight: Light.module.getFieldValue("ChargeableWeight")
            }
        });
    });

    export function onApplyAddonDialogSubmit(dialog) {
        var addonAmount = Light.parseFloat(dialog.$divContent.find(".addon-amount").val());
        //Пройдём по подтаблице Selling, найдём строку Airfreight и к ней применим Addon
        incomeSubtable.eachRow(function(rowNum) {
            var name = incomeSubtable.getFieldValue(rowNum, "Name");
            if (name.toLowerCase().indexOf("airfreight") !== -1) {
                var oldPrice = incomeSubtable.getFieldValue(rowNum, "Price");
                var newPrice = oldPrice + addonAmount;
                incomeSubtable.setFieldValue(rowNum, "Price", newPrice);
                //Возвращаем false, чтобы прекратить перебор коллекции
                return false;
            }
        });

        dialog.close();
    }
}
