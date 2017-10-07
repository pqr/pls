/**
 * Вспомагательный JS файл, который подключается в PHP классе Forward\Classes\CompanyChildFromCustomer
 */
(function() {

    //Ассоциативный массив CustomerID => CompanyID
    var customersCompanyId = Light.getPageParam('CustomersCompanyID');
    //При изменении клиента попробуем изменить и компанию, если есть соответсвие в справочнике
    $("#CustomerID").on("change keyup", function() {
        var newCustomerId = this.value;
        if (typeof customersCompanyId[newCustomerId] !== "undefined") {
            $("#CompanyID").val(customersCompanyId[newCustomerId]);
        }
    });

}());