namespace AirfreightRate {
    $(".upload-excel").on("click", function(e) {
        e.preventDefault();
        var bodyHtml =
            '<form action="?a=AirfreightRate&p=RatesUploadExcel" id="RatesUploadExcel" enctype="multipart/form-data" method="post">' +
            '<input type="file" name="ratesfile" id="ratesfile">' +
            "</form>";

        var options = {
            buttonOkText: "Upload",
            buttonOkHandler: function(dialog) {
                var $form = $("#RatesUploadExcel");
                if ($("#ratesfile", $form).val() != "") {
                    $form.off();
                    $form.submit();
                }

                dialog.unblockButtons();
            }
        };

        new Light.Dialog(bodyHtml, options).open();
    });
}
