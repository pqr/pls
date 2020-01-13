import { getBeautyMoney, myParseFloat, myParseInt, round, stripTags, parseJson } from "./Light";
import { Filters } from "./Filters";
import { FieldsWidth } from "./TableLayoutDialog";

export type FilterChangeHandler = (newValue: string, $node: JQuery) => void;
export type SelectRowHandler = (selected: boolean, $tr: JQuery) => void;
export type SelectAllHandler = (allSelected: boolean) => void;
export type ReloadHandler = (table: Table) => void;
export type ResetHandler = (table: Table) => void;

export interface ReloadParams {
    page?: number;
    showall?: string; //'1' или '0'
    sort?: string;
    order?: string;
    filterValues?: string;
    fieldsShow?: string;
    fieldsWidth?: FieldsWidth;
}

export class Table {
    private $div: JQuery;
    private $table: JQuery;
    private $tbody: JQuery;
    private name: string;
    private linkModule: string;
    private linkBlank: boolean;
    private linkModuleDialog: boolean;
    private currentPage: number;
    private currentReloadXhr: JQueryXHR | null;

    private filters: Filters | undefined;

    private selectRowHandlers: SelectRowHandler[] = [];
    private selectAllHandlers: SelectAllHandler[] = [];
    private reloadHandlers: ReloadHandler[] = [];

    public reloadUrl: string;

