export interface Field {
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
export declare type Fields = {
    [key: string]: Field;
};
export declare type FieldValue = string | number;
export declare type RowNum = string | number;
export declare type RowData = {
    [key: string]: string | number;
};
export declare type FieldCalcTotalHandler = (this: Subtable, total: number) => void;
export declare type AddHandler = (this: Subtable, newRowNum: RowNum, $newRow: JQuery) => void;
export declare type DelHandler = (this: Subtable, rowNum: RowNum) => void;
export declare type CalcTotalHandler = (subtable: Subtable) => void;
export declare type FieldChangeHandler = (rowNum: RowNum, newValue: FieldValue, $node: JQuery) => void;
export declare class Subtable {
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
        newRowNode: JQuery<HTMLElement>;
    };
    setRowData(rowNum: RowNum, data: RowData): void;
    addRows(rowsData: Array<RowData>): void;
    delRow(rowNum: RowNum): boolean;
    empty(): void;
    getRowNode(rowNum: RowNum): JQuery;
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
export declare function init($root: JQuery): void;
export declare function getByNode($node: JQuery): Subtable;
export declare function get(name: string): Subtable;
export declare function getRowNum($node: JQuery): number;
export declare function has(name: string): boolean;
