import {
    addStandardListeners,
    calcTotalPerCurrency,
    getBeautyValue,
    getFieldNode,
    getModule,
    getNodeValue,
    isLangRu,
    myParseFloat,
    myParseInt,
    parseJson,
    parseTextValue,
    postMetric,
    round,
    setNodeValue,
    loadSortableJs
} from "./Light";
import { getObjectId } from "./Module";
import { Dialog } from "./Dialog";

declare var Sortable: any;

export interface Field {
    mode: string; //'view' или 'edit'
    type: string;
    total: boolean;
    copy: boolean;
    colIndex: number;
    disablePaste: boolean;
    tableAlign: string;
    precision?: number;
    _optionsHash?: { [key: string]: string };
}

export type Fields = { [key: string]: Field };

export type FieldValue = string | number;

export type RowNum = string | number;
export type RowData = { [key: string]: string | number };

export type FieldCalcTotalHandler = (this: Subtable, total: number) => void;

export type AddHandler = (this: Subtable, newRowNum: RowNum, $newRow: JQuery) => void;
export type DelHandler = (this: Subtable, rowNum: RowNum) => void;
export type CalcTotalHandler = (subtable: Subtable) => void;

export type FieldChangeHandler = (rowNum: RowNum, newValue: FieldValue, $node: JQuery) => void;

export class Subtable {
    private name: string;

    private $div: JQuery;
    private fields: Fields;
    private $table: JQuery;
    private $thead: JQuery;
    private $tbody: JQuery;
    private $countSpan: JQuery;
    private tbodyEl: HTMLElement;
    private totalRowEl: HTMLElement;
    private idsEl: HTMLInputElement;
    private delRowsEl: HTMLInputElement;

    //+Для различный операций над ячейками неплохо было бы закешировать индексы полей
    private fieldKeys: string[] = [];
    private hiddenColumns: boolean[] = [];
    private copyOnAddFieldKeys: string[] = [];

    //Хеш-массивы fieldKey => [...changeHandlers...]
    private fieldCalcTotalHandlers: { [key: string]: FieldCalcTotalHandler[] } = {};

    private onAddHandlers: AddHandler[] = [];
    private onDelHandlers: DelHandler[] = [];
    private onCalcTotalHandlers: CalcTotalHandler[] = [];

    //Кеширующий объект, в качестве клюа выступает связка rowNum + fieldKey
    private fieldNodesCache: { [key: string]: JQuery } = {};

    //Грязный хак с глобальным флагом: когда вызывается метод addRows он добавляет несколько строк за один раз
    //Этот метод может быть вызван из клиентского скрипта (например, копирование Cost-sheet) или в результате paste диалога
    //При каждом добавлении строки в неё записываются какие-то данные и срабатывают обработчики onChange на отдельных полях
    //если какое-то поле подразумевает под собой подсчёт total в столбце, то автоматически запустится calcTotal по этому столбцу
    //и так на каждую вставленную строку!
    //Решение: введём глобальный флаг, если он установлен в true, метод calcTotal ничего не будет делать!
    private calcTotalEnabled = true;

    private orderManual: boolean;

