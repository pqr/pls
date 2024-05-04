export declare const listener: any;
export declare function getLang(): any;
export declare const isLangRu: boolean;
export declare function loadSortableJs(): Promise<unknown>;
export declare function initTooltip($root: JQuery): void;
export declare function showFlashMessagess(messagess: Array<{
    type: string;
    msg: string;
}>): void;
export declare function showToastMessagess(messagess: Array<{
    type: string;
    msg: string;
}>): void;
export declare function toast(type: string, msg: string): void;
export declare function toastInfo(msg: string): void;
export declare function toastSuccess(msg: string): void;
export declare function toastWarning(msg: string): void;
export declare function toastError(msg: string): void;
export declare function initInputMask($root: JQuery): void;
export declare function setUserParam(name: string, value: string, successCallback?: (confirmedValue: string) => void): void;
export declare function getUserParam(name: string, successCallback: (value: string) => void): void;
export declare function getModule(): any;
/**
 * Возвращает указанный параметр, который опередлён в json сгенерированном на стороне сервера в глобальном объекте window
 */
export declare function getPageParam(paramName: string): any;
export declare function blockButtons(selector: any, block: boolean): void;
export declare function parseJson(stringJson: string, returnOnError?: any): any;
export declare function buildMapFromObj(obj: {
    [key: string]: any;
}): Map<string, any>;
export declare function buildObjFromMap(m: Map<string, any>): {
    [key: string]: any;
};
/**
 * Округляет до двух знаков полсе запятой
 */
export declare function round(value: number | string, precision?: number): number;
/**
 * Отличие от стандартного метода myParseInt - если не удалось распарсить, этот метод возвращает 0
 */
export declare function myParseInt(value: any): number;
/**
 * Отличие от стандартного метода myParseFloat - если не удалось распарсить, этот метод возвращает 0
 */
export declare function myParseFloat(value: any): number;
export declare function getBeautyMoney(num: any, short?: boolean, currencyIso?: string, zeroEmpty?: boolean): string;
export declare function getBeautyMoneyShort(num: any, currencyIso?: string): string;
export declare function getBeautyDate(date: any, fullyear?: boolean): string;
/**
 * Принимает объект типа Date или строку DD.MM.YYYY и возвращает строку в формате MySQL 'YYYY-MM-DD';
 */
export declare function getMySQLDate(date: any): string;
export declare function nl2br(string: any): string;
export declare function htmlspecialchars(html: string): string;
export declare function stripTags(html: string): string;
export declare function getFieldValue($root: JQuery, fieldName: string, type?: string): string | number | null;
export declare function getFieldNode($root: JQuery, fieldName: string, type?: string): JQuery;
export declare function getNodeValue($node: JQuery, type?: string): string | number;
export declare function parseTextValue(text: string, type: string): string | number;
export declare function setFieldValue($root: JQuery, fieldName: string, newValue: any, type?: string): void;
export declare function setNodeValue($node: JQuery, newValue: any, type?: string, triggerChange?: boolean): void;
export declare function onNodeChange($node: JQuery, handler: (newValue: string | number, $node: JQuery) => void, type?: string): void;
export declare function getBeautyValue(value: any, type: string): string;
export declare function getMoneyWithCurrencyAsTouple(value: string): [string, number];
export declare function postMetric(actionType: string, timeMs: number, payload: string | Object): void;
export declare function iconExcel(): string;
export declare function iconFolderFull(): string;
export declare function calcTotalPerCurrency(values: string[]): Map<string, number>;
export declare function ajaxGetCurrRate(date: string, currencyFrom: string, currencyTo: string, callback: (currRate: any) => void): void;
export declare function getOppositeString(str: string): string;
export declare function matchWithOpposite(source: string | undefined, searchString: string | undefined): boolean;
export declare function addStandardListeners($root: JQuery): void;
export declare function getVatAmount(amount: number, vatPercent: number): number;
