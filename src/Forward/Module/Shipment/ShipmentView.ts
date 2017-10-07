namespace Shipment {
    //Повесим обрабочик на кнопку Email Notice, чтобы обновить флаг
    $("#emailnotice-link").on("click", function() {
        Light.module.ajaxChangeField(Light.module.getObjectId(), "EmailNoticeSent", "1").done(function(newValue) {
            $("#emailnoticesent-td").html('<span class="good">Yes</span>');
        });
    });
}