    constructor($div: JQuery) {
        this.$div = $div;
        this.fields = parseJson($div.attr("data-fields")!);
        this.name = <string>$div.attr("data-subtablename")!;
        this.orderManual = !!$div.attr("data-sortable")!;
        this.$table = $div.find("table.table").first();
        this.$thead = this.$table.children("thead").first();
        this.$tbody = this.$table.children("tbody").first();
        this.$countSpan = $(`.${this.name}-subtable-count-span`);

        this.tbodyEl = this.$tbody.get(0);

        this.totalRowEl = this.$tbody.find("#" + this.name + "-subtable-tr-total").get(0);
        this.idsEl = <HTMLInputElement>$div.find("#" + this.name + "-subtable-ids").get(0);
        this.delRowsEl = <HTMLInputElement>$div.find("#" + this.name + "-subtable-delrows").get(0);

        //Сформируем массив colIndex => fieldKey
        for (const fieldKey in this.fields) {
            this.fields[fieldKey].colIndex = this.fieldKeys.length;
            this.fieldKeys.push(fieldKey);
            this.hiddenColumns.push(this.$thead.find(`th[data-fieldkey="${fieldKey}"]`).hasClass("display-none"));

            //Сформируем массив из ключей полей, которые нужно копировать при добавлении новой строки
            if (this.fields[fieldKey].copy) {
                this.copyOnAddFieldKeys.push(fieldKey);
            }
        }

        const that = this;

        //Добавляем обработчик на ссылку добавления новой строки
        $div.find(".subtable-add-link").on("click", (e: any) => {
            e.preventDefault();

            let data: { [key: string]: string } = {};
            if (this.copyOnAddFieldKeys.length > 0) {
                const $lastTr = that.$tbody
                    .find("tr")
                    .not(".total")
                    .last();
                if ($lastTr.length > 0) {
                    const lastRowNum = getRowNum($lastTr);
                    //Нашли последнюю строку, вычленим данные по полям из массива copyOnAddFieldKeys
                    this.copyOnAddFieldKeys.forEach((fieldKey: string) => {
                        data[fieldKey] = this.getFieldValue(lastRowNum, fieldKey).toString();

                        if (this.fields[fieldKey].type === "MoneyWithCurrency") {
                            //Если это поле MoneyWithCurrency, то будем копировать не всё значение, а только валюту
                            data[fieldKey] = data[fieldKey].substring(0, 3) + " 0";
                        }
                    });
                }
            }

            //Вызываем публичный метод addRow - он пересчитывает total
            that.addRow(data);
        });

        // обработчик на копирование строки
        $div.on("click", ".subtable-copy-link", (e: any) => {
            e.preventDefault();

            let data: { [key: string]: string } = {};
            const rowNum = getRowNum($(e.target));

            const fieldKeys: Array<string> = this.fieldKeys;
            const removedFieldKeys: Array<string> = ["Del", "Copy", "Sort"];

            removedFieldKeys.forEach((fieldKey: string) => {
                const index = fieldKeys.indexOf(fieldKey, 0);
                if (index > -1) {
                    fieldKeys.splice(index, 1);
                }
            });

            fieldKeys.forEach((fieldKey: string) => {
                data[fieldKey] = this.getFieldValue(rowNum, fieldKey).toString();

                if (this.fields[fieldKey].type === "MoneyWithCurrency") {
                    //Если это поле MoneyWithCurrency, то будем копировать не всё значение, а только валюту
                    data[fieldKey] = data[fieldKey].substring(0, 3) + " 0";
                }
            });

            //Вызываем публичный метод addRow - он пересчитывает total
            that.addRow(data);
        });

        $div.find(".change-all").on("click", (e: any) => {
            e.preventDefault();

            //Hide changeAll button
            const $link = $(e.currentTarget);
            $link.addClass("display-none");

            //Отобразим выпадающий список
            const $node = $link.next(".display-none");
            $node.removeClass("display-none");

            const nodeTag = <string>$node.prop("tagName")!;
            const inputType = <string>$node.attr("type")!;

            const fieldKey = <string>$link.attr("data-fieldkey")!;
            //Повесим на него обработчик изменения, который обновит все строки
            $node.on("change keyup", (e: any) => {
                const input = <HTMLInputElement>e.currentTarget;
                let val: FieldValue;
                if (nodeTag === "INPUT" && inputType === "checkbox") {
                    val = input.checked ? 1 : 0;
                } else {
                    val = <string>input.value!;
                }
                this.eachRow((rowNum: RowNum) => {
                    this.setFieldValue(rowNum, fieldKey, val);
                });
            });
        });

        $div.find(".change-all-string").on("click", e => {
            e.preventDefault();

            const $link = $(e.currentTarget);
            const fieldKey = <string>$link.attr("data-fieldkey")!;

            // отобразим диалог с выбором полной замены текста или только часть (с поиском)
            const that = this;

            const dialog = new Dialog(
                `
                        <div>
                            <div><label><input type="radio" name="type-update-string" value="update" checked="checked"> Обновить полностью</label></div>
                            <div class="area-update" style="padding: 1em;">
                                <input type="text" class="input-default" name="text-update" placeholder="Обновить на">
                            </div>
                            <div style="margin-top: 0.5em;"><label><input type="radio" name="type-update-string" value="replace"> Заменить часть</label></div>
                            <div class="area-replace display-none" style="padding: 1em;">
                                <div><input type="text" class="input-default" name="text-find" placeholder="Искомый текст"></div>
                                <div><input type="text" class="input-default" name="text-replace" placeholder="Заменить на"></div>
                            </div>
                      </div>
                    `,
                {
                    width: "360px",
                    buttonOkText: "Заменить",
                    buttonOkHandler: (dialog: Dialog) => {
                        const type = <string>dialog.$divContent!.find("input[type='radio']:checked").val();
                        if (type == "update") {
                            const text = <string>dialog.$divContent!.find("input[name='text-update']").val();
                            this.eachRow((rowNum: RowNum) => {
                                this.setFieldValue(rowNum, fieldKey, text);
                            });
                        } else if (type == "replace") {
                            const find = <string>dialog.$divContent!.find("input[name='text-find']").val();
                            const replace = <string>dialog.$divContent!.find("input[name='text-replace']").val();
                            that.eachRow((rowNum: RowNum) => {
                                let text = <string | number>that.getFieldValue(rowNum, fieldKey);
                                text = text.toString().replace(find, replace);
                                this.setFieldValue(rowNum, fieldKey, text);
                            });
                        }

                        dialog.close();
                    }
                }
            );

            dialog.open();

            dialog.$divContent!.find("input[type='radio']").on("change", e => {
                const type = $(e.target).val();
                dialog.$divContent!.find("div[class^='area']").addClass("display-none");
                dialog.$divContent!.find("div.area-" + type).removeClass("display-none");
            });
        });

        //Добавляем обработчик на ссылку вставки нескольких строк
        $div.find(".subtable-paste-link").on("click", e => {
            e.preventDefault();
            this.showPasteDialog();
        });

        //Добавим обработчик на ссылки удаления строк
        $div.on("click", ".subtable-del-link", e => {
            e.preventDefault();
            //Определим номер удаляемой строки
            const deleteRowNum = getRowNum($(e.currentTarget));
            this.delRow(deleteRowNum);
        });

        // Отключим стандартный обработчик на талбицах (который установлен в Table.ts)
        $div.find("div.table-div").off("click", "a.table-sort-link");
        // Добавим особый обработчик в режиме edit
        $div.on("click", "a.table-sort-link", (e: any) => {
            e.preventDefault();

            const currentTarget = <HTMLAnchorElement>e.currentTarget;

            const params = currentTarget.hash.substring(1).split("|");
            const sortFieldKey = params[0],
                sortOrder = params[1];
            currentTarget.hash = "#" + sortFieldKey + "|" + (sortOrder === "ASC" ? "DESC" : "ASC");

            let rows: Array<any> = [];
            this.eachRow((rowNum: RowNum) => {
                const $row = this.getRowNode(rowNum);
                rows.push({
                    rowEl: $row.get(0),
                    sortValue: this.getFieldValue(rowNum, sortFieldKey)
                });
                $row.find("input.input-date").datepicker("destroy");
                $row.remove();
            });

            rows = rows.sort((a, b) => {
                if (a.sortValue > b.sortValue) {
                    return sortOrder === "ASC" ? 1 : -1;
                } else if (a.sortValue < b.sortValue) {
                    return sortOrder === "ASC" ? -1 : 1;
                }
                return 0;
            });

            rows.forEach(({ rowEl }: { rowEl: HTMLTableRowElement }) => {
                this.tbodyEl.insertBefore(rowEl, this.totalRowEl);
            });

            addStandardListeners(this.$tbody);

            this.$thead.find(`th span.tr-sort-icon`).remove();
            let icon = sortOrder === "DESC" ? "▲" : "▼";
            this.$thead
                .find(`th[data-fieldkey="${sortFieldKey}"]`)
                .append('<span class="tr-sort-icon">' + icon + "</span>");

            $.post("index.php?a=Core&ps=SetTableUserParams", {
                table: getModule() + this.name,
                sortFieldKey: sortFieldKey,
                sortOrder: sortOrder
            });
        });

        // Добавим обработчики на пересчёт Total при изменнии полей у которых стоит флаг total
        this.fieldKeys.filter(fieldKey => this.fields[fieldKey].total).forEach(fieldKey => {
            this.onFieldChange(fieldKey, () => {
                this.calcTotal(fieldKey);

                // Запустим общие обрабочики события calcTotal
                this.onCalcTotalHandlers.forEach((handler: (subtable: Subtable) => void) => {
                    handler.call(this);
                });
            });
        });

        // Пересчитаем все Total поля
        this.calcTotalAll();
        this.updateCountSpan();

        if (this.orderManual) {
            this.updateSort();
            loadSortableJs().then(() => {
                Sortable.create(this.$tbody.get(0), {
                    animation: 50, // ms, animation speed moving items when sorting, `0` — without animation
                    handle: ".subtable-sort-link", // Restricts sort start click/touch to the specified element
                    draggable: "tr", // Specifies which items inside the element should be sortable
                    onUpdate: function() {
                        that.updateSort();
                    }
                });
            });
        }
    }

