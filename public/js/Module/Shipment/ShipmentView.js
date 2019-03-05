var Shipment;
(function (Shipment) {
    //Повесим обрабочик на кнопку Email Notice, чтобы обновить флаг
    $(".emailnotice-link").on("click", function () {
        Light.module.ajaxChangeField(Light.module.getObjectId(), "EmailNoticeSent", "1").done(function () {
            $("#emailnoticesent-td").html('<span class="good">Yes</span>');
        });
    });
})(Shipment || (Shipment = {}));
//# sourceMappingURL=ShipmentView.js.map