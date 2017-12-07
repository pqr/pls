export interface Field {
    name: string;
    type: string;
    def?: string | number;
    donotreset?: boolean;
}
export declare class Form {
    private $form;
    private name;
    private fields;
    constructor($form: JQuery);
    getFormNode(): JQuery;
    getName(): string;
    getFields(): Map<string, Field>;
    hasField(fieldKey: string): boolean;
    getField(fieldKey: string): Field | undefined;
    getFieldType(fieldKey: string): string | undefined;
    getFieldName(fieldKey: string): string | undefined;
    setFieldDefaultValue(fieldKey: string, defaultValue: string | number): void;
    getFieldNode(fieldKey: string): JQuery;
    getFieldValue(fieldKey: string): string | number | null;
    getFieldsValues(): Map<string, string | number>;
    setFieldValue(fieldKey: string, newValue: string | number): void;
    onFieldChange(fieldKey: string, handler: (newValue: string, $node: JQuery) => void): void;
    removeErrors(): void;
    highlightErrorField(fieldKey: string, hint?: string): void;
}
export declare function init($root: JQuery): void;
export declare function get(formName: string): Form;