    updateSort() {
        this.$tbody.find(">tr").each(function(this: any, sortIndex: number) {
            $(this)
                .find('input[name$="[OrderID]"]')
                .val(sortIndex);
        });
    }

    disableCalcTotal() {
        this.calcTotalEnabled = false;
    }

    enableCaclTotal() {
        this.calcTotalEnabled = true;
        this.calcTotalAll();
        this.updateCountSpan();
    }

    private showPasteDialog() {
        const that = this;

        const dialog = new Dialog('<textarea class="paste-textarea" rows="24"></textarea>', {
            width: "560px",
            buttonOkText: isLangRu ? "Вставить" : "Paste",
            buttonOkHandler: function(dialog: Dialog) {
                const startMs = performance.now();

                //Переберём все строки из вставленного текста
                const text = <string>dialog.$div!.find("textarea").val()!;
                let lines = text.split("\n");
                //Удаляем пустые строки
                lines = lines.filter((line: string) => line != "");

                //Сформируем массив из строк для вставки (строки готовые для передачи в метод addRow()
                const rowsData = lines.map((line: string) => {
                    const lineData = line.split("\t");
                    const rowData: { [key: string]: string } = {};

                    //Перебираем поля и вынимаем из массива lineData ячейки одна за одной
                    for (const fieldKey in that.fields) {
                        //Если для данного поля вставка из буфера не запрещена, используем текущее значение из lineData
                        if (!that.fields[fieldKey].disablePaste && !that.isColHidden(fieldKey)) {
                            rowData[fieldKey] = lineData.shift() || "";
                        }
                    }

                    return rowData;
                });

                //Теперь можно вставить все сформированные строки (метод addRows() сам пересчитает total и обновит count span)
                that.addRows(rowsData);

                const workTimeMs = performance.now() - startMs;
                if (workTimeMs > 1000) {
                    // Если вставка на стороне JavaScript заняла более секунды, то зарегистрируем это событие на сервере
                    postMetric("subtablepaste", workTimeMs, {
                        lines: rowsData.length,
                        module: getModule(),
                        id: getObjectId(),
                        subtable: that.getName(),
                        text: text
                    });
                }

                dialog.close();
            }
        });

        dialog.open();

        const $textarea = dialog.$div!.find("textarea");
        $textarea.focus();
        $textarea.on("keydown", function(e: any) {
            const key = e.keyCode,
                st = $textarea.prop("scrollTop"),
                selStart = $textarea.prop("selectionStart"),
                selEnd = $textarea.prop("selectionEnd");

            let value = <string>$textarea.val()!;

            if (key == 9) {
                e.preventDefault();
                //Only insert a tab when nothing is selected.
                if (selStart === selEnd) {
                    value = value.substring(0, selStart) + String.fromCharCode(9) + value.substring(selStart);
                    $textarea.val(value);
                }
                $textarea.prop("scrollTop", st);
            }
        });
    }

