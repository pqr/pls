import { addStandardListeners, getPageParam, myParseInt } from "./Light";

import "./AjaxFields";
import "./Colorpalette";
import "./Mainmenu";
import * as colopalette from "./Colorpalette";
import "./MonthWidget";
import * as currency from "./Currency";
import * as forms from "./Form";
import * as table from "./Table";
import * as module from "./Module";
import * as subtable from "./Subtable";

export * from "./Light";
export * from "./Dialog";
export * from "./TableLayoutDialog";
export { myParseInt as parseInt, myParseFloat as parseFloat } from "./Light";
export { currency, forms, table, table as tables, module, subtable, subtable as subtables, colopalette };

//На событие dom ready навешиваем эти стандратные обработчики
$(function() {
    "use strict";

    const $body = $(document.body);
    addStandardListeners($body);

    // Добавим плавающие шапки таблиц в момент загрузки страницы
    if (!getPageParam("disableTableSticky")) {
        $body.find("table.table:not(.nosticky)").each(function(this: HTMLTableElement) {
            const $table = $(this);
            const offset = myParseInt($table.attr("data-stickyoffset")!);
            ($table as any).stickyTableHeaders({ fixedOffset: offset });
        });
    }

    const scrollTop = (window as any)._lightScrollTop;
    if (scrollTop) {
        document.documentElement.scrollTop = scrollTop;
    }
});
