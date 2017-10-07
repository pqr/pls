(function () {
    "use strict";

    var hideEmptyBlock = function () {
        $("div.reportmenu > div > div").each(function () {
            var $div = $(this);
            var $parent = $div.parent();
            if ($div.text().trim() === "" || $div.children("div:not(.display-none)").length === 0) {
                $parent.addClass("display-none");
            } else {
                $parent.removeClass("display-none");
            }
        });
    };

    hideEmptyBlock();

    var $blocks = $(".reportblock");

    $("#reportmenu-filter-input")
        .focus()
        .on("keyup", function () {
            var filterValue = this.value.toLowerCase();
            $blocks.each(function () {
                var $block = $(this);
                $block.toggleClass('display-none', $block.text().toLowerCase().indexOf(filterValue) === -1);
            });
            hideEmptyBlock();
        });

})();