    constructor($div: JQuery) {
        this.$div = $div;
        this.$table = $div.find("table.table");
        this.$tbody = this.$table.children("tbody");
        this.name = $div.attr("data-tablename") || "";
        this.reloadUrl = $div.attr("data-reloadurl") || "";
        this.linkModule = $div.attr("data-linkmodule") || "";
        this.linkBlank = !!$div.attr("data-linkblank");
        this.linkModuleDialog = !!$div.attr("data-linkmoduledialog");

        $div.on("click", "span.table-excel a", (e: any) => {
            const ids = this.getSelectedValues();
            if (ids.length !== 0) {
                e.preventDefault();
                document.location.href = e.currentTarget.href + "&excelIds=" + ids.join(",");
            }
        });

        //Попробуем найти текущую страницу из вёрстки
        //Необходимо хранить текущую страницу для метода reload() (удобно при смене table layout settings и при удалении в ModuleAbstract)
        const $strong = $div.find(".table-pages:first").find("strong");
        this.currentPage = $strong.length > 0 ? myParseInt($strong.text()) : 1;

        const that = this;

        // Проверим, есть ли форма фильтров поиском по глобальному идентификатору?
        const filtersFormEl = document.getElementById(`${this.name}-filters-form`);
        if (filtersFormEl) {
            this.filters = new Filters(
                this.name,
                this.filtersSubmitAction.bind(this),
                this.filtersResetAction.bind(this),
            );
        }

        $div.on("click", ".table-pages a", (e: any) => {
            e.preventDefault();

            const tableTop = $div.offset()!.top;
            //Проверим, виден ли верх таблицы при пажинации, если нет, нужно прокрутить
            const $body = $(document.body);
            if ($body.scrollTop()! > tableTop) {
                //window.scrollTo($body.scrollLeft(), tableTop - 10);
                $("html,body").animate({ scrollTop: tableTop - 10 }, 500);
            }

            const newPage = myParseInt((<HTMLAnchorElement>e.currentTarget).hash.substring(1));
            this.reload({ page: newPage });
        });

        $div.on("click", "a.table-showall-link", (e: any) => {
            e.preventDefault();
            const showall = (<HTMLAnchorElement>e.currentTarget).hash.substring(1);
            this.reload({ showall: showall, page: 1 });
        });

        $div.on("click", "a.table-sort-link", (e: any) => {
            e.preventDefault();
            const params = (<HTMLAnchorElement>e.currentTarget).hash.substring(1).split("|");
            this.reload({ sort: params[0], order: params[1], page: 1 });
        });

        // Переход по ссылке зашитой внутри строки таблицы при двойном клике
        $div.on("dblclick", "table.table > tbody > tr", (e: any) => {
            const $tr = $(e.currentTarget);
            let href = $tr.attr("data-href");
            if (!href) {
                // Если внутри самой строки найти href не удалось, попробуем сформировать ссылку из парамтера href у всей таблицы
                if (this.linkModule) {
                    // У всего контейнера определён параметр linkmodule, попробуем найти id у самой строки
                    const linkId = $tr.attr("data-linkid");
                    if (linkId) {
                        href = `?a=${that.linkModule}&id=${linkId}`;

                        if (this.linkModuleDialog) {
                            href += "&func=Dialog";
                        }

                        // Попробуем проверить, есть ли дополнительные параметры для url
                        const tableLinkUrlParams = this.$table.attr("data-linkurlparams");
                        if (tableLinkUrlParams) {
                            href += "&" + tableLinkUrlParams;
                        }

                        const rowLinkUrlParams = $tr.attr("data-linkurlparams");
                        if (rowLinkUrlParams) {
                            href += "&" + rowLinkUrlParams;
                        }
                    }
                }
            }

            // Если в итоге удалось сформировать ссылку, редиректим страницу
            if (href) {
                // Снимаем окраску findid внутри данной таблицы
                that.$table.find("tr.findid").removeClass("findid");
                // Перемещаем окраску findid на строку, по которой был совершен двойной клик
                $tr.addClass("findid");
                // Переходим на другую страницу
                if (this.linkModuleDialog || href.substr(-5) === '#ajax') {
                    $.ajax(href);
                } else if (this.linkBlank) {
                    window.open(href, "_blank");
                } else {
                    document.location.href = href;
                }
            }
        });

        //Выделение строк в таблице содержащих чекбокс с классом .cb
        $div.on("click", "table.table > tbody > tr", (e: any) => {
            //Делаем выделение строки, только если клик был совершен
            // не по ссылке
            // не по кнопке color
            // и не по какому-либо элементу формы (input, textarea, select, button)
            const $target = $(e.target);
            if (
                $target.closest("a").length > 0 ||
                $target.hasClass("colorpalette") ||
                ($target.is("input") && !$target.hasClass("cb") && !$target.hasClass("rb")) ||
                ($target.hasClass("select2") || $target.closest(".select2").length > 0) ||
                $target.is("textarea") ||
                $target.is("select") ||
                $target.is("button") ||
                ($target.hasClass("no-select-row") || $target.closest(".no-select-row").length > 0)
            ) {
                return;
            }

            const $tr = $(e.currentTarget);
            //Проверим, есть ли внутри этого tr чекбокс с классом cb?
            const $cb = $tr.find("input[type=checkbox].cb");
            if ($cb.length > 0) {
                let currentSelected: boolean = $cb.prop("checked");
                //Если клик был совершен непосредсвенно на чекбоксе, то $cb.prop("checked") возвращает статус после нажатия
                if ($target.is($cb)) {
                    currentSelected = !currentSelected;
                }

                //В качестве вторго параметра (selected) передаём инвертированное значение текущего статуса
                //т.е. если сейчас строка выбрана, то нужно снять выделение (передать вторым параметром selected = false)
                this.selectRow($tr, !currentSelected);
            } else {
                //Возможно, в этой сторке есть радио кнопка?
                const $rb = $tr.find("input[type=radio].rb");
                if ($rb.length > 0) {
                    //Делаем выделение этой строки, но перед этим снимим выделение со всех других строк
                    $tr.closest("table.table")
                        .find("tr")
                        .removeClass("selected");

                    //Выделяем строку с помощью специального метода в классе Table, чтобы запустить обработчики события
                    this.selectRow($tr, true);
                }
            }
        });

        //Выделение всех строк или снятие выделения при клике на чекбокс в заголовке таблицы
        $div.on("click", "table.table > thead .cb-all", (e: any) => {
            //Найдём все строки в таблице и пройдём их в цикле
            this.selectAll(e.currentTarget.checked);
        });

        $(`a.check-all-link[data-tablename="${this.name}"]`).on("click", (e: any) => {
            e.preventDefault();
            this.selectAll(true);
        });

        $(`a.uncheck-all-link[data-tablename="${this.name}"]`).on("click", (e: any) => {
            e.preventDefault();
            this.selectAll(false);
        });
    }

