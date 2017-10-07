(function () {

    var $trs = $("table[data-tablename=Light_Module_Autolaunch_AutolaunchMenuTable] > tbody > tr");

    if ($trs.length > 0) {

        var $selectedTr = null;

        $("#autolaunch-filter-input")
            .focus()
            .on("keyup", function () {
                var filterValue = this.value;
                $trs.each(function () {
                    var $tr = $(this);
                    $tr.toggleClass('display-none', $tr.text().toLowerCase().indexOf(filterValue.toLowerCase()) === -1);
                });

                if ($selectedTr !== null && $selectedTr.hasClass("display-none")) {
                    $selectedTr.removeClass("selected");
                    $selectedTr = null;
                }
            });


        Light.listener.simple_combo("down", function (e) {
            if ($selectedTr === null) {
                $selectedTr = $trs.not('.display-none').first();
                if ($selectedTr.length > 0) {
                    $selectedTr.addClass('selected');
                }
            } else {
                var $tryNext = $selectedTr;
                while (1) {
                    $tryNext = $tryNext.next("tr");
                    if ($tryNext.length === 0) {
                        break;
                    }
                    if (!$tryNext.hasClass("display-none")) {
                        $selectedTr.removeClass('selected');
                        $tryNext.addClass('selected');
                        $selectedTr = $tryNext;
                        break;
                    }
                }
            }
        });

        Light.listener.simple_combo("up", function (e) {
            if ($selectedTr === null) {
                $selectedTr = $trs.not('.display-none').last();
                if ($selectedTr.length > 0) {
                    $selectedTr.addClass('selected');
                }
            } else {
                var $tryPrev = $selectedTr;
                while (1) {
                    $tryPrev = $tryPrev.prev("tr");
                    if ($tryPrev.length === 0) {
                        break;
                    }
                    if (!$tryPrev.hasClass("display-none")) {
                        $selectedTr.removeClass('selected');
                        $tryPrev.addClass('selected');
                        $selectedTr = $tryPrev;
                        break;
                    }
                }
            }
        });

        Light.listener.simple_combo("enter", function (e) {
            if ($selectedTr !== null) {
                $selectedTr.trigger("dblclick");
            }
        });
    }

    var $form = $("#autolaunch-form");
    if ($form.length > 0) {

        $form.on("submit", function () {
            //Заблокируем кнопку
            $(this).find(".bgcolor-btn-primary").prop("disabled", true).html("Running...");
            this.submit();
        });

        Light.listener.simple_combo("ctrl enter", function (e) {
            $form.trigger("submit");
        });
    }

})();
