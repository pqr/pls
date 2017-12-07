export interface Layout {
    name: string;
    fieldKeys: string[];
}
export interface Field {
    key: string;
    name: string;
    title: string;
}
export declare type DialogSubmitCallback = (shownFieldKeys: Array<string>) => void;
/**
     * Этот метод специально сделан экспортируемым, чтобы можно было отображать диалоговое окно выбора полей
     * не только по стандартной ссылке "Table layout settings", но и в SPA приложении
     */
export declare function showLayoutDialog(tableName: string, allFields: Array<Field>, initialShownFieldKeys: Array<string>, layouts: Array<Layout>, userLayoutsHtml: string, dialogSubmitCallback: DialogSubmitCallback): void;