    private filtersSubmitAction(filterValues:any) {
        const reloadParams = {
            filterValues: JSON.stringify(filterValues),
            page: 1
        };

        return new Promise((resolve) => {
            this.reload(reloadParams, () => {
                const hiddenInput = this.getDivNode().find("input[name=filterValues]");
                if (hiddenInput.length > 0) {
                    const newValues = parseJson(<string>hiddenInput.val());
                    this.filters!.setValuesAfterReload(newValues);
                }
                resolve();
            });
        });
    }

    private filtersResetAction() {
        if (this.isLoading()) {
            // Если в момент нажатия RESET уже происходила какая-то загрузка данных, то её нужно прервать,
            this.cancelReload();
        }

        if (globalResetHandlers.has(this.name)) {
            const globalResetHandlersForTable = globalResetHandlers.get(this.name)!;
            globalResetHandlersForTable.forEach(handler => {
                handler(this);
            });
        }
    }

    getName(): string {
        return this.name;
    }

    getDivNode(): JQuery {
        return this.$div;
    }

    getTableNode(): JQuery {
        return this.$table;
    }

    getTBodyNode(): JQuery {
        return this.$tbody;
    }

    onFiltersReset(handler: () => void): Table {
        if (this.filters !== undefined) {
            this.filters.resetHandlers.push(handler);
        }
        return this;
    }

    onFilterChange(fieldKey: string, handler: FilterChangeHandler): Table {
        if (this.filters !== undefined) {
            this.filters.form.onFieldChange(fieldKey, handler);
        }
        return this;
    }

    filtersSubmit() {
        if (this.filters !== undefined) {
            this.filters.submit();
        }
    }

    onSelectAll(handler: SelectAllHandler): Table {
        this.selectAllHandlers.push(handler);
        return this;
    }

    onSelectRow(handler: SelectRowHandler): Table {
        this.selectRowHandlers.push(handler);
        return this;
    }

    onReload(handler: ReloadHandler): Table {
        this.reloadHandlers.push(handler);
        return this;
    }

    getFilterValue(fieldKey: string): string | number {
        if (this.filters !== undefined) {
            return this.filters.getValue(fieldKey);
        }
        return "";
    }

    setFilterValue(fieldKey: string, newValue: string | number): Table {
        if (this.filters !== undefined) {
            this.filters.setValue(fieldKey, newValue);
        }
        return this;
    }

    private callSelectRowHandlers($tr: JQuery, selected: boolean) {
        this.selectRowHandlers.forEach((handler: SelectRowHandler) => {
            handler(selected, $tr);
        });
    }

    selectRow($tr: JQuery, selected: boolean): void {
        // Сделаем подствеку строки специальным классом
        $tr.toggleClass("selected", selected);

        //Отмечаем чекбокс или радио-кнопку
        $tr.find("input[type=checkbox].cb, input[type=radio].rb").prop("checked", selected);

        // После выделения текущей строки, проверим, есть ли чекбокс в заголовке таблицы, нужно ли его переключить?
        this.updateHeadCheckbox();
        // Обновим счётчик выделенных строк рядом со ссылкой "Export to Excel"
        this.updateExcelSelectedCountSpan();

        // Вызовем пользовательские обработчики (если такие были)
        this.callSelectRowHandlers($tr, selected);
    }

    updateHeadCheckbox() {
        const $headCb = this.getHeadCheckbox();
        if ($headCb.length > 0) {
            // Если есть хотя бы один неотмеченный чекбокс среди строк таблицы - снимаем выделение с headCb
            const allSelected = this.$tbody.find("input[type=checkbox].cb:not(:checked)").length === 0;
            $headCb.prop("checked", allSelected);
        }
    }

