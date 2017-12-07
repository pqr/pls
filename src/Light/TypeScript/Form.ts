import {
    buildMapFromObj,
    getFieldNode,
    getFieldValue,
    myParseFloat,
    myParseInt,
    onNodeChange,
    parseJson,
    setFieldValue
} from "./Light";

export interface Field {
    name: string;
    type: string;
    def?: string | number;
    donotreset?: boolean;
}

export class Form {
    private $form: JQuery;
    private name: string;
    private fields: Map<string, Field>;

    constructor($form: JQuery) {
        this.$form = $form;
        this.name = $form.attr("name") || "";
        this.fields = buildMapFromObj(parseJson($form.attr("data-fields")!, {}));

        //Сохраняем созданный объект внутрь самой ноды с помощью jQuery
        $form.data("formObj", this);
    }

    getFormNode(): JQuery {
        return this.$form;
    }

    getName(): string {
        return this.name;
    }

    getFields(): Map<string, Field> {
        return this.fields;
    }

    hasField(fieldKey: string): boolean {
        return this.fields.has(fieldKey);
    }

    getField(fieldKey: string): Field | undefined {
        return this.fields.get(fieldKey);
    }

    getFieldType(fieldKey: string): string | undefined {
        return this.fields.has(fieldKey) ? this.fields.get(fieldKey)!.type : undefined;
    }

    getFieldName(fieldKey: string): string | undefined {
        return this.fields.has(fieldKey) ? this.fields.get(fieldKey)!.name : undefined;
    }

    setFieldDefaultValue(fieldKey: string, defaultValue: string | number): void {
        if (this.fields.has(fieldKey)) {
            this.fields.get(fieldKey)!.def = defaultValue;
        }
    }

    getFieldNode(fieldKey: string): JQuery {
        return getFieldNode(this.$form, fieldKey, this.getFieldType(fieldKey));
    }

    getFieldValue(fieldKey: string): string | number | null {
        return getFieldValue(this.$form, fieldKey, this.getFieldType(fieldKey));
    }

    getFieldsValues(): Map<string, string | number> {
        const result: Map<string, string | number> = new Map();

        this.fields.forEach((_, fieldKey) => {
            const value = this.getFieldValue(fieldKey);
            if (value !== null) {
                result.set(fieldKey, value);
            }
        });

        return result;
    }

    setFieldValue(fieldKey: string, newValue: string | number): void {
        const type = this.getFieldType(fieldKey);
        if ((type === "Int" && myParseInt(newValue) === 0) || (type === "Float" && myParseFloat(newValue) === 0)) {
            newValue = "";
        }

        setFieldValue(this.$form, fieldKey, newValue, type);
    }

    onFieldChange(fieldKey: string, handler: (newValue: string, $node: JQuery) => void): void {
        //Сначала првоерим, что это поле есть среди списка полей
        const $node = this.getFieldNode(fieldKey);
        const type = this.getFieldType(fieldKey);
        onNodeChange($node, handler, type);
    }

    removeErrors(): void {
        this.$form.find(".input-error").removeClass("input-error");
    }

    highlightErrorField(fieldKey: string, hint?: string): void {
        const $node = this.getFieldNode(fieldKey).addClass("input-error");
        if (hint) {
            $node.prop("title", hint);
        }
    }
}

let formsStore: { [key: string]: Form } = {};

export function init($root: JQuery) {
    $root.find("form").each(function(this: HTMLFormElement) {
        const $form = $(this);
        const formObj = new Form($form);
        const name = formObj.getName();
        formsStore[name] = formObj;
    });
}

export function get(formName: string): Form {
    if (!formsStore[formName]) {
        throw Error("Unknown form: " + formName);
    }
    return formsStore[formName];
}

//Инициализируем все формы на странице
init($(document.body));
