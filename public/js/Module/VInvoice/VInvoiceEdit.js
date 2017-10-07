var VInvoice;
(function (VInvoice) {
    var linesSubtable = Light.subtables.get("Lines");
    function onQuantityOrPriceOrVatPercentChange(rowNum) {
        var quantity = linesSubtable.getFieldValue(rowNum, "Quantity");
        var price = linesSubtable.getFieldValue(rowNum, "Price");
        var amount = quantity * price;
        var vatPercent = Light.parseFloat(linesSubtable.getFieldValue(rowNum, "VATPercent"));
        var vatAmount, finalAmount;
        if (vatPercent === -18) {
            //Особый случай: цена уже включае в себя НДС, поэтому сумму НДС нужно выделить из имеющейся суммы
            vatAmount = amount / 118 * 18;
            finalAmount = amount;
        }
        else {
            //Обычный случай: НДС сверху цены
            if (vatPercent < 0) {
                vatAmount = 0;
                finalAmount = amount;
            }
            else {
                vatAmount = amount * vatPercent / 100;
                finalAmount = amount + vatAmount;
            }
        }
        linesSubtable.setFieldValue(rowNum, "Amount", amount, false);
        linesSubtable.setFieldValue(rowNum, "VATAmount", vatAmount, false);
        linesSubtable.setFieldValue(rowNum, "FinalAmount", finalAmount, false);
    }
    linesSubtable.onFieldChange("Quantity", onQuantityOrPriceOrVatPercentChange);
    linesSubtable.onFieldChange("Price", onQuantityOrPriceOrVatPercentChange);
    linesSubtable.onFieldChange("VATPercent", onQuantityOrPriceOrVatPercentChange);
    linesSubtable.onAdd(onQuantityOrPriceOrVatPercentChange);
    linesSubtable.onFieldCalcTotal("FinalAmount", function () {
        var finalTotalAmount = linesSubtable.getTotalValue("FinalAmount");
        $("#amount-span").html(Light.getBeautyMoney(finalTotalAmount));
    });
    linesSubtable.onFieldChange("Amount", function (rowNum) {
        var quantity = linesSubtable.getFieldValue(rowNum, "Quantity");
        var amount = linesSubtable.getFieldValue(rowNum, "Amount");
        var price = quantity ? amount / quantity : amount;
        var vatPercent = Light.parseFloat(linesSubtable.getFieldValue(rowNum, "VATPercent"));
        var vatAmount, finalAmount;
        if (vatPercent === -18) {
            //Особый случай: цена уже включае в себя НДС, поэтому сумму НДС нужно выделить из имеющейся суммы
            vatAmount = amount / 118 * 18;
            finalAmount = amount;
        }
        else {
            //Обычный случай: НДС сверху цены
            if (vatPercent < 0) {
                vatAmount = 0;
                finalAmount = amount;
            }
            else {
                vatAmount = amount * vatPercent / 100;
                finalAmount = amount + vatAmount;
            }
        }
        linesSubtable.setFieldValue(rowNum, "Price", price, false);
        linesSubtable.setFieldValue(rowNum, "VATAmount", vatAmount, false);
        linesSubtable.setFieldValue(rowNum, "FinalAmount", finalAmount, false);
    });
    linesSubtable.onFieldChange("VATAmount", function (rowNum) {
        var amount = linesSubtable.getFieldValue(rowNum, "Amount");
        var vatAmount = linesSubtable.getFieldValue(rowNum, "VATAmount");
        var finalAmount = amount + vatAmount;
        linesSubtable.setFieldValue(rowNum, "FinalAmount", finalAmount, false);
    });
    linesSubtable.onFieldChange("FinalAmount", function (rowNum) {
        var finalAmount = linesSubtable.getFieldValue(rowNum, "FinalAmount");
        var vatPercent = Light.parseFloat(linesSubtable.getFieldValue(rowNum, "VATPercent"));
        var vatAmount, amount;
        //Обычный случай: НДС сверху цены
        if (vatPercent < 0) {
            vatAmount = 0;
        }
        else {
            vatAmount = finalAmount / (100 + vatPercent) * vatPercent;
        }
        amount = finalAmount - vatAmount;
        var quantity = linesSubtable.getFieldValue(rowNum, "Quantity");
        var price = amount / quantity;
        linesSubtable.setFieldValue(rowNum, "Price", price, false);
        linesSubtable.setFieldValue(rowNum, "Amount", amount, false);
        linesSubtable.setFieldValue(rowNum, "VATAmount", vatAmount, false);
    });
})(VInvoice || (VInvoice = {}));
//# sourceMappingURL=VInvoiceEdit.js.map