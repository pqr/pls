/**
 * Вспомагательный JS файл, который подключается в PHP классе Forward\Classes\CompanyChildFromCustomer
 */
(function() {

    //Ассоциативный массив CustomerID => CompanyID
    var vendorsCompanyId = Light.getPageParam('VendorsCompanyID');
    //При изменении клиента попробуем изменить и компанию, если есть соответсвие в справочнике
    $("#VendorID").on("change keyup", function() {
        var newVendorId = this.value;
        var newCompanyId = vendorsCompanyId[newVendorId];
        if (newCompanyId) {
            var $companyId = $("#CompanyID");
            if ($companyId.find("option[value" + newCompanyId + "]").length > 0) {
                $companyId.val(newCompanyId);
            }
        }
    });

}());