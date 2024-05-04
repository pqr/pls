namespace CostSheet {
    declare var Light:any;

    //Заранее узнаем id первозки и сохраним в переменную - пригодится много где
    var shipmentId = Light.getPageParam("shipmentId");
    var dataForSearchAirfreigthRate = Light.getPageParam("DataForSearchAirfreigthRate");

    var gp = Light.getPageParam("gp");
    var egp = Light.getPageParam("egp");
    var deviation = Light.getPageParam("deviation");

    //Разбивка сумм по реально существующим счетам (Customer Invoices и Vendor Invoices)
    //Нужна для сравнения сумм со строками кост-шита во время подсветки
    var invoicesAmounts = Light.getPageParam("invoicesAmounts");

    //Также в параметрах страницы хранится номер котировки из этого шипмента. Понадобится для заполнения диалогового окна
    //Fill from Quotation
    var defaultQuotationNum = Light.getPageParam("QuotationID");
    if (defaultQuotationNum == "0") {
        defaultQuotationNum = "";
    }

    //Вспомагательный объект, нужен для режима редактирования
    var editTotals = {
        Income: 0,
        Expenses: 0
    };

    //Вспомагательный обхъект, который будет хранить копию HTML-view при переходе в режим Edit - эта копия понадобится
    //при нажатии на cancel в режиме редактирования
    var incomeDocumentFragment = null;
    var expensesDocumentFragment = null;

    var getIncomeExpensesTypeFromSubtableName = function(subtableName) {
        //Пример: IncomeOffshoreEdit
        return subtableName.indexOf("Income") === 0 ? "Income" : "Expenses";
    };

    //Повесим обработчики на все основные кнопки
    $(".cs-edit-btn").on("click", function(e) {
        e.preventDefault();

        var $incomeTd = $(".cs-income-td");
        var $expensesTd = $(".cs-expenses-td");

        //Скопируем DOM режима View в специальный буфер - на случай если пользователь потом нажмёт cancel
        incomeDocumentFragment = document.createDocumentFragment();
        $incomeTd.children().each(function() {
            "use strict";
            incomeDocumentFragment.appendChild(this);
        });

        expensesDocumentFragment = document.createDocumentFragment();
        $expensesTd.children().each(function() {
            "use strict";
            expensesDocumentFragment.appendChild(this);
        });

        //Сразу после нажатия кнопки поменяем менюшку на которой находится кнопка edit на менюшку для режима редактирования
        $(".cs-save-div").removeClass("display-none");
        $(".cs-edit-btn").addClass("display-none");
        $(".cs-cinvoice-btn").addClass("display-none");
        $(".cs-vinvoice-btn").addClass("display-none");

        //Отобразим ссылки Copy и Fill from Quotation
        $(".cs-copy-btn").removeClass("display-none");
        $(".cs-fillquotation-btn").removeClass("display-none");

        //Отобразим ссылки Search Airfreight Rate и Copy to Selling
        $("#copy-to-selling-link").removeClass("display-none");

        var url = "index.php?a=Shipment&id=" + shipmentId + "&func=cs&editcs=1";
        $.ajax(url, {
            dataType: "json",
            success: function(data) {
                $incomeTd.html(data.IncomeHtml);
                Light.addStandardListeners($incomeTd);
                Light.tables.init($incomeTd);
                Light.subtables.init($incomeTd);
                var incomeSubtable = Light.subtables.get("IncomeEdit");
                incomeSubtable.onFieldChange("Quantity", onQuantityChange);
                incomeSubtable.onFieldChange("Price", onPriceChange);
                incomeSubtable.onFieldChange("Amount", onAmountWithCurrencyChange);
                //Добавим обработчики на удаление и добавление новых строк - полный пересчёт total, EGP, deviation
                incomeSubtable.onCalcTotal(subtableCaclTotalHandler);

                $expensesTd.html(data.ExpensesHtml);
                Light.addStandardListeners($expensesTd);
                Light.tables.init($expensesTd);
                Light.subtables.init($expensesTd);
                var expensesSubtable = Light.subtables.get("ExpensesEdit");
                expensesSubtable.onFieldChange("Quantity", onQuantityChange);
                expensesSubtable.onFieldChange("Price", onPriceChange);
                expensesSubtable.onFieldChange("Amount", onAmountWithCurrencyChange);
                expensesSubtable.onCalcTotal(subtableCaclTotalHandler);

                recalculateTotal(incomeSubtable);
                recalculateTotal(expensesSubtable);
                recalculateEgp();
            }
        });
    });

    $(".cs-cancel-link").on("click", function(e) {
        e.preventDefault();

        //Восстановим HTML код режима View в соотвествующих ячейках
        var $incomeTd = $(".cs-income-td");
        $incomeTd.empty();
        $incomeTd.append(incomeDocumentFragment);

        var $expensesTd = $(".cs-expenses-td");
        $expensesTd.empty();
        $expensesTd.append(expensesDocumentFragment);

        //Запускаем метод, который отрендрит кнопки для режима View
        showView(egp, deviation);
    });

    var showView = function(egp, deviation) {
        //Сразу после нажатия кнопки поменяем менюшку на которой находится кнопка edit на менюшку для режима редактирования
        $(".cs-save-div").addClass("display-none");
        $(".cs-edit-btn").removeClass("display-none");
        $(".cs-cinvoice-btn").removeClass("display-none");
        $(".cs-vinvoice-btn").removeClass("display-none");

        //Скроем ссылки Copy и Quotation / Standart Rate
        $(".cs-copy-btn").addClass("display-none");
        $(".cs-fillquotation-btn").addClass("display-none");

        //Скроем ссылки Search Airfreight Rate и Copy to Selling
        $("#copy-to-selling-link").addClass("display-none");

        //Обновим блоки с EGP и Deviation
        renderEgp(egp);
        renderDeviation(deviation);
    };

    $(".cs-cinvoice-btn").on("click", function(e) {
        e.preventDefault();

        var table = Light.tables.get("Income");
        var checkedValues = table.getSelectedValues().join(",");
        //перенаправляем пользователя на страницу создания Customer Invoice
        document.location.href =
            "index.php?a=CInvoice&addnew=1&fromCostSheet=" + shipmentId + "&csLineIds=" + checkedValues;
    });

    $(".cs-vinvoice-btn").on("click", function(e) {
        e.preventDefault();

        var table = Light.tables.get("Expenses");
        var checkedValues = table.getSelectedValues().join(",");
        //перенаправляем пользователя на страницу создания Customer Invoice
        document.location.href =
            "index.php?a=VInvoice&addnew=1&fromCostSheet=" + shipmentId + "&csLineIds=" + checkedValues;
    });

    $(".cs-copy-btn").on("click", function(e) {
        e.preventDefault();

        var bodyHtml =
            '<form><label for="cs-copy-number">Copy from Shipment (enter number):</label> <input type="text" id="cs-copy-number" class="input-ceil input-small input-left"></form>';
        var options = {
            buttonCancelText: "Cancel",
            buttonOkText: "Copy Cost-sheet",
            buttonOkHandler: function(dialog) {
                var $input = $("#cs-copy-number");
                var shipmentNum = parseInt($input.val(), 10);
                if (isNaN(shipmentNum)) {
                    $input.addClass("input-error");
                    alert("Введите номер перевозки из которой скопировать Cost-sheet");
                    $input.focus();
                    dialog.unblockButtons();
                    return;
                }

                $input.removeClass("input-error");
                //Загрузим данные из другого Cost-sheet через ajax
                var url = "index.php?a=Shipment&ps=CopyFromCs&FileNumber=" + shipmentNum;
                $.ajax(url, {
                    dataType: "json",
                    success: function(data) {
                        if (typeof data.income == "undefined") {
                            $input.addClass("input-error");
                            alert("Shipment not found");
                            $input.focus();
                            dialog.unblockButtons();
                            return;
                        }

                        var subtableIncome = Light.subtables.get("IncomeEdit");
                        subtableIncome.addRows(data.income);

                        var subtableExpenses = Light.subtables.get("ExpensesEdit");
                        subtableExpenses.addRows(data.expenses);

                        dialog.close();
                    }
                });
            }
        };

        new Light.Dialog(bodyHtml, options).open();
    });

    $(".btn-save").on("click", function(e) {
        e.preventDefault();

        var saveBtnEl = this;
        Light.blockButtons(saveBtnEl, true);

        var url = "index.php?a=Shipment&id=" + shipmentId + "&func=cs&submitcs=1";
        var postData = $("form").serialize();
        $.post(
            url,
            postData,
            function(data, textStatus, jqXHR) {
                var $incomeTd = $(".cs-income-td");
                $incomeTd.html(data.IncomeHtml);
                Light.addStandardListeners($incomeTd);
                Light.tables.init($incomeTd);

                var $expensesTd = $(".cs-expenses-td");
                $expensesTd.html(data.ExpensesHtml);
                Light.addStandardListeners($expensesTd);
                Light.tables.init($expensesTd);

                //Запускаем метод, который отрендрит кнопки для режима View
                showView(data.EGP, data.Deviation);

                //Запишем новую History таблицу
                $(".history-div").html(data.HistoryHtml);

                //В шапке над Cost-sheet также обновим номер котировки
                $("#QuotationID-td").html(data.QuotationIDHtml);

                //Сохраним некоторые данные в глобальных переменных
                egp = data.EGP;
                deviation = data.Deviation;
            },
            "json"
        ).always(function() {
            Light.blockButtons(saveBtnEl, false);
        });
    });

    /**
     * Преесчитывате total в подтаблице и записывает в total ячейку
     * @param {Subtable} subtable
     */
    var recalculateTotal = function(subtable) {
        //Проходим в цикле по всем строкам, считаем Total с учётом курсов валют
        //Все строки переводим в USD для подсчёта total
        //Одновременно с этим сохраним разбивку по валютам в объекте totalPerCurrency
        var total = 0;
        var totalPerCurrency = {};
        var currencyCount = 0;
        subtable.eachRow(function(rowNum) {
            var parts = subtable.getFieldValue(rowNum, "Amount").split(" ");
            var currencyIso = parts[0];
            var amount = Light.parseFloat(parts[1]);

            total += Light.currency.convertValue(amount, currencyIso);

            if (typeof totalPerCurrency[currencyIso] === "undefined") {
                totalPerCurrency[currencyIso] = 0;
                currencyCount++;
            }

            totalPerCurrency[currencyIso] += amount;
        });

        total = Light.round(total);

        //Теперь отрендрим total с разбивкой по валютам я одной ячейке в строке total
        var totalHtml = Light.getBeautyMoney(total, false, "USD");

        //Если разбивка по валютам дала какие-то валюты отличные от USD, добавим к html результатам эту разбивку
        if (currencyCount > 1 || typeof totalPerCurrency["USD"] === "undefined") {
            totalHtml += "<div>";
            var amoutPerCurrencyHtml = [];
            for (var currencyIso in totalPerCurrency) {
                amoutPerCurrencyHtml.push(Light.getBeautyMoney(totalPerCurrency[currencyIso], false, currencyIso));
            }

            totalHtml += amoutPerCurrencyHtml.join("<br>");
            totalHtml += "</div>";
        }

        //Запишем полученный HTML в ячейку Amount
        subtable.getTotalTdNode("Amount").html(totalHtml);

        //Сохраним значение total в специальном объекте на будущее
        var subtableName = subtable.getName();
        var incomeExpenses = getIncomeExpensesTypeFromSubtableName(subtableName);
        editTotals[incomeExpenses] = total;
    };

    var recalculateEgp = function() {
        var egp = Light.round(editTotals.Income - editTotals.Expenses);
        renderEgp(egp);

        var deviation = gp - egp;
        var deviationRub;

        //Запишем значение Deviation в соответствующий элемент
        renderDeviation(deviation);
    };

    var renderEgp = function(egp) {
        var $egpDiv = $(".cs-egp-div");
        if (egp <= 0) {
            $egpDiv.removeClass("good").addClass("bad");
        } else {
            $egpDiv.removeClass("bad").addClass("good");
        }

        $egpDiv.find(".cs-egp-value").html(Light.getBeautyMoney(egp));
    };

    var renderDeviation = function(deviation) {
        var $deviationDiv = $(".cs-deviation-div");
        $deviationDiv.removeClass("bad").removeClass("good");
        deviation = deviation | 0;
        if (deviation == 0) {
            $deviationDiv.addClass("good");
        } else if (deviation < 0) {
            $deviationDiv.addClass("bad");
        }

        $deviationDiv.find(".cs-deviation-value").html(Light.getBeautyMoney(deviation));
    };

    var onQuantityChange = function(rowNum, newValue) {
        //this в этом обработчике события ссылается на саму подтаблицу!
        var subtable = this;
        var amount = newValue * subtable.getFieldValue(rowNum, "Price");
        subtable.setFieldValue(rowNum, "Amount", amount);
        recalculateTotal(subtable);
        recalculateEgp();
    };

    var onPriceChange = function(rowNum, newValue) {
        //this в этом обработчике события ссылается на саму подтаблицу!
        var subtable = this;
        var amount = newValue * subtable.getFieldValue(rowNum, "Quantity");
        subtable.setFieldValue(rowNum, "Amount", amount);
        recalculateTotal(subtable);
        recalculateEgp();
    };

    var onAmountWithCurrencyChange = function(rowNum, newValue, $node) {
        //this в этом обработчике события ссылается на саму подтаблицу!
        var subtable = this;
        recalculateTotal(subtable);
        recalculateEgp();
    };

    var subtableCaclTotalHandler = function(rowNum) {
        //this ссылается на текущую подтабилцу!
        var subtable = this;
        recalculateTotal(subtable);
        recalculateEgp();
    };

    $(".cs-td")
        .on("mouseover", "tr", function(e) {
            "use strict";

            var invId = this.getAttribute("data-invid");
            if (!invId) {
                //У данной строки нет invId - скорее всего, это либо Estimated строки без InvNum
                //либо это блок с внутренними счетами
                return;
            }

            var $cstd = $(e.delegateTarget);
            var incomeExpenses = $cstd.hasClass("type-income") ? "income" : "expenses";
            var $allTbody = $(".type-" + incomeExpenses).find("tbody");

            //Включаем подсветку
            $allTbody.addClass("hover");
            //Найдём все строки с указанным номером счёта и в самом Cost-sheet и в таблице со счетами и подсветим их!
            $allTbody.find("tr[data-invid=" + invId + "]").addClass("hover");

            //Посчиатем сумму выделенный в верхней (Estimated) части и сравним с суммой по счёту внизу
            var $estimatedBlock = $(".cs-" + incomeExpenses + "-td");
            var $totalSelectedDiv = $estimatedBlock.find(".total-selected");
            //Блок $totalSelectedDiv может отсутсвовать в режиме редактирования
            if ($totalSelectedDiv.length) {
                //Найдём все ранее подсвеченные строки в верхнем (Estimated) блоке и посчитаем сумму по ним
                var hoverRows = $estimatedBlock
                    .find("tbody")
                    .find(".hover")
                    .get();
                var totalSelectedAmount = hoverRows.reduce(function(sum, rowEl) {
                    return sum + Light.round(rowEl.getAttribute("data-amount"));
                }, 0);

                //Проверим, все ли подсвеченные строки кост-шита имеют одинаковую валюту причём равную валюте
                //подсвеченного счёта?
                var invoiceCurrency = invoicesAmounts[incomeExpenses][invId]["CurrencyISO"];
                var isCurrencyEqual = hoverRows.every(function(rowEl) {
                    return rowEl.getAttribute("data-currency") == invoiceCurrency;
                });

                totalSelectedAmount = Light.round(totalSelectedAmount, 2);
                $totalSelectedDiv.find("span").html(Light.getBeautyMoney(totalSelectedAmount));
                $totalSelectedDiv.css("visibility", "visible");

                $totalSelectedDiv.removeClass("alert-success");
                $totalSelectedDiv.removeClass("alert-danger");
                $totalSelectedDiv.removeClass("alert-info");
                if (!isCurrencyEqual) {
                    //Если расходятся валюты, то сделаем голубую подсветку
                    $totalSelectedDiv.addClass("alert-info");
                } else {
                    //сравнить с суммой счёта в нижней таблице и сделать alert-success или alert-danger
                    var invoiceSelectedAmount = invoicesAmounts[incomeExpenses][invId]["Amount"];
                    if (invoiceSelectedAmount == totalSelectedAmount) {
                        $totalSelectedDiv.addClass("alert-success");
                    } else {
                        $totalSelectedDiv.addClass("alert-danger");
                    }
                }
            }
        })
        .on("mouseout", "tr", function(e) {
            "use strict";
            $("tbody.hover")
                .removeClass("hover")
                .find("tr.hover")
                .removeClass("hover");
            $(".total-selected").css("visibility", "hidden");
        });

    $(".cs-fillquotation-btn").on("click", function(e) {
        e.preventDefault();

        //Отобразим диалог для ввода номера котировки из которой заполнить
        var bodyHtml =
            '<form><label for="use PHPUnitFrameworkTestCase;fillFromQuotation-quotation">Quotation / Standart Rate ID</label> <input type="text" id="fillFromQuotation-quotation" class="input-ceil input-small input-left" value="' +
            defaultQuotationNum +
            '"></form>';
        var options = {
            buttonCancelText: "Cancel",
            buttonOkText: "Fill from Quotation / Standart Rate",
            buttonOkHandler: function(dialog) {
                var $input = $("#fillFromQuotation-quotation");
                var quotationNum = parseInt($input.val(), 10);
                if (isNaN(quotationNum)) {
                    $input.addClass("input-error");
                    alert("Input Quotation №");
                    $input.focus();
                    dialog.unblockButtons();
                    return;
                }

                $input.removeClass("input-error");
                //Загрузим данные из котировки
                var url =
                    "index.php?a=Shipment&id=" + shipmentId + "&func=FillFromQuotation&QuotationID=" + quotationNum;
                $.ajax(url, { dataType: "json" })
                    .done(function(data) {
                        var incomeSubtable = Light.subtables.get("IncomeEdit");
                        incomeSubtable.empty();
                        incomeSubtable.addRows(data.income);

                        var expensesSubtable = Light.subtables.get("ExpensesEdit");
                        expensesSubtable.empty();
                        expensesSubtable.addRows(data.expenses);
                        dialog.close();

                        //В специальное скрытое поле запишем номер выбранной котировки
                        var $newQuotationId = $("#NewQuotationID");
                        if ($newQuotationId.length > 0) {
                            $newQuotationId.val(quotationNum);
                        } else {
                            //Этого скрытого поля ещё нет в вёрстке - нужно добавить
                            $("form").append(
                                '<input type="hidden" id="NewQuotationID" name="NewQuotationID" value="' +
                                    quotationNum +
                                    '">'
                            );
                        }
                    })
                    .fail(function(jqXHR) {
                        $input.addClass("input-error");
                        alert(jqXHR.responseText);
                        $input.focus();
                        dialog.unblockButtons();
                    });
            }
        };

        new Light.Dialog(bodyHtml, options).open();
    });

    //При нажатии на ссылку "Copy to Selling", которая находится в блоке Buying нужно скопировать
    $("#copy-to-selling-link").on("click", function(e) {
        e.preventDefault();
        const expensesSubtable = Light.subtables.get("ExpensesEdit");
        const incomeSubtable = Light.subtables.get("IncomeEdit");
        const data = expensesSubtable.getData().map((rowData:any) => {
            rowData['InvNum'] = '';
            return rowData;
        });
        incomeSubtable.empty();
        incomeSubtable.addRows(data);
    });
}
