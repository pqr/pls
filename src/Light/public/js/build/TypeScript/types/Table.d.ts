/// <reference types="jquery" />
import { FieldsWidth } from "./TableLayoutDialog";
export declare type FilterChangeHandler = (newValue: string, $node: JQuery) => void;
export declare type SelectRowHandler = (selected: boolean, $tr: JQuery) => void;
export declare type SelectAllHandler = (allSelected: boolean) => void;
export declare type ReloadHandler = (table: Table) => void;
export declare type ResetHandler = (table: Table) => void;
export interface ReloadParams {
    page?: number;
    showall?: string;
    sort?: string;
    order?: string;
    filterValues?: string;
    fieldsShow?: string;
    fieldsWidth?: FieldsWidth;
}
export declare class Table {
    private $div;
    private $table;
    private $tbody;
    private name;
    private linkModule;
    private linkBlank;
    private linkModuleDialog;
    private currentPage;
    private currentReloadXhr;
    private filters;
    private selectRowHandlers;
    private selectAllHandlers;
    private reloadHandlers;
    reloadUrl: string;
    constructor($div: JQuery);
    private filtersSubmitAction;
    private filtersResetAction;
    getName(): string;
    getDivNode(): JQuery;
    getTableNode(): JQuery;
    getTBodyNode(): JQuery;
    onFiltersReset(handler: () => void): Table;
    onFilterChange(fieldKey: string, handler: FilterChangeHandler): Table;
    filtersSubmit(): void;
    onSelectAll(handler: SelectAllHandler): Table;
    onSelectRow(handler: SelectRowHandler): Table;
    onReload(handler: ReloadHandler): Table;
    getFilterValue(fieldKey: string): string | number;
    setFilterValue(fieldKey: string, newValue: string | number): Table;
    private callSelectRowHandlers;
    selectRow($tr: JQuery, selected: boolean): void;
    updateHeadCheckbox(): void;
    private getHeadCheckbox;
    /**
     * Вспомагательная функция для обработчиков в таблицах - выделяет все строки в таблице или снимает выделение
     * у которых есть чекбокс с классом cb
     */
    selectAll(selected: boolean): void;
    reload(reloadParams?: ReloadParams, callback?: () => void): JQueryXHR;
    reloadRow($tr: JQuery): JQueryXHR | undefined;
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
    /**
     * Обновляет div в котором находятся выпадающий список с massaction
     * Обновление происходит во время прокрутки таблицы или при выделении строк таблицы
     */
    updateMassactionDiv(): void;
    getSelectedTotalMoneyWithCurrencyHtml(colIndex: number): string | null;
    getSelectedTotalMoneyHtml(colIndex: number): string | null;
    getSelectedTotalHtml(colIndex: number): string | null;
    calcSelectedTotal(colIndex: number): number;
    initMassactionListeners(): void;
}
export declare function init($root: JQuery): void;
export declare function get(name: string): Table;
export declare function has(name: string): boolean;
export declare function findClosestTable($node: JQuery): Table | null;
export declare function onReload(tableName: string, onReloadHandler: ReloadHandler): void;
export declare function onFiltersReset(tableName: string, onResetHandler: ResetHandler): void;
