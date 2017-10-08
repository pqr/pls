module Light.forms {

    export interface Field {
        name: string
        type: string
        def?: string|number
        donotreset?: boolean
        noCheckInvisible: boolean
    }
    export interface SavedFilterField {
        FieldKey: FieldKey
        Value: string
    }
    export interface SavedFilter {
        Name: string
        FilterValues: Array<SavedFilterField>
    }

    export type FieldKey = string;
    export type Fields = {[key:string] : Field};

    export class Form {

        private $form: JQuery;
        private name: string;
        private fields: Fields;
        private savedFilters: SavedFilter[];
        private defaultSort = [];
        private noSettings = false;
        private newSystemFlag:boolean = false;

        constructor($form: JQuery) {
            this.$form = $form;
            this.name = $form.attr('name') || '';
            this.fields = Light.parseJson($form.attr("data-fields")!);
            this.savedFilters = Light.parseJson($form.attr("data-saved")!, []);
            this.defaultSort = Light.parseJson($form.attr("data-default-sort")!, []);
            this.noSettings = Light.parseJson($form.attr("data-nosettings")!);
            this.newSystemFlag = Light.parseJson($form.attr("data-newsystemflag")!);

            //Сохраняем созданный объект внутрь самой ноды с помощью jQuery
            $form.data('formObj', this);
        }

        getFormNode(): JQuery {
            return this.$form;
        }

        getName(): string {
            return this.name;
        }

        getSavedFilters(): Array<SavedFilter> {
            return this.savedFilters;
        }

        getFields(): Fields {
            return this.fields;
        }

        getDefaultSort() {
            return this.defaultSort;
        }

        getNoSettings() {
            return this.noSettings;
        }

        getNewSystemFlag() {
            return this.newSystemFlag;
        }

        hasField(fieldKey: string): boolean {
            return this.fields[fieldKey] !== undefined;
        }

        getFieldType(fieldKey: string): string | undefined {
            return this.fields[fieldKey] ? this.fields[fieldKey].type : undefined;
        }

        getFieldName(fieldKey: string): string | undefined {
            return this.fields[fieldKey] ? this.fields[fieldKey].name : undefined;
        }

        setFieldDefaultValue(fieldKey: string, defaultValue: string|number): void {
            if (this.fields[fieldKey]) {
                this.fields[fieldKey].def = defaultValue;
            }
        }

        getFieldNode(fieldKey: string): JQuery {
            return Light.getFieldNode(this.$form, fieldKey, this.getFieldType(fieldKey));
        }

        getFieldValue(fieldKey: string): string | number | null {
            return Light.getFieldValue(this.$form, fieldKey, this.getFieldType(fieldKey));
        }

        getFieldsValues(): {[key:string]: any} {

            let result:{[key:string]: any} = {};

            for (let fieldKey in this.fields) {
                const value = this.getFieldValue(fieldKey);
                if (value !== null) {
                    result[fieldKey] = value;
                }
            }

            return result;

        }

        setFieldValue(fieldKey: string, newValue: string|number): void {

            const type = this.getFieldType(fieldKey);
            if ((type === 'Int' && Light.parseInt(newValue) === 0)
                || (type === 'Float' && Light.parseFloat(newValue) === 0)) {
                newValue = '';
            }

            Light.setFieldValue(this.$form, fieldKey, newValue, type);

        }

        onFieldChange(fieldKey: string, handler: (newValue: string, $node: JQuery) => void): void {
            //Сначала првоерим, что это поле есть среди списка полей
            const $node = this.getFieldNode(fieldKey);
            const type = this.getFieldType(fieldKey);
            Light.onNodeChange($node, handler, type);
        }

        removeErrors(): void {
            this.$form.find(".input-error").removeClass("input-error");
        }

        highlightErrorField(fieldKey: string, hint?: string): void {
            const $node = this.getFieldNode(fieldKey).addClass("input-error");
            if (hint) {
                $node.prop("title", hint)
            }
        }
    }

    let formsStore:{[key:string]: Form} = {};

    export function init($root: JQuery) {
        $root.find("form").each(function (this:HTMLFormElement) {
            const $form = $(this);
            const formObj = new Form($form);
            const name = formObj.getName();
            formsStore[name] = formObj;
        });
    }

    export function get(formName:string): Form {
        if (!formsStore[formName]) {
            throw Error('Unknown form: ' + formName);
        }
        return formsStore[formName];
    }

    //Инициализируем все формы на странице
    init($(document.body));

}

