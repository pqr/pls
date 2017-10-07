/**
 * Создадим объект Payment, внутри которого инкапсулируем общую логику для CPayment и VPayment
 */

namespace Payment {
    "use strict";
    import RowNum = Light.subtables.RowNum;

    //По имени текущего модуля определим правильное имя подтаблицы
    const firstLetter = Light.getModule().substring(0, 1);
    const subtableObj = Light.subtables.get(firstLetter + "Invoices");
    let paymentCurrencyIso = Light.module.getFieldValue("CurrencyISO");

    /**
     * Пересчитывает поле ClearingAmountInPaymentCurrency = clearingAmountInInvoiceCurrency * RateToInvoiceCurrency
     */
    function recalcClearingAmountInPaymentCurrency(rowNum: RowNum) {
        "use strict";
        const clearingAmountInInvoiceCurrency = <number>subtableObj.getFieldValue(
            rowNum,
            "ClearingAmountInInvoiceCurrency"
        );
        const rateToInvoiceCurrency = <number>subtableObj.getFieldValue(rowNum, "RateToInvoiceCurrency");
        const clearingAmountInPaymentCurrency = clearingAmountInInvoiceCurrency * rateToInvoiceCurrency;

        //Подсчёт total по столбцу ClearingAmountInPaymentCurrency запустится автоматически внутри setFieldValue
        subtableObj.setFieldValue(rowNum, "ClearingAmountInPaymentCurrency", clearingAmountInPaymentCurrency);
    }

    /**
     * Эта функция проверяет, совпадает ли валюта счёта с валютой всей оплаты.
     * Если да, то поле для ввода курса блокируется со значением 1
     * Делаем этот метод публичным в объекте Payment, чтобы его можно было вызвать из CPayment.js и VPayment.js
     */
    export function checkInvoiceCurrency(rowNum: RowNum) {
        const invoiceCurrency = subtableObj
            .getRowNode(rowNum)
            .find(".invoice-currency-span")
            .html();
        // Используем нативные DOM элементы, т.к. при
        const rateInput = <HTMLInputElement>subtableObj.getFieldNode(rowNum, "RateToInvoiceCurrency").get(0);
        const clearingAmountInPaymentCurrencyInput = <HTMLInputElement>subtableObj
            .getFieldNode(rowNum, "ClearingAmountInPaymentCurrency")
            .get(0);

        if (invoiceCurrency === paymentCurrencyIso) {
            rateInput.value = "1";
            rateInput.disabled = true;
            clearingAmountInPaymentCurrencyInput.disabled = true;
        } else {
            rateInput.disabled = false;
            clearingAmountInPaymentCurrencyInput.disabled = false;
        }
    }

    function onCurrencyChange() {
        // Если валюта счёта совпадает с валютой инвойса - нужно заблокировать поле для ввода курса
        paymentCurrencyIso = Light.module.getFieldValue("CurrencyISO");
        subtableObj.eachRow(function(rowNum) {
            Payment.checkInvoiceCurrency(rowNum);
        });

        //Обновим валюту во всех строках рядом с полем ClearingAmountInPaymentCurrency
        $(".payment-currency-span").html(paymentCurrencyIso);
    }

    //При изменении валюты в шапке оплаты нужно обновить лейблы и, возможно, заблокировать поля ввода курса
    Light.module.onFieldChange("CurrencyISO", onCurrencyChange);

    //В начальный момент времени заблокируем поля с курсами для тех счетов, валюта которых совпадает с валютой оплаты
    onCurrencyChange();

    //Обработчики изменения полей в подтаблицы, чтобы пересчитывалась суммуа помноженная на курс
    subtableObj.onFieldChange("ClearingAmountInInvoiceCurrency", recalcClearingAmountInPaymentCurrency);
    subtableObj.onFieldChange("RateToInvoiceCurrency", recalcClearingAmountInPaymentCurrency);

    //При добавлении строки нужно в столбце ClearingAmountInPaymentCurrency записать текущую валюту
    subtableObj.onAdd(function(_: any, $newRow) {
        $newRow.find(".payment-currency-span").html(paymentCurrencyIso);
    });

