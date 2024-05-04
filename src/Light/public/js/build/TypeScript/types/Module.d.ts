export declare type ModuleState = "list" | "addnew" | "view" | "edit";
/**
 * Эта функция может вызываться из validationHandler. Например, VInvoice диалоговое окно
 */
export declare function save(saveanyway: boolean, additionalData?: Object): void;
export declare function getState(): ModuleState;
export declare function isView(): boolean;
export declare function isAddNewOrEdit(): boolean;
export declare function getObjectId(): number;
export declare function getModuleSessionParams(): string;
export declare function getFieldNode(fieldKey: string): JQuery;
export declare function getFieldValue(fieldKey: string): any;
export declare function setFieldValue(fieldKey: string, newValue: any): void;
export declare function onFieldChange(fieldKey: string, handler: (newValue: any, $input: JQuery) => void): void;
export declare function highlightErrorField(fieldKey: string): void;
export declare function ajaxChangeField(id: number, fieldKey: string, newValue: string): any;
export declare function setValidationHandler(handler: () => boolean): void;
