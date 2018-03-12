/// <reference types="jquery" />
export declare type FilterChangeHandler = (newValue: string, $node: JQuery) => void;
export declare type SelectRowHandler = (selected: boolean, $tr: JQuery) => void;
export declare type SelectAllHandler = (allSelected: boolean) => void;
export declare type ReloadHandler = () => void;
export interface ReloadParams {
    page?: number;
    showall?: string;
    sort?: string;
    order?: string;
    filterValues?: string;
    fieldsShow?: string;
}
export declare class Table {
    private $div;
    private $table;
    private $tbody;
    private name;
    private linkModule;
    private currentPage;
    private currentReloadXhr;
    private filters;
    private selectRowHandlers;
    private selectAllHandlers;
    private reloadHandlers;
    reloadUrl: string;
    constructor($div: JQuery);
    getName(): string;
    getDivNode(): JQuery;
    getTableNode(): JQuery;
    getTBodyNode(): JQuery;
    onFiltersReset(handler: () => void): Table;
    onFilterChange(fieldKey: string, handler: FilterChangeHandler): Table;
    onSelectAll(handler: SelectAllHandler): Table;
    onSelectRow(handler: SelectRowHandler): Table;
    onReload(handler: ReloadHandler): Table;
    getFilterValue(fieldKey: string): string | number;
    setFilterValue(fieldKey: string, newValue: string | number): Table;
    selectRow($tr: JQuery, selected: boolean): void;
    _getHeadCb(): JQuery;
    /**
             * Вспомагательная функция для обработчиков в таблицах - выделяет все строки в таблице или снимает выделение
             * у которых есть чекбокс с классом cb
             */
    selectAll(allSelected: boolean): void;
    reload(data?: ReloadParams, callback?: () => void): JQueryXHR;
    isLoading(): boolean;
    cancelReload(): void;
    /**
             * Возвращает массив из значений заключённых внутри чекбоксов в таблице table-tableName-table
             */
    getSelectedValues(): string[];
    getSelectedCount(): number;
    /**
     * Возвращает массив из значений заключённых внутри чекбоксов в таблице table-tableName-table
     */
    getSelectedRows(): JQuery;
    /**
     * Возвращает значение первой отмеченной радио-точки
     */
    getSelectedRadio(): string;
    updateExcelSelectedCountSpan(): void;
}
export declare function init($root: JQuery): void;
export declare function get(name: string): Table;
export declare function has(name: string): boolean;