    private getHeadCheckbox(): JQuery {
        return this.$table.children("thead").find(".cb-all");
    }

    /**
     * Вспомагательная функция для обработчиков в таблицах - выделяет все строки в таблице или снимает выделение
     * у которых есть чекбокс с классом cb
     */
    selectAll(selected: boolean): void {
        this.$tbody.find("tr").each(function(this: HTMLTableRowElement) {
            const $tr = $(this);
            const $cb = $tr.find("input[type=checkbox].cb");
            if ($cb.length > 0) {
                //Выделим строку специальным классом
                $tr.toggleClass("selected", selected);
                //Выделим чекбокс
                $cb.prop("checked", selected);
            }
        });

        this.getHeadCheckbox().prop("checked", selected);
        this.updateExcelSelectedCountSpan();
        this.selectAllHandlers.forEach((handler: SelectAllHandler) => {
            handler(selected);
        });
    }

    reload(reloadParams: ReloadParams = {}, callback?: () => void): JQueryXHR {
        const newPage = myParseInt(reloadParams.page);
        if (newPage > 0) {
            // При данном вызове метода realod меняется страница - сохраним в currentPage
            this.currentPage = newPage;
        } else {
            // Если же страница не была передана, то нужно взять из currentPage
            reloadParams.page = this.currentPage;
        }

        // Выполним ajax запрос к серверу, чтобы обновить таблицу
        // Если уже есть какой-то запрос на обновление этой таблицы, то его нужно прервать
        if (this.currentReloadXhr) {
            // Если уже есть какой-то ajax запрос в процессе, то его нужно убить
            this.currentReloadXhr.abort();
        }

        // При передаче параметров на сервер, основным параметрам влияющим на таблицу дадим префикс с именем таблицы
        const { fieldsShow, page, showall, sort, order, fieldsWidth, ...other } = reloadParams;
        const postData: any = { ...other };
        if (typeof fieldsShow !== "undefined") {
            postData[this.name + "_fieldsShow"] = fieldsShow;
        }
        if (typeof page !== "undefined") {
            postData[this.name + "_page"] = page;
        }
        if (typeof showall !== "undefined") {
            postData[this.name + "_showall"] = showall;
        }
        if (typeof sort !== "undefined") {
            postData[this.name + "_sort"] = sort;
        }
        if (typeof order !== "undefined") {
            postData[this.name + "_order"] = order;
        }
        if (typeof fieldsWidth !== "undefined") {
            postData[this.name + "_fieldsWidth"] = fieldsWidth;
        }

        // Запоминаем ссылку на текущий запрос обновления этой таблицы
        this.currentReloadXhr = $.post(this.reloadUrl, postData);

        // Запоминим выделенные галочками элементы, чтобы после перезагрузки таблицы вернуть выделение
        const selectedValues = this.getSelectedValues();

        this.currentReloadXhr
            .done((newTableHtml: string) => {
                this.$div.html(newTableHtml);
                //Обновим керующие переменные
                this.$table = this.$div.find("table.table");
                this.$tbody = this.$table.find("tbody");
                // Вернём отметки галочками к состоянию до перезагрузки
                selectedValues.forEach(value => {
                    this.$tbody.find("input[type=checkbox].cb").each(function(this: HTMLInputElement) {
                        if (this.value == value) {
                            this.checked = true;
                            const $tr = $(this).closest("tr");
                            $tr.toggleClass("selected", true);
                        }
                    });
                });

                // После выделения текущей строки, проверим, есть ли чекбокс в заголовке таблицы, нужно ли его переключить?
                this.updateHeadCheckbox();
                // Обновим счётчик выделенных строк рядом со ссылкой "Export to Excel"
                this.updateExcelSelectedCountSpan();
            })
            .always(() => {
                this.currentReloadXhr = null;

                if (callback) {
                    callback();
                }

                this.reloadHandlers.forEach(handler => {
                    handler(this);
                });

                if (globalReloadHandlers.has(this.name)) {
                    const globalReloadHandlersForTable = globalReloadHandlers.get(this.name)!;
                    globalReloadHandlersForTable.forEach(handler => {
                        handler(this);
                    });
                }
            });

        // Возвращаем JQueryXHR
        return this.currentReloadXhr;
    }

