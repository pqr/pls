export interface Layout {
    name: string;
    fieldKeys: string[];
}
export interface Field {
    key: string;
    name: string;
    title: string;
    width: string;
}
export declare type FieldsWidth = {
    [key: string]: string;
};
export declare type DialogSubmitCallback = (shownFieldKeys: string[], fieldsWidth?: FieldsWidth) => void;
/**
 * Этот метод специально сделан экспортируемым, чтобы можно было отображать диалоговое окно выбора полей
 * не только по стандартной ссылке "Table layout settings", но и в SPA приложении
 */
export declare function showLayoutDialog(tableName: string, allFields: Field[], initialShownFieldKeys: string[], layouts: Layout[], bodyHtml: string, dialogSubmitCallback: DialogSubmitCallback, showFieldsWidth?: boolean): void;