    function updateRowInfo(
        rowNum: RowNum,
        invoiceId: number,
        invoiceName: string,
        invoiceAmount: number,
        invoiceOutstandingAmount: number,
        invoiceCurrencyIso: string,
        invoiceStatusHtml: string
    ) {
        subtableObj.setFieldValue(rowNum, firstLetter + "InvoiceID", invoiceId);
        subtableObj.setFieldValue(rowNum, firstLetter + "InvoiceName", invoiceName);
        subtableObj.getTdNode(rowNum, "StatusIco").html(invoiceStatusHtml);
        subtableObj
            .getTdNode(rowNum, "InvoiceAmount")
            .html(Light.getBeautyMoney(invoiceAmount, false, invoiceCurrencyIso));
        subtableObj
            .getTdNode(rowNum, "InvoiceOutstandingAmount")
            .html(Light.getBeautyMoney(invoiceOutstandingAmount, false, invoiceCurrencyIso));
        subtableObj
            .getRowNode(rowNum)
            .find(".invoice-currency-span")
            .html(invoiceCurrencyIso);

        //На основе валюты счёта заблокируем курс валюты или разблокируем
        Payment.checkInvoiceCurrency(rowNum);

        //Узнаем курс валюты из соответсвующего поля
        let exchRate = <number>subtableObj.getFieldValue(rowNum, "RateToInvoiceCurrency");
        if (exchRate == 0) {
            exchRate = 1;
        }

        subtableObj.setFieldValue(rowNum, "ClearingAmountInInvoiceCurrency", invoiceOutstandingAmount);
        subtableObj.setFieldValue(rowNum, "ClearingAmountInPaymentCurrency", invoiceOutstandingAmount * exchRate);
    }

    // Экспортируем публичные методы объекта
    export function onInvoiceSelect(this: HTMLInputElement, item: any) {
        if (typeof item.InvoiceAmount !== "undefined") {
            const rowNum = Light.subtables.getRowNum($(this));
            updateRowInfo(
                rowNum,
                item.ID,
                item.Name,
                item.InvoiceAmount,
                item.InvoiceOutstandingAmount,
                item.InvoiceCurrencyISO,
                item.StatusHtml
            );
        }
    }

    export function acInvoiceResultFormatter(this: JQuery, item: any) {
        if (typeof item.InvoiceAmount !== "undefined") {
            //Если в массиве результатов есть поле Amount - значит что-то найдено, отформатируем строку
            return (
                '<span class="ac-invoice">' +
                item.StatusHtml +
                '<span class="ac-invoice-id">' +
                Light.htmlspecialchars(item.ID) +
                '</span><span class="ac-invoice-name">' +
                Light.htmlspecialchars(item.Name) +
                '</span><span class="ac-invoice-amount">' +
                Light.getBeautyMoney(item.InvoiceAmount) +
                '</span><span class="ac-invoice-outstandingamount">' +
                Light.getBeautyMoney(item.InvoiceOutstandingAmount) +
                '</span><span class="ac-invoice-currencyiso">' +
                item.InvoiceCurrencyISO +
                "</span></span>"
            );
        } else {
            const rowNum = Light.subtables.getRowNum(this);
            //Если поля Amount в результатах ajax запроса нет, значит нужно вывести сообщение "не найдено"
            //И одновременно с этим сразу очистить текущую строку в подтаблице
            const invId = typeof item.ID === "undefined" ? "" : item.ID;
            const invName = typeof item.Name === "undefined" ? "" : item.Name;

            updateRowInfo(rowNum, invId, invName, 0, 0, "", "");
            return '<span class="bad">Похожих инвойсов не найдено</span>';
        }
    }

    /**
     * Функция формирует url для ajax запроса к серверу при вводе номера счёта
     * Необходимо переопределить её вручную, чтобы передать на сервер текущий выбор VendorID и VendorAccountID
     */
    export function acInvoiceByIdSourceFunction() {
        if (firstLetter === "C") {
            return (
                "?a=CPayment&CustomerID=" +
                $("#CustomerID").val() +
                "&CustomerAccountID=" +
                $("#CustomerAccountID").val() +
                "&ac=SearchInvoiceById"
            );
        } else {
            return (
                "?a=VPayment&VendorID=" +
                $("#VendorID").val() +
                "&VendorAccountID=" +
                $("#VendorAccountID").val() +
                "&ac=SearchInvoiceById"
            );
        }
    }

    export function acInvoiceByNameSourceFunction() {
        if (firstLetter === "C") {
            return (
                "?a=CPayment&CustomerID=" +
                $("#CustomerID").val() +
                "&CustomerAccountID=" +
                $("#CustomerAccountID").val() +
                "&ac=SearchInvoiceByName"
            );
        } else {
            return (
                "?a=VPayment&VendorID=" +
                $("#VendorID").val() +
                "&VendorAccountID=" +
                $("#VendorAccountID").val() +
                "&ac=SearchInvoiceByName"
            );
        }
    }
}