    reloadRow($tr: JQuery) {
        if (
            $tr.length === 0 ||
            $tr.hasClass("no-reload-row-after-ajax-field-change") ||
            !this.$div.attr("data-allowtablereloadurl")
        ) {
            // На строке таблице стоит специальный класс, который запроещает перезагружать отдельные строки
            // Или на всей таблице нет флага data-allowtablereloadurl
            return;
        }

        const rowId = $tr.attr("data-rowid");
        if (!rowId) {
            return;
        }

        if (this.currentReloadXhr) {
            this.currentReloadXhr.abort();
        }

        const url = `${this.reloadUrl}&renderRowForAjax=${this.name}&rowId=${rowId}&nouserrefreshtime=1`;

        // Запоминаем ссылку на текущий запрос обновления этой таблицы
        this.currentReloadXhr = $.get(url);

        this.currentReloadXhr.done((newRowHtml: any) => {
            if (!newRowHtml) {
                return;
            }

            $tr.replaceWith(newRowHtml);
        });

        // Возвращаем JQueryXHR
        return this.currentReloadXhr;
    }

    isLoading() {
        return !!this.currentReloadXhr;
    }

    cancelReload() {
        // Если уже есть какой-то запрос на обновление этой таблицы, то его нужно прервать
        if (this.currentReloadXhr) {
            this.currentReloadXhr.abort();
        }
    }

    /**
     * Возвращает массив из значений заключённых внутри чекбоксов в таблице table-tableName-table
     */
    getSelectedValues(): string[] {
        return this.$tbody
            .find("input[type=checkbox].cb:checked")
            .toArray()
            .map((el: HTMLInputElement) => el.value);
    }

    getSelectedCount(): number {
        return this.$tbody.find("input[type=checkbox].cb:checked").length;
    }

    /**
     * Возвращает массив из значений заключённых внутри чекбоксов в таблице table-tableName-table
     */
    getSelectedRows(): JQuery {
        return this.$tbody.find("input[type=checkbox].cb:checked").closest("tr");
    }

    /**
     * Возвращает значение первой отмеченной радио-точки
     */
    getSelectedRadio(): string {
        return (
            (this.$tbody
                .find("input[type=radio].rb:checked")
                .first()
                .val() as string) || ""
        );
    }

    updateExcelSelectedCountSpan() {
        const selectedCount = this.getSelectedCount();
        const $a = this.$div.find("span.table-excel a");
        let $span = $a.find(".table-excel-selected-count-span");
        if (selectedCount === 0) {
            // Если не выделено ничего, удаляем специальный span в котором отображено число выделенных строк
            // (возможно, этого span и не было в вёрстке, в этом случае jQuery ничего не сделает)
            $span.remove();
        } else {
            // Если же что-то было выделено, нужно вывести кол-во выделенных строк
            if (!$span.length) {
                // Специальный span пока не существует в вёрстке, быстренько создадим его
                $span = $('<span class="table-excel-selected-count-span"></span>').appendTo($a);
            }
            $span.html(`&nbsp;(${selectedCount})`);
        }
    }