    /**
     * Пользователь может указать в вёрстке специальный тег (или несколько) с классом <subtableName>-count-span и тогда
     * при каждом добавлении или удалении строки в этот span будет записываться текущее число строк
     */
    private updateCountSpan(): void {
        this.$countSpan.html(this.getDataCount().toString());
    }

    calcTotalAll(): void {
        if (!this.calcTotalEnabled) {
            return;
        }

        //Перебираем все поля в таблице и при нахождении полей с флагом calcTotal - вызываем пересчёт поля
        for (const fieldKey in this.fields) {
            if (this.fields[fieldKey].total) {
                this.calcTotal(fieldKey);
            }
        }

        // Запустим общие обрабочики события calcTotal
        this.onCalcTotalHandlers.forEach((handler: (subtable: Subtable) => void) => {
            handler.call(this);
        });
    }

    getTotalValue(fieldKey: string): number | Map<string, number> {
        const fieldValues = this.getFieldValues(fieldKey);

        // Если это тип MoneyWithCurrency, то считать нужно особым образом
        const isMoneyWithCurrency = this.fields[fieldKey].type === "MoneyWithCurrency";
        if (isMoneyWithCurrency) {
            return calcTotalPerCurrency(<string[]>fieldValues);
        }

        // Обычное числовое поле (не MoneyWithCurrency)
        return fieldValues.map(myParseFloat).reduce((total, value) => total + value, 0);
    }

