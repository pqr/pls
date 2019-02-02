import { addStandardListeners } from "./Light";

import "./AjaxFields";
import "./Colorpalette";
import * as mainmenu from "./Mainmenu";
import * as colopalette from "./Colorpalette";
import "./MonthWidget";
import * as currency from "./Currency";
import * as forms from "./Form";
import * as table from "./Table";
import * as module from "./Module";
import * as subtable from "./Subtable";
import * as filter from "./Filters";

export * from "./Light";
export * from "./Dialog";
export * from "./TableLayoutDialog";
export { myParseInt as parseInt, myParseFloat as parseFloat } from "./Light";
export { currency, forms, filter, table, table as tables, module, subtable, subtable as subtables, colopalette, mainmenu };

//На событие dom ready навешиваем эти стандратные обработчики
$(function() {
    "use strict";

    const $body = $(document.body);
    addStandardListeners($body);

    const scrollTop = (window as any)._lightScrollTop;
    if (scrollTop) {
        document.documentElement.scrollTop = scrollTop;
    }
});