    /**
     * Обновляет div в котором находятся выпадающий список с massaction
     * Обновление происходит во время прокрутки таблицы или при выделении строк таблицы
     */
    updateMassactionDiv() {
        const $massactionDiv = $(".listmassaction");
        if ($massactionDiv.length === 0) {
            // В этом модуле вообще нет div отвечающего за massaction
            return;
        }

        // Сразу удаляем все total-span, которые могли быть в этом div -
        // в них отображались старые знаения total по полям
        // (ниже будет пересчёт этих total и повторный вывод)
        $massactionDiv.find(".listmassaction__total").remove();

        const $countSpan = $massactionDiv.find('label[for="massaction-select"] > span');

        const selectedCount = this.getSelectedCount();
        if (selectedCount === 0) {
            // Если ничего не выделено, больше ничего и не делаем
            $massactionDiv.removeClass("sticky");
            $countSpan.html("");
            return;
        }

        // Если что-то выделено галочками, обязательно добавляем класс sticky, который выставит position: sticky; bottom: 0;
        $massactionDiv.addClass("sticky");
        $countSpan.html(` (${selectedCount})`);

        // Некоторые колонки могут быть отмечены атрибутом data-total="true"
        // по ним посчитаем сумму по выделенным галочками
        const $thead = this.getTableNode().find("thead");
        const table = this;
        $thead.find("th[data-total]").each(function(this: HTMLTableHeaderCellElement) {
            const $th = $(this);
            // Узнаем порядковый номер колонки, чтобы далее прочитать все значения
            // в выделенных строках
            const colIndex = $thead.find("th").index($th);
            const fieldType = $th.attr("data-total");

            let totalHtml: string | null = null;
            switch (fieldType) {
                case "MoneyWithCurrency":
                    totalHtml = table.getSelectedTotalMoneyWithCurrencyHtml(colIndex);
                    break;

                case "Money":
                    totalHtml = table.getSelectedTotalMoneyHtml(colIndex);
                    break;

                default:
                    totalHtml = table.getSelectedTotalHtml(colIndex);
            }

            if (totalHtml === null) {
                return;
            }

            // Имя поля возьмём из th, чтобы вывести внизу в massaction div
            const fieldName = $th
                .html()
                .replace(/<br>/g, " ")
                .replace(/<\/?[^>]+>/gi, "")
                .replace("▼", "")
                .replace("▲", "")
                .trim();

            const $totalSpan = $(`<div class="listmassaction__total"></div>`).append(
                `<div class="listmassaction__totalfieldname"><strong>${fieldName}:&nbsp;</strong></div>`
            );
            // Склеиваем все суммы по валютам через запятую и доавляем в текущий $totalSpan
            $totalSpan.append(`<div class="listmassaction__totalamount">${totalHtml}</div>`);

            // Итоговый результат вставляем в $massactionDiv
            $massactionDiv.append($totalSpan);
        });
    }

    getSelectedTotalMoneyWithCurrencyHtml(colIndex: number): string | null {
        // Сразу заготовим Map с разбивкой по валютам
        const totalPerCurrency = new Map<string, number>();

        // Пройдём по всем выделенным строкам
        this.getSelectedRows().each((_, tr) => {
            const tdHtml = $(tr)
                .find("td")
                .eq(colIndex)
                .html();

            // HTML код внутри ячейки может содержать <br>
            // это значит несколько сумм в режиме groupconcat
            // разобьём на элементарные строки
            tdHtml.split("<br>").forEach((oneLineHtml: string) => {
                // Вырежем все теги (которые обычно оформляют суммы)
                const text = stripTags(oneLineHtml);

                // Попробуем распарсить текст, возможно там какие суммы с валютами?
                // Например "123 456.78 USD" или "1 234.00 $"
                // По умолчанию примим валюту пустой
                let currencyIso: string = "";
                const parts = text.split(" ");
                // Прововерим последнюю часть массива после разбивки по пробелам - там может быть реальная валюта
                if (!$.isNumeric(parts[parts.length - 1])) {
                    // Нашли валюту в конце числа, вынимаем валюту из массива частей
                    currencyIso = parts.pop()!;
                }
                // Оставшиеся части после изъятия валюты склеиваем обратно
                const amount = myParseFloat(parts.join(""));
                if (!totalPerCurrency.has(currencyIso)) {
                    // Нашли какую-то новую валюту, которая не встречалась ранее
                    totalPerCurrency.set(currencyIso, amount);
                } else {
                    // Эта валюта уже есть в Map totalPerCurrency, прибавляем текующую сумму amount
                    totalPerCurrency.set(currencyIso, totalPerCurrency.get(currencyIso)! + amount);
                }
            });
        });

        // После того как прошлись по всем строкам и собрали результат в totalPerCurrency
        // выведем этот результат в massaction div
        let perCurrencyBeautyAmounts: string[] = [];
        totalPerCurrency.forEach((total: number, currencyIso: string) => {
            if (total != 0) {
                perCurrencyBeautyAmounts.push(getBeautyMoney(total, true, currencyIso));
            }
        });
        if (perCurrencyBeautyAmounts.length === 0) {
            return null;
        }

        return perCurrencyBeautyAmounts.join("<br>");
    }

