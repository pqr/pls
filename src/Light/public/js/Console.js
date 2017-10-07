$(function () {

    var blockSubmitButton = function($form, block) {
        $form.find("input[type=submit]").prop("disabled", block);
    };

    $("#tabs-uploadhg").find("form").on("submit", function(e) {
        if ($(this).find("input[type=file]").val() == "") {
            e.stopImmediatePropagation();
            e.preventDefault();
            Light.toastError("Select file");
        }
    });

    //Глобальный блокиратор кнопок при сабмите любой формы
    $("form").on("submit", function () {
        blockSubmitButton($(this), true);
    });

    $("textarea").keydown(function (e) {
        if ((e.keyCode == 10 || e.keyCode == 13) && e.ctrlKey) {
            var $form = $(this).closest("form");
            if ($form.length > 0) {
                $form.submit();
            }
        }
    });

    var submitTextForm = function () {
        var $form = $(this);
        var url = $form.attr('action');
        var value = $form.find("textarea").val();
        $.post(url, {value: value}).done(function (data) {
            $form.find(".result").html(data);
        }).always(function () {
                blockSubmitButton($form, false);
            });
    };

    $("#sql-form").on("submit", submitTextForm);
    $("#shell-form").on("submit", submitTextForm);

    $('ul.tabs').on('tabs:activate', function(_, activeHash) {
        if (activeHash === '#tab-phpinfo') {
            $('#tab-phpinfo').load('?a=Console&ps=PhpInfo')
        }
    })
});