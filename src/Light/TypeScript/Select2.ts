import {getFieldNode, getNodeValue} from "./Light";

class Select2 {
    private select: HTMLSelectElement & {select2widget?: Select2};
    private $select: JQuery;
    private $root: JQuery;
    private parentFields: JQuery[] | null = null;
    private currentXhr: JQueryXHR | null = null;
    private readonly multiple: boolean = false;

    constructor(select: HTMLSelectElement, $root: JQuery) {
        this.select = select;
        // Внутри select поля сохраним ссылку на экземпляр этого класса, чтобы обрабатывать присвоение значения
        // из Light.setNodeValue()
        this.select.select2widget = this;

        this.$select = $(select);

        this.$root = this.$select.closest("form") || $root;

        this.multiple = this.select.hasAttribute("multiple");

        // Инициализируем внутреннюю переменную this.parentFields
        // пригодится для правильного формирования опций select2
        this.initParentFields();

        const options = this.getSelect2Options();

        this.$select.select2(options);
    }

    private initParentFields() {
        const parentFieldKeysStr = this.select.getAttribute("data-parantfieldkeys");
        if (!parentFieldKeysStr) {
            return;
        }

        // Преобразуем строку содержащую ключи родительских полей через запятую в массив конкретных элементов
        // Поиск будем производить внутри ближайшей формы или $root контейнера, внутри которого происходит initSelect2
        const parentFields: JQuery[] = <JQuery[]>parentFieldKeysStr
            .split(",")
            .map((parentFieldKey: string) => {
                return getFieldNode(this.$root, parentFieldKey, "Select");
            })
            // уберём все не найденные элементы
            .filter(($parentField: JQuery) => {
                return $parentField.length === 1;
            });

        if (parentFields.length === 0) {
            return;
        }

        if (!this.multiple && !this.select.closest("form.filters-form")) {
            // Также на каждый Parent Field навесим обработчик onChange - чтобы при изменении родительского поля
            // автоматически подставлять в дочернее (текущее) поле едиснветнно возможный вариант
            // (если этот вариан действительно единственно возможный).
            // А если вариантов несколько, то сбросим на any или empty, если текущий child выбор не присутствует среди возможных
            parentFields.forEach(($parentField: JQuery) => {
                // почему-то есил делать parentField.addEventListener('change', onParentFieldChange);
                // то не срабатывает. Возможно, из-за того, что parent зачастую сам select2
                $parentField.on("change", (e: any) => {
                    const newParentValue = e.target.value;
                    if (newParentValue !== "" && newParentValue !== "%") {
                        this.onParentFieldChange();
                    }
                });
            });
        }

        this.parentFields = parentFields;
    }

    /**
     * При изменении одного из родительских полей нужно поменять текущее значение дочернего (т.е. текущего select2)
     * Если среди возможных вариантов дочернего select2 всего один  - именно его и выберем!
     * Если же среди возможных вариантов дочернего select2 несколько - то провалидирует текущий выбранный. Если текущий
     * выбранные не присутсвует в возможных вариантах, то сбрасываем на any или empty
     */
    private onParentFieldChange() {
        // Отправим специальный ajax запрос на сервер, чтобы проверить, удовлетворяет ли текущее child поле
        // выбранным parent значениям. Тут может быть три исхода:
        // - текущее выбранное child занчение удовлетворяет, никаких изменений тогда не произойдёт
        // - текущее child значение не удовлетворяет, тогда оно будет сброшено на пустую опцию
        // - текущее child значение уже является пустой опцией, но среди возможных вариантов есть всего один,
        // тогда именно его и выберем (пример: у Customer всего один Customer Account)
        const currentChildValue = this.select.value;
        const url = this.getUrl() + "&updateCurrentChildValue=" + encodeURIComponent(currentChildValue);
        if (this.currentXhr) {
            this.currentXhr.abort();
        }
        this.currentXhr = $.ajax(url)
            .always(() => {
                this.currentXhr = null;
            })
            .done((data: any) => {
                const action = data.action;
                if (action === "reset") {
                    this.$select.val("").trigger("change");
                    return;
                }

                if (action === "change") {
                    // create the option and append to Select2
                    const option = new Option(data.text, data.id, true, true);
                    this.$select.append(option).trigger("change");
                    return;
                }
            });
    }

    private getUrl(): string {
        let url: string | null = null;
        const filtersForm = <HTMLFormElement | null>this.select.closest("form.filters-form");
        if (filtersForm) {
            // Это поле находится внутри формы фильтров, значит для запроса на сервер будем использовать reloadUrl
            // который хранится в таблице, которая относится к этим фильтрам
            const tableName = filtersForm.name;
            const tableDiv = document.querySelector(`div[data-tablename="${tableName}"]`);
            if (tableDiv) {
                url = tableDiv.getAttribute("data-reloadurl");
                if (url) {
                    url += "&fromFilters=" + encodeURIComponent(tableName);
                }
            }
        }

        if (!url) {
            // Похоже, мы не внутри фильтров (или из фильтров не удалось определить url)
            // Попробуем найти ближайший узел с атрибутом data-select2ajaxurl
            const container = <HTMLFormElement | null>this.select.closest("*[data-select2ajaxurl]");
            if (container) {
                url = container.getAttribute("data-select2ajaxurl");
            }
        }

        if (!url) {
            // Если ничего не получилось, используем адрес текущей страницы
            url = window.location.href;
            if (!url.includes("?")) {
                url += "?";
            }
        }

        url += "&select2ajax=" + encodeURIComponent(this.select.name) + "&nouserrefreshtime=1";

        if (this.parentFields) {
            // http://api.jquery.com/jquery.param/
            url += "&" + $.param({parentFieldValues: this.getParentFieldsValues()});
        }

        return url;
    }

    private getParentFieldsValues(): object | null {
        if (!this.parentFields) {
            return null;
        }

        return this.parentFields.reduce((acc: any, $parentField: JQuery) => {
            // У родительских полей принудительно убираем квардратные скобки из атрибута name, на случае если это
            // было поле multiple select
            const parentFieldKey = $parentField.attr("name")!.replace("[]", "");
            acc[parentFieldKey] = getNodeValue($parentField, "Select");
            return acc;
        }, {});
    }

    private getSelect2Options(): object {
        const options: any = {
            width: "resolve",
            templateResult: formatResultSelect2,
            templateSelection: formatResultSelect2
        };

        if (this.select.hasAttribute("multiple")) {
            options["placeholder"] = "";
            options["allowClear"] = true;
        }

        if (this.select.hasAttribute("data-select2-closeOnSelect")) {
            options["closeOnSelect"] = this.select.getAttribute("data-select2-closeOnSelect") === '1';
        }

        if (this.select.hasAttribute("data-minimum-input-length")) {
            options["minimumInputLength"] = this.select.getAttribute("data-minimum-input-length");
        }

        if (this.select.classList.contains("select2-ajax")) {
            options["ajax"] = {
                cache: true,
                delay: 250,
                url: (params: any) => {
                    let url = this.getUrl();
                    if (params.term) {
                        url += "&term=" + encodeURIComponent(params.term);
                    }
                    return url;
                }
            };
        }

        return options;
    }

    /**
     * Этот обработчик срабатывает только на select2ajax полях
     * @param newValue
     */
    public change(newValue: any) {
        if (this.multiple && newValue === "%") {
            this.$select.val("").trigger("change");
            return;
        }

        let newValues: any[];
        if (this.multiple) {
            newValues = newValue.split("|").filter((n: string) => n !== "");
        } else {
            newValues = [newValue];
        }

        this.ensureOptionsAvailable(newValues).then((availableNewValues: any[]) => {
            if (availableNewValues.length === 0) {
                if (this.multiple) {
                    this.$select.val("").trigger("change");
                }
                return;
            }

            if (this.multiple) {
                this.$select.val(availableNewValues).trigger("change");
                return;
            }

            this.$select.val(availableNewValues[0]).trigger("change");
        });
    }

    private ensureOptionsAvailable(values: any[]) {
        return new Promise((resolve, reject) => {
            // Сначала узнаем, какие из опций реально доступны в вёрстке на данный момент?
            const availableValues: any[] = values.filter(val => this.select.querySelector(`option[value="${val}"]`));

            if (!this.select.classList.contains("select2-ajax")) {
                // Если это не ajax поле, то мы ничего не загружаем с сервера, просто проверяем среди текущих опций
                // Резолвим промис с реально доступными опциями
                resolve(availableValues);
                return;
            }

            // Если же это было ajax поле, то проверим, а совпадает ли число доступных опций с запрощенными?
            if (values.length === availableValues.length) {
                // Совпадает, значит все запрошенные опции реально доступны
                resolve(availableValues);
                return;
            }

            // Если кол-во не совпадает, сделаем ajax запрос на сервер, чтобы подтянуть недостающие
            const requestValues: any[] = values.filter(val => !availableValues.includes(val));

            const url = this.getUrl() + "&loadOptionIds=" + encodeURIComponent(requestValues.join(","));
            $.ajax(url)
                .done((data: any) => {
                    if (!data.results) {
                        reject();
                    }

                    // Добавим загруженные опции в вёрстку
                    data.results.forEach((r: any) => {
                        // create the option and append to Select2
                        const option = new Option(r.text, r.id, false, false);
                        this.$select.append(option);
                        availableValues.push(r.id);
                    });

                    // После этого резолвим промис
                    resolve(availableValues);
                })
                .fail(() => {
                    reject();
                });
        });
    }
}

function escapeHtml(text: string) {
    const map: { [key: string]: string } = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;"
    };

    return text.replace(/[&<>"']/g, function (m) {
        return map[m];
    });
}

function formatResultSelect2(option: any) {
    if (option.loading) {
        return;
    }

    const originalOption = $(option.element);

    let icon = originalOption.data("icon");
    const img = originalOption.data("img");
    let cssClass = originalOption.attr("class") || '';
    if (!icon && !cssClass && !img) {
        return option.text;
    }

    const title = option.title;
    const text = option.text;

    let out = "";

    if (title || cssClass || img) {
        out = '<span';
        if (title) {
            cssClass += ' toggle-tooltip';
        }
        if (img) {
            cssClass += ' select2-option-with-avatar';
        }
        if (cssClass) {
            out += ` class="${cssClass}"`;
        }
        if (title) {
            out += ` title="${escapeHtml(title)}"`;
        }
        out += '>';
    }

    if (icon !== undefined) {
        if (icon.indexOf("fa-") !== 0) {
            icon = "fa-" + icon;
        }
        out += '<i class="fa ' + icon + '"></i> ';
    }

    if (img) {
        out += `<img src="${img}" class="select2-option-avatar"/> `;
    }

    out += escapeHtml(text);

    if (title || cssClass || img) {
        out += "</span>";
    }

    return $(out);
}

export function initSelect2($root: JQuery) {
    if (jQuery().select2) {
        $root.find("select.select-select2").each(function (this: HTMLSelectElement) {
            const select = this;
            new Select2(select, $root);
        });

        $root.find("select.select-select2multiple").select2({
            width: "resolve",
            templateResult: formatResultSelect2,
            templateSelection: formatResultSelect2,
            placeholder: "",
            allowClear: true
        });
    }
}