    getSelectedTotalMoneyHtml(colIndex: number): string | null {
        const totalAmount = this.calcSelectedTotal(colIndex);
        if (totalAmount == 0) {
            return null;
        }

        return getBeautyMoney(totalAmount, true);
    }

    getSelectedTotalHtml(colIndex: number): string | null {
        const totalAmount = this.calcSelectedTotal(colIndex);
        if (totalAmount == 0) {
            return null;
        }

        return round(totalAmount).toString();
    }

    calcSelectedTotal(colIndex: number): number {
        let total = 0;

        // Пройдём по всем выделенным строкам
        this.getSelectedRows().each((_, tr) => {
            const tdHtml = $(tr)
                .find("td")
                .eq(colIndex)
                .html();

            // HTML код внутри ячейки может содержать <br>
            // это значит несколько сумм в режиме groupconcat
            // разобьём на элементарные строки
            tdHtml.split("<br>").forEach((oneLineHtml: string) => {
                // Вырежем все теги (которые обычно оформляют суммы)
                const amount = myParseFloat(oneLineHtml);
                total += amount;
            });
        });

        return total;
    }

    initMassactionListeners() {
        try {
            this.onSelectRow(() => this.updateMassactionDiv.call(this));
            this.onSelectAll(() => this.updateMassactionDiv.call(this));
            this.onReload(() => this.updateMassactionDiv.call(this));
        } catch (e) {
            console.log(e);
        }
    }
}

const tablesStore: { [key: string]: Table } = {};

export function init($root: JQuery) {
    $root.find("div.table-div").each(function(this: HTMLDivElement) {
        const $div = $(this);
        const tableObj = new Table($div);
        const name = tableObj.getName();
        tablesStore[name] = tableObj;
    });
}

export function get(name: string): Table {
    if (tablesStore[name] === undefined) {
        throw Error("Unknown table: " + name);
    }

    return tablesStore[name];
}

export function has(name: string): boolean {
    return tablesStore[name] !== undefined;
}

export function findClosestTable($node: JQuery): Table | null {
    const $div = $node.closest("div[data-tablename]");
    if ($div.length === 0) {
        return null;
    }

    const tableName = $div.attr("data-tablename") as string;
    if (!has(tableName)) {
        return null;
    }

    return get(tableName);
}

const globalReloadHandlers = new Map<string, ReloadHandler[]>();
export function onReload(tableName: string, onReloadHandler: ReloadHandler) {
    const handlersForTable: ReloadHandler[] = globalReloadHandlers.has(tableName)
        ? globalReloadHandlers.get(tableName)!
        : [];

    handlersForTable.push(onReloadHandler);
    globalReloadHandlers.set(tableName, handlersForTable);
}

const globalResetHandlers = new Map<string, ResetHandler[]>();
export function onFiltersReset(tableName: string, onResetHandler: ResetHandler) {
    const handlersForTable: ReloadHandler[] = globalResetHandlers.has(tableName)
        ? globalReloadHandlers.get(tableName)!
        : [];

    handlersForTable.push(onResetHandler);
    globalResetHandlers.set(tableName, handlersForTable);
}

//Инициализируем таблицы
init($(document.body));