    calcTotal(fieldKey: string) {
        if (!this.calcTotalEnabled) {
            return;
        }

        //Посчитаем total в столбик по всем значениям
        const totalValue = this.getTotalValue(fieldKey);
        //Запишем total в специально подготовленную ячейку
        const $totalTd = this.getTotalTdNode(fieldKey);
        if ($totalTd.length > 0) {
            let totalHtml: string;

            if (totalValue === 0) {
                totalHtml = "";
            } else {
                // Если это тип Money или MoneyWithCurrency - нужно привести к красивому виду перед записью в таблицу
                const type = this.fields[fieldKey].type;
                if (type === "Money" || type === "MoneyWithCurrency") {
                    totalHtml = getBeautyValue(totalValue, type);
                } else {
                    //Это не деньги, значит простой числовой тип (вес или объём)
                    //Округлим до того количества знаков после запятой, которое указано в параметре precision
                    let precision = this.fields[fieldKey].precision;
                    if (precision === undefined) {
                        precision = 2;
                    }
                    totalHtml = round(<number>totalValue, precision).toString();
                }
            }

            $totalTd.html(totalHtml);
        }

        //Запустим все обработчики события onCalcTotalField приязанные к этому конрктеному полю
        if (this.fieldCalcTotalHandlers[fieldKey] !== undefined) {
            const handlers = this.fieldCalcTotalHandlers[fieldKey];
            for (let i = 0; i < handlers.length; ++i) {
                handlers[i].call(this, totalValue);
            }
        }
    }

    getMaxRowNum(): number {
        const idsValue = this.idsEl.value;
        if (idsValue === "") {
            //Таблица была пуста, нет номера максимальной строки
            return -1;
        }

        return idsValue.split(",").length - 1;
    }

    /**
     * Возвращает число строк в подтаблице
     * @return {int}
     */
    getDataCount(): number {
        // Посчитаем число id строк записанных в скротом элементе ids и вычтем число строк записанных в delrows
        const idsValue = this.idsEl.value;
        if (idsValue === "") {
            //Таблица пуста
            return 0;
        }

        let rowsCount = idsValue.split(",").length;

        if (this.delRowsEl) {
            const delrowsValue = this.delRowsEl.value;
            if (delrowsValue !== "") {
                rowsCount -= delrowsValue.split(",").length;
            }
        }

        return rowsCount;
    }

    /**
     * Возвращает массив из объектов с данными
     */
    getData(): Array<RowData> {
        const data: Array<RowData> = [];
        this.eachRow((rowNum: RowNum) => {
            data.push(this.getRowData(rowNum));
        });

        return data;
    }

    /**
     * Возвращает объект с данными из указанной строки
     */
    getRowData(rowNum: RowNum): RowData {
        let rowData: RowData = {};
        //Перебираем поля и вынимаем из массива ячейки одна за одной
        for (const fieldKey in this.fields) {
            if (fieldKey !== "Del") {
                rowData[fieldKey] = this.getFieldValue(rowNum, fieldKey);
            }
        }

        return rowData;
    }

    eachRow(callback: (this: Subtable, rowNum: RowNum) => boolean | void) {
        // Перебираем все строки до максимального номера
        // При этом если порядковый номер строки есть среди delrows - значит она удалена и её использовать не нужно
        const maxRowNum = this.getMaxRowNum();
        const delrows: Array<string> = this.delRowsEl ? this.delRowsEl.value.split(",") : [];
        for (let rowNum = 0; rowNum <= maxRowNum; rowNum++) {
            if (delrows.indexOf(rowNum.toString()) !== -1) {
                // Текущая строка есть среди удалённых
                continue;
            }

            const result = callback.call(this, rowNum);
            // Если пользовательская функция вернула false - прерываем цикл
            if (result === false) {
                break;
            }
        }
    }

    addRow(data?: RowData) {
        //Номер следующей строки определим исходя из содержимого скрытого элемента, в котором пепречислены id всех строк
        let newRowNum = 0;
        const idsValue = this.idsEl.value;
        if (idsValue) {
            //Таблица была не пуста
            const ids = idsValue.split(",");
            newRowNum = ids.length;

            //Обязательно добавляем в конец скрытого поля ноль = ID элемента в базе (ведь элемент новый и его ещё в базе не существует)
            this.idsEl.value += ",0";
        } else {
            //Таблица была пуста
            //Обязательно добавляем в конец скрытого поля ноль = ID элемента в базе (ведь элемент новый и его ещё в базе не существует)
            this.idsEl.value = "0";
        }

        //Создадим новую строку
        const tr = document.createElement("tr");
        tr.id = this.name + "-subtable-tr-" + newRowNum;
        let tdsHtml = "";

        let fieldParams: any;
        for (const fieldKey in this.fields) {
            tdsHtml += '<td class="';
            fieldParams = this.fields[fieldKey];
            if (fieldParams.tableAlign) {
                tdsHtml += "ta" + fieldParams.tableAlign[0];
            }
            if (this.isColHidden(fieldKey)) {
                tdsHtml += " display-none";
            }
            tdsHtml += '">' + fieldParams.html + "</td>";
        }

        tdsHtml = tdsHtml.replace(/\{NewRowNum\}/g, newRowNum.toString());
        tr.innerHTML = tdsHtml;

        //Добавим созданную строку к таблице перед строкой total
        //Используем нативные элементы и нативный метод insertBefore для скорости (особенно актуально при использовании
        //paste большого числа строк)
        if (this.totalRowEl) {
            //Если есть total строка, то вставляем в tbody перед этой total строкой
            //https://developer.mozilla.org/ru/docs/Web/API/Node/insertBefore
            this.tbodyEl.insertBefore(tr, this.totalRowEl);
        } else {
            //Если total строки не было, то просто вставляем в конец tbody
            this.tbodyEl.appendChild(tr);
        }

        //Если передан объект data, нужно заполнить поля ввода переданными данными
        //Внутри этого метода будет произведён пересчёт total (если обновление total влючено флагом this.calcTotalEnabled)
        if (data) {
            this.setRowData(newRowNum, data);
        }

        //Сформируем jQuery объект из вставленной строки
        const $newRow = $(tr);
        addStandardListeners($newRow);

        //Если не было заблокировано пересчёт total, то нужно заодно и обновить так называемсый count-span - элемент
        //в котором отображается общее число строк.
        //(если же пресчёт total был заблокирован? скорее всего мы находимся где-то внутри
        //цикла вставки нескольких строк, поэтому нет смысла обновлять count-span после каждой вставки строки. Этот метод
        //будет вызыван внешним кодом после окончания цикла)
        if (this.calcTotalEnabled) {
            this.calcTotalAll();
            this.updateCountSpan();
        }

        //Вызовем все обработчики подписанные на событие добавление строки
        const onAddHandlersLength = this.onAddHandlers.length;
        for (let i = 0; i < onAddHandlersLength; ++i) {
            this.onAddHandlers[i].call(this, newRowNum, $newRow);
        }

        // Пересчитаем сортировку
        if (this.orderManual) {
            this.updateSort();
        }

        return { newRowNum: newRowNum, newRowNode: $newRow };
    }

    setRowData(rowNum: RowNum, data: RowData) {
        let $fieldNode, newValue, field: Field;

        for (const fieldKey in this.fields) {
            if (data[fieldKey] === undefined) {
                continue;
            }

            //Проверим, что для данного поля существует DOM элемент. Заодно $newFieldNode пригодится для проверки это select или нет
            $fieldNode = this.getFieldNode(rowNum, fieldKey);
            if ($fieldNode.length === 0) {
                continue;
            }

            field = this.fields[fieldKey];
            newValue = data[fieldKey];

            if (field.type === "MoneyWithCurrency") {
                //Для поля MoneyWithCurrency нужно дополнительно обработать newValue
                //Например, при вставке из диалогового окна paste пользователь может указать чисто сумму без валюты
                if ((<string>newValue).indexOf(" ") === -1) {
                    //Нет пробела, значит это чисто сумма - добавим вперёд валюту по умолчанию
                    newValue = "USD " + newValue;
                }
            } else {
                //Сделаем исключение для поля типа Select
                // нужно сделать поиск по всем элементам option на предмет совпадения текса
                if ($fieldNode.prop("nodeName").toLowerCase() === "select") {
                    //Проверим наличие option с таким же текстом, как value
                    if (field._optionsHash === undefined) {
                        field._optionsHash = {};
                        $fieldNode.find("option").each(function(this: HTMLOptionElement) {
                            field._optionsHash![this.innerHTML] = this.value;
                        });
                    }

                    if (field._optionsHash[newValue] !== undefined) {
                        newValue = field._optionsHash[newValue];
                    }
                }
            }

            this.setFieldValue(rowNum, fieldKey, newValue);
        }
    }

    addRows(rowsData: Array<RowData>) {
        this.disableCalcTotal();
        for (let i = 0; i < rowsData.length; i++) {
            this.addRow(rowsData[i]);
        }
        this.enableCaclTotal();
    }

    delRow(rowNum: RowNum) {
        //Найдём строку по номеру
        const $trNode = this.getRowNode(rowNum);
        if ($trNode.length === 0) {
            return false;
        }

        //Физически удалим строку из таблицы (из DOM дерева)
        $trNode.remove();

        //Добавим номер удалённой строки в скрытое поле, чтобы потом обработать на стороне php
        if (this.delRowsEl.value === "") {
            this.delRowsEl.value = rowNum.toString();
        } else {
            this.delRowsEl.value += "," + rowNum;
        }

        //Вызовем все обработчики подписанные на событие удаление строки
        for (let i = 0; i < this.onDelHandlers.length; ++i) {
            this.onDelHandlers[i].call(this, rowNum);
        }

        if (this.calcTotalEnabled) {
            this.calcTotalAll();
            this.updateCountSpan();
        }

        // Пересчитаем сортировку
        if (this.orderManual) {
            this.updateSort();
        }

        return true;
    }

