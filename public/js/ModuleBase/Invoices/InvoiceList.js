var Invoice;
(function (Invoice) {
    var module = Light.getModule();
    var combinations;
    if (module === "CInvoice") {
        combinations = {
            Open: ["invopen", "invpartcleared"]
        };
    }
    else {
        combinations = {
            Open: ["invopen", "invunderdispute", "invaccepted", "invpartcleared"],
            NotDeleted: ["invopen", "invunderdispute", "invaccepted", "invpartcleared", "invcleared", "invcreditnoted"]
        };
    }
    var $statusCheckboxesTable = $("#statuses-checkboxes-table");
    var $statusCheckboxes = $statusCheckboxesTable.find("input");
    var table = Light.tables.get(module);
    function updateFiltersSatatusesBlock() {
        // Скроем или покажем блко с чекбоксами-фильтрами статусов
        var statusFilter = table.getFilterValue("Status");
        if (statusFilter === "Manual" || statusFilter === "Open" || statusFilter === "NotDeleted") {
            $statusCheckboxesTable.removeClass("display-none");
            // Отдельно проверим Open и NotDeleted - нужно расставить чекбоксы
            if (statusFilter === "Open" || statusFilter === "NotDeleted") {
                // Сначала пройдём по всем галочкам и снимем фильтры
                $statusCheckboxes.each(function () {
                    table.setFilterValue(this.id, 0);
                });
                // Теперь проставим только нужные
                combinations[statusFilter].forEach(function (cssClass) {
                    var $cb = $statusCheckboxesTable.find("div." + cssClass).find("input");
                    table.setFilterValue($cb.prop("id"), 1);
                });
            }
        }
        else {
            $statusCheckboxesTable.addClass("display-none");
        }
    }
    table.onFilterChange("Status", updateFiltersSatatusesBlock);
    table.onFiltersReset(updateFiltersSatatusesBlock);
    updateFiltersSatatusesBlock();
})(Invoice || (Invoice = {}));
//# sourceMappingURL=InvoiceList.js.map