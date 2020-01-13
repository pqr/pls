(function () {
    "use strict";

    function hideEmptyBlock() {
        $("div.reportmenu > div > div").each(function () {
            var $div = $(this);
            var $parent = $div.parent();
            if ($div.text().trim() === "" || $div.children("div:not(.display-none)").length === 0) {
                $parent.addClass("display-none");
            } else {
                $parent.removeClass("display-none");
            }
        });
    }

    hideEmptyBlock();

    var $blocks = $(".reportblock");

    function showAll() {
        $blocks.removeClass('display-none');
        hideEmptyBlock();
    }

    $("#reportmenu-filter-input")
        .focus()
        .on("keyup", function () {
            var filterValue = this.value.trim();
            if (filterValue === '') {
                showAll();
            } else {
                $blocks.each(function () {
                    var $block = $(this);
                    var hide = !Light.matchWithOpposite($block.text(), filterValue);
                    $block.toggleClass("display-none", hide);
                });
                hideEmptyBlock();
            }
        });
})();