    empty() {
        this.disableCalcTotal();
        this.eachRow((rowNum: RowNum) => {
            this.delRow(rowNum);
        });
        this.enableCaclTotal();
    }

    getRowNode(rowNum: RowNum): JQuery {
        return this.$tbody.find("#" + this.name + "-subtable-tr-" + rowNum);
    }

    /**
     * Если это поле в режиме view, то метод возвразает td ячейку
     * Если же поле в режиме редактирования, то возвращается input/select элемент
     */
    getFieldNode(rowNum: RowNum, fieldKey: string): JQuery {
        if (!this.fields[fieldKey]) {
            throw new Error("Subtabe " + this.name + ": uknown field (3) " + fieldKey);
        }

        const cacheKey = rowNum + "-" + fieldKey;
        if (this.fieldNodesCache[cacheKey]) {
            return this.fieldNodesCache[cacheKey];
        }

        const $tdNode = this.getTdNode(rowNum, fieldKey);
        if (this.fields[fieldKey].mode === "view") {
            //Для поля находящегося в режиме view возвращаем td ячейку
            this.fieldNodesCache[cacheKey] = $tdNode;
            return $tdNode;
        }

        const type = this.fields[fieldKey].type;
        if (type === "MoneyWithCurrency") {
            const $result = getFieldNode($tdNode, `${this.name}Subtable[${rowNum}][${fieldKey}]`, "MoneyWithCurrency");
            this.fieldNodesCache[cacheKey] = $result;
            return $result;
        }

        let $fieldNode = $tdNode.find("input");
        if ($fieldNode.length > 0) {
            this.fieldNodesCache[cacheKey] = $fieldNode;
            return $fieldNode;
        }

        $fieldNode = $tdNode.find("select");
        if ($fieldNode.length > 0) {
            this.fieldNodesCache[cacheKey] = $fieldNode;
            return $fieldNode;
        }

        $fieldNode = $tdNode.find("textarea");
        if ($fieldNode.length > 0) {
            this.fieldNodesCache[cacheKey] = $fieldNode;
            return $fieldNode;
        }

        throw new Error("Subtabe " + this.name + ": uknown field (row " + rowNum + ") " + fieldKey);
    }

    getFieldValue(rowNum: RowNum, fieldKey: string): string | number {
        if (!this.fields[fieldKey]) {
            throw new Error("Subtabe " + this.name + ": uknown field (2) " + fieldKey);
        }

        if (this.fields[fieldKey].mode === "view") {
            //Для поля находящегося в режиме View парсим из текста внутри td
            return parseTextValue(this.getTdNode(rowNum, fieldKey).text(), this.fields[fieldKey].type);
        }

        //Для режим Edit парсим из поля ввода
        return getNodeValue(this.getFieldNode(rowNum, fieldKey), this.fields[fieldKey].type);
    }

    /**
     * Возвращает массив со значениями определённого поля (колонку)
     */
    getFieldValues(fieldKey: string): Array<string | number> {
        const out: Array<string | number> = [];
        this.eachRow((rowNum: RowNum) => {
            const value = this.getFieldValue(rowNum, fieldKey);
            out.push(value);
        });

        return out;
    }

    setFieldValue(
        rowNum: RowNum,
        fieldKey: string,
        newValue: FieldValue,
        triggerChange = true,
        updateDisabled = false
    ) {
        if (this.fields[fieldKey] === undefined) {
            return;
        }

        const field = this.fields[fieldKey];

        const $node = this.getFieldNode(rowNum, fieldKey);
        if (field.mode === "view") {
            // Для поля находящегося в режиме view просто запишем значение в html код ячейки
            // При этом значение нужно обренуть в вызов функции getBeautyValue(), чтобы было красиво!
            $node.html(getBeautyValue(newValue, field.type));
        } else {
            // Для поля находящегося в режиме edit, записываем значение в поле ввода
            // но только если это поле не находится в состоянии disabled
            if (updateDisabled || !$node.prop("disabled")) {
                setNodeValue($node, newValue, field.type, triggerChange && this.calcTotalEnabled);
            }
        }

        // Обновим Total по этому полю, если у него есть флаг total и при вызове этого метода не было запрещено обновлять total
        if (this.calcTotalEnabled && field.total) {
            this.calcTotal(fieldKey);
        }
    }

