(function() {
    "use strict";

    var hideEmptyBlock = function() {
        $("div.reportmenu > div > div").each(function() {
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
        .on("keyup", function() {
            var filterLower = this.value.toLowerCase();
            var filterLowerOpposite = Light.getOppositeString(filterLower);
            $blocks.each(function() {
                var $block = $(this);
                var blockTextLower = $block.text().toLowerCase();
                var hide =
                    blockTextLower.indexOf(filterLower) === -1 && blockTextLower.indexOf(filterLowerOpposite) === -1;
                $block.toggleClass("display-none", hide);
            });
            hideEmptyBlock();
        });
})();
