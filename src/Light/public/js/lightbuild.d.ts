/// <reference types="jquery" />
declare const angular: any;
declare const _lightCsrfToken: string;
declare const _lightModule: string;
declare const _lightPageParams: any;
declare const nativeParseInt: typeof parseInt;
declare const nativeParseFloat: typeof parseFloat;
declare namespace Light {
    const listener: any;
    function getLang(): any;
    const isLangRu: boolean;
    function initSelect2($root: JQuery): void;
    function initTooltip($root: JQuery): void;
    function showFlashMessagess(messagess: Array<{
        type: string;
        msg: string;
    }>): void;
    function showToastMessagess(messagess: Array<{
        type: string;
        msg: string;
    }>): void;
    function toast(type: string, msg: string): void;
    function toastInfo(msg: string): void;
    function toastSuccess(msg: string): void;
    function toastWarning(msg: string): void;
    function toastError(msg: string): void;
    function setUserParam(name: string, value: string, successCallback?: (confirmedValue: string) => void): void;
    function getUserParam(name: string, successCallback: (value: string) => void): void;
    function getModule(): string;
    /**
     * Возвращает указанный параметр, который опередлён в json сгенерированном на стороне сервера в глобальном объекте window
     */
    function getPageParam(paramName: string): any;
    function blockButtons(selector: string | JQuery | HTMLElement, block: boolean): void;
    function parseJson(stringJson: string, returnOnError?: any): any;
    /**
     * Округляет до двух знаков полсе запятой
     */
    function round(value: number | string, precision?: number): number;
    /**
     * Отличие от стандартного метода parseInt - если не удалось распарсить, этот метод возвращает 0
     */
    function parseInt(value: any): number;
    /**
     * Отличие от стандартного метода parseFloat - если не удалось распарсить, этот метод возвращает 0
     */
    function parseFloat(value: any): number;
    function getBeautyMoney(num: any, short?: boolean, currencyIso?: string, zeroEmpty?: boolean): string;
    function getBeautyMoneyShort(num: any, currencyIso?: string): string;
    function getBeautyDate(date: any, fullyear?: boolean): string;
    /**
     * Принимает объект типа Date или строку DD.MM.YYYY и возвращает строку в формате MySQL 'YYYY-MM-DD';
     */
    function getMySQLDate(date: any): string;
    function nl2br(string: any): string;
    function htmlspecialchars(html: string): string;
    function getFieldValue($root: JQuery, fieldName: string, type?: string): string | number | null;
    function getFieldNode($root: JQuery, fieldName: string, type?: string): JQuery;
    function getNodeValue($node: JQuery, type?: string): string | number;
    function parseTextValue(text: string, type: string): string | number;
    function setFieldValue($root: JQuery, fieldName: string, newValue: any, type?: string): void;
    function setNodeValue($node: JQuery, newValue: any, type?: string, triggerChange?: boolean): void;
    function onNodeChange($node: JQuery, handler: (newValue: string | number, $node: JQuery) => void, type?: string): void;
    function getBeautyValue(value: any, type: string): string;
    function getMoneyWithCurrencyAsTouple(value: string): [string, number];
    function postMetric(actionType: string, timeMs: number, payload: string | Object): void;
    function iconExcel(): string;
    function iconFolderFull(): string;
    function addStandardListeners($root: JQuery): void;
}
declare namespace Light {
}
declare module Light.forms {
    interface Field {
        name: string;
        type: string;
        def?: string | number;
        donotreset?: boolean;
        noCheckInvisible: boolean;
    }
    interface SavedFilterField {
        FieldKey: FieldKey;
        Value: string;
    }
    interface SavedFilter {
        Name: string;
        FilterValues: Array<SavedFilterField>;
    }
    type FieldKey = string;
    type Fields = {
        [key: string]: Field;
    };
    class Form {
        private $form;
        private name;
        private fields;
        private savedFilters;
        private defaultSort;
        private noSettings;
        private newSystemFlag;
        constructor($form: JQuery);
        getFormNode(): JQuery;
        getName(): string;
        getSavedFilters(): Array<SavedFilter>;
        getFields(): Fields;
        getDefaultSort(): never[];
        getNoSettings(): boolean;
        getNewSystemFlag(): boolean;
        hasField(fieldKey: string): boolean;
        getFieldType(fieldKey: string): string | undefined;
        getFieldName(fieldKey: string): string | undefined;
        setFieldDefaultValue(fieldKey: string, defaultValue: string | number): void;
        getFieldNode(fieldKey: string): JQuery;
        getFieldValue(fieldKey: string): string | number | null;
        getFieldsValues(): {
            [key: string]: any;
        };
        setFieldValue(fieldKey: string, newValue: string | number): void;
        onFieldChange(fieldKey: string, handler: (newValue: string, $node: JQuery) => void): void;
        removeErrors(): void;
        highlightErrorField(fieldKey: string, hint?: string): void;
    }
    function init($root: JQuery): void;
    function get(formName: string): Form;
}
declare module Light.MonthWidget {
}
declare module Light.colopalette {
    type ChooseCallback = (newColor: string) => void;
    function showPalette($targetBtn: JQuery, onChooseCallback?: ChooseCallback): void;
}
declare module Light.currency {
    function getRate(from: string, to?: string): number;
    function convertValue(value: string | number, from: string, to?: string): number;
}
declare module Light {
    interface Button {
        id?: string;
        text: string;
        isPrimary?: boolean;
        className?: string;
        handler: (dialog: Dialog, buttonId: string) => void;
    }
    interface DialogOptions {
        buttonOkText?: string;
        buttonOkHandler?: (dialog: Dialog) => void;
        buttonCancelText?: string;
        buttons?: Array<Button>;
        width?: string;
        height?: string;
        top?: string;
        afterClose?: () => void;
    }
    class Dialog {
        $div: JQuery | null;
        $divContent: JQuery | null;
        private top;
        private afterClose;
        private destroyAfterClose;
        private $overlay;
        private defaultSubmitHandler;
        constructor(content: any, options?: DialogOptions, destroyAfterClose?: boolean);
        open(): void;
        positiionCenter(): void;
        close(): void;
        blockButtons(): void;
        unblockButtons(): void;
    }
}
declare namespace Light {
    namespace tables {
        import SavedFilterField = Light.forms.SavedFilterField;
        type FilterChangeHandler = (newValue: string, $node: JQuery) => void;
        type SelectRowHandler = (selected: boolean, $tr: JQuery) => void;
        type SelectAllHandler = (allSelected: boolean) => void;
        type ReloadHandler = () => void;
        interface ReloadParams {
            page?: number;
            showall?: string;
            sort?: string;
            order?: string;
            filterValues?: string;
            fieldsShow?: string;
        }
        class Table {
            private $div;
            private $table;
            private $tbody;
            private name;
            private linkModule;
            private currentPage;
            private currentReloadXhr;
            private $filtersForm;
            private filtersForm;
            private filtersResetHandlers;
            private $filtersWidget;
            private isShowedFiltersWidget;
            private savedFilters;
            private currentSavedFilterName;
            private $layer;
            private cacheSortable;
            private cacheInvisible;
            private notEmptyFilterKeys;
            private selectRowHandlers;
            private selectAllHandlers;
            private reloadHandlers;
            reloadUrl: string;
            constructor($div: JQuery);
            initFiltersWidget(): void;
            showFiltersWidget(): void;
            hideFiltersWidget(): void;
            onSettingsFilters(): void;
            offSettingsFilters(): void;
            initBodySavedFilterWidget(): void;
            saveFilters(name: string, data: Array<SavedFilterField>, callback: () => void): void;
            checkOnVoidInvisibleFilters(): void;
            showNotEmptyInvisibleFilters(): void;
            hideNotEmptyInvisibleFilters(): void;
            getName(): string;
            getDivNode(): JQuery;
            getTableNode(): JQuery;
            getTBodyNode(): JQuery;
            onFiltersReset(handler: () => void): Table;
            onFilterChange(fieldKey: string, handler: FilterChangeHandler): Table;
            onSelectAll(handler: SelectAllHandler): Table;
            onSelectRow(handler: SelectRowHandler): Table;
            onReload(handler: ReloadHandler): Table;
            _filtersSubmit(): void;
            _blockFitlerButton(block: boolean): void;
            _filtersUpdateActive(): void;
            getFilterValue(fieldKey: string): string | number;
            setFilterValue(fieldKey: string, newValue: string | number): Table;
            setFilterDefaultValue(fieldKey: string, defaultValue: string): Table;
            selectRow($tr: JQuery, selected: boolean): void;
            _getHeadCb(): JQuery;
            /**
             * Вспомагательная функция для обработчиков в таблицах - выделяет все строки в таблице или снимает выделение
             * у которых есть чекбокс с классом cb
             */
            selectAll(allSelected: boolean): void;
            reload(data?: ReloadParams, callback?: () => void): JQueryXHR;
            _showProgressbar(): void;
            /**
             * Возвращает массив из значений заключённых внутри чекбоксов в таблице table-tableName-table
             */
            getSelectedValues(): string[];
            /**
             * Возвращает массив из значений заключённых внутри чекбоксов в таблице table-tableName-table
             */
            getSelectedRows(): JQuery;
            /**
             * Возвращает значение первой отмеченной радио-точки
             */
            getSelectedRadio(): string;
        }
        function init($root: JQuery): void;
        function get(name: string): Table;
        function has(name: string): boolean;
    }
}
declare module Light.tables {
    interface Layout {
        name: string;
        fieldKeys: Array<string>;
    }
    interface Field {
        key: string;
        name: string;
        title: string;
    }
    type DialogSubmitCallback = (shownFieldKeys: Array<string>) => void;
    /**
     * Этот метод специально сделан экспортируемым, чтобы можно было отображать диалоговое окно выбора полей
     * не только по стандартной ссылке "Table layout settings", но и в SPA приложении
     */
    function showLayoutDialog(tableName: string, allFields: Array<Field>, initialShownFieldKeys: Array<string>, layouts: Array<Layout>, userLayoutsHtml: string, dialogSubmitCallback: DialogSubmitCallback): void;
}
declare module Light {
    module subtables {
        interface Field {
            mode: string;
            type: string;
            calcTotal: boolean;
            copyOnAdd: boolean;
            colIndex: number;
            disablePaste: boolean;
            tableAlign: string;
            precision?: number;
            _optionsHash?: {
                [key: string]: string;
            };
        }
        type Fields = {
            [key: string]: Field;
        };
        type FieldValue = string | number;
        type RowNum = string | number;
        type RowData = {
            [key: string]: string | number;
        };
        type FieldCalcTotalHandler = (this: Subtable, total: number) => void;
        type AddHandler = (this: Subtable, newRowNum: RowNum, $newRow: JQuery) => void;
        type DelHandler = (this: Subtable, rowNum: RowNum) => void;
        type CalcTotalHandler = (subtable: Subtable) => void;
        type FieldChangeHandler = (rowNum: RowNum, newValue: FieldValue, $node: JQuery) => void;
        class Subtable {
            private name;
            private $div;
            private fields;
            private $table;
            private $thead;
            private $tbody;
            private $countSpan;
            private tbodyEl;
            private totalRowEl;
            private idsEl;
            private delRowsEl;
            private fieldKeys;
            private hiddenColumns;
            private copyOnAddFieldKeys;
            private fieldCalcTotalHandlers;
            private onAddHandlers;
            private onDelHandlers;
            private onCalcTotalHandlers;
            private fieldNodesCache;
            private calcTotalEnabled;
            private rowsCount;
            constructor($div: JQuery);
            disableCalcTotal(): void;
            enableCaclTotal(): void;
            private showPasteDialog();
            /**
             * Пользователь может указать в вёрстке специальный тег (или несколько) с классом <subtableName>-count-span и тогда
             * при каждом добавлении или удалении строки в этот span будет записываться текущее число строк
             */
            private updateCountSpan();
            calcTotalAll(): void;
            getTotalValue(fieldKey: string): any;
            calcTotal(fieldKey: string): void;
            getMaxRowNum(): number;
            /**
             * Возвращает число строк в подтаблице
             * @return {int}
             */
            getDataCount(): number;
            /**
             * Возвращает массив из объектов с данными
             */
            getData(): Array<RowData>;
            /**
             * Возвращает объект с данными из указанной строки
             */
            getRowData(rowNum: RowNum): RowData;
            eachRow(callback: (this: Subtable, rowNum: RowNum) => boolean | void): void;
            addRow(data?: RowData): {
                newRowNum: number;
                newRowNode: JQuery;
            };
            setRowData(rowNum: RowNum, data: RowData): void;
            addRows(rowsData: Array<RowData>): void;
            delRow(rowNum: RowNum): boolean;
            empty(): void;
            getRowNode(rowNum: RowNum): JQuery;
            /**
             * @deprecated
             * @param $node
             * @returns {number}
             */
            static getRowNum($node: JQuery): number;
            /**
             * Если это поле в режиме view, то метод возвразает td ячейку
             * Если же поле в режиме редактирования, то возвращается input/select элемент
             */
            getFieldNode(rowNum: RowNum, fieldKey: string): JQuery;
            getFieldValue(rowNum: RowNum, fieldKey: string): string | number;
            /**
             * Возвращает массив со значениями определённого поля (колонку)
             */
            getFieldValues(fieldKey: string): Array<string | number>;
            setFieldValue(rowNum: RowNum, fieldKey: string, newValue: FieldValue, triggerChange?: boolean): void;
            getTdNode(rowNum: RowNum, fieldKey: string): JQuery;
            getTotalTdNode(fieldKey: string): JQuery;
            getDivNode(): JQuery;
            showHideCol(fieldKey: string, show: boolean): void;
            hideCol(fieldKey: string): void;
            showCol(fieldKey: string): void;
            isColHidden(fieldKey: string): boolean;
            getName(): string;
            onFieldCalcTotal(fieldKey: string, handler: FieldCalcTotalHandler): void;
            onCalcTotal(handler: CalcTotalHandler): void;
            onAdd(handler: AddHandler): void;
            onDel(handler: DelHandler): void;
            onFieldChange(fieldKey: string, handler: FieldChangeHandler): void;
            hasField(fieldKey: number): boolean;
        }
        function init($root: JQuery): void;
        function getByNode($node: JQuery): Subtable;
        function get(name: string): Subtable;
        function getRowNum($node: JQuery): number;
        function has(name: string): boolean;
    }
}
declare namespace Light {
    namespace module {
        type ModuleState = "list" | "addnew" | "view" | "edit";
        /**
         * Эта функция может вызываться из validationHandler. Например, VInvoice диалоговое окно
         */
        function save(saveanyway: boolean, additionalData?: Object): void;
        function getState(): ModuleState;
        function getObjectId(): number;
        function getModuleSessionParams(): string;
        function getFieldNode(fieldKey: string): JQuery;
        function getFieldValue(fieldKey: string): any;
        function setFieldValue(fieldKey: string, newValue: any): void;
        function onFieldChange(fieldKey: string, handler: (newValue: any, $input: JQuery) => void): void;
        function highlightErrorField(fieldKey: string): void;
        function ajaxChangeField(id: number, fieldKey: string, newValue: string): JQueryXHR;
        function setValidationHandler(handler: () => boolean): void;
    }
}