    getTdNode(rowNum: RowNum, fieldKey: string): JQuery {
        const colIndex = this.fields[fieldKey].colIndex;
        return this.getRowNode(rowNum)
            .find("td")
            .eq(colIndex);
    }

    getTotalTdNode(fieldKey: string): JQuery {
        return this.getTdNode("total", fieldKey);
    }

    getDivNode(): JQuery {
        return this.$div;
    }

    showHideCol(fieldKey: string, show: boolean): void {
        //Все ячейки td и заголовчная ячейка th имеют атрибут data-fieldkey
        const colIndex = this.fields[fieldKey].colIndex;
        this.$table
            .find("tr")
            .find(`td:eq(${colIndex}), th:eq(${colIndex})`)
            .toggleClass("display-none", !show);

        this.hiddenColumns[colIndex] = !show;
    }

    hideCol(fieldKey: string): void {
        this.showHideCol(fieldKey, false);
    }

    showCol(fieldKey: string): void {
        this.showHideCol(fieldKey, true);
    }

    isColHidden(fieldKey: string): boolean {
        const colIndex = this.fields[fieldKey].colIndex;
        return this.hiddenColumns[colIndex] === true;
    }

    getName(): string {
        return this.name;
    }

    onFieldCalcTotal(fieldKey: string, handler: FieldCalcTotalHandler) {
        this.fieldCalcTotalHandlers[fieldKey] = this.fieldCalcTotalHandlers[fieldKey] || [];
        this.fieldCalcTotalHandlers[fieldKey].push(handler);
    }

    onCalcTotal(handler: CalcTotalHandler) {
        this.onCalcTotalHandlers.push(handler);
    }

    onAdd(handler: AddHandler) {
        this.onAddHandlers.push(handler);
    }

    onDel(handler: DelHandler) {
        this.onDelHandlers.push(handler);
    }

    onFieldChange(fieldKey: string, handler: FieldChangeHandler): void {
        // Сначала проверим, что это поле есть среди списка полей
        if (this.fields[fieldKey] === undefined) {
            throw new Error(`Unknown field ${fieldKey} in subtable ${this.name}`);
        }

        const changeHandler = (e: any) => {
            const rowNum = getRowNum($(e.currentTarget));
            const newValue = this.getFieldValue(rowNum, fieldKey);
            const $node = this.getFieldNode(rowNum, fieldKey);
            handler.call(this, rowNum, newValue, $node);
        };

        // Просто добавим делегат на всю таблицу
        this.$tbody.on("change keyup", `input[name$="[${fieldKey}]"]`, changeHandler);
        this.$tbody.on("change keyup", `textarea[name$="[${fieldKey}]"]`, changeHandler);
        this.$tbody.on("change", `select[name$="[${fieldKey}]"]`, changeHandler);
    }

    hasField(fieldKey: number): boolean {
        return this.fields[fieldKey] !== undefined;
    }
}

const subtablesStore: { [key: string]: Subtable } = {};

export function init($root: JQuery) {
    $root.find("div.subtable-div").each(function(this: HTMLElement) {
        // Проверим, есть ли в этом div атрибут data-fields с описанием полей? Если есть, создадим объект Subtable
        const $div = $(this);
        // Проверим, что эта таблица находится в режиме редактирования по атрибуту data-fields
        if ($div.attr("data-fields")) {
            const subtableObj = new Subtable($div);
            const name = subtableObj.getName();
            subtablesStore[name] = subtableObj;
        }
    });
}

export function getByNode($node: JQuery): Subtable {
    const subtableName = $node.closest("div.subtable-div").attr("data-subtablename");
    if (!subtableName) {
        throw new Error("Subtable not found by node: " + $node);
    }
    return get(subtableName);
}

export function get(name: string): Subtable {
    if (subtablesStore[name] === undefined) {
        throw new Error("Unknown subtable: " + name);
    }

    return subtablesStore[name];
}

export function getRowNum($node: JQuery): number {
    const id = $node.closest("tr").attr("id");
    if (!id) {
        throw new Error("No row num at Subtable row node " + $node);
    }
    const parts = id.split("-");
    if (typeof parts[3] === "undefined") {
        throw new Error("Wrong row num at Subtable row node " + $node);
    }
    return myParseInt(parts[3]);
}

export function has(name: string): boolean {
    return subtablesStore[name] !== undefined;
}

// Инициализируем подтаблицы
init($(document.body));
