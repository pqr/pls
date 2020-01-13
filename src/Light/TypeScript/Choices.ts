import {getOppositeString} from "./Light";

declare var window: any;

class ChoicesWidget {
    private select: any;
    private choices: any;
    private multiple: boolean = false;

    constructor(select: HTMLSelectElement) {
        this.select = select;
        // Внутри select поля сохраним ссылку на экземпляр этого класса, чтобы обрабатывать присвоение значения
        // из Light.setNodeValue()
        this.select.choicesWidget = this;

        const $select: JQuery = $(select);

        $select.find('option').each((_:any, option: any) => {
            option.setAttribute('data-custom-properties', getOppositeString(option.innerText));
        });


        this.multiple = this.select.hasAttribute("multiple");

        const config = this.getConfig();
        this.choices = new window.Choices(select, config);
    }

    private getConfig(): object {
        // searchResultLimit
        const config: any = {
            duplicateItemsAllowed: false,
            // shouldSort: false,
            removeItemButton: true,
            searchResultLimit: 20,
            searchFields: ['label', 'customProperties'],
        };

        return config;
    }

    public change(newValue: string) {
        this.choices.removeActiveItems();

        if (newValue.trim() === '' || newValue === '%') {
            return;
        }

        if (this.multiple) {
            newValue.split('|').map(v => v.trim()).filter(Boolean).forEach(v => {
                this.choices.setChoiceByValue(v);
            });
            return;
        }

        this.choices.setChoiceByValue(newValue);
    }
}

export function initChoices($root: JQuery) {
    if (window.Choices) {
        $root.find("select.select-choices").each((_:any, select: HTMLSelectElement) => {
            new ChoicesWidget(select);
        });
    }
}
