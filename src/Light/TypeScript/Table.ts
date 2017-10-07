namespace Light {
    export namespace tables {
        import Form = Light.forms.Form;
        import Field = Light.forms.Field;
        import SavedFilter = Light.forms.SavedFilter;
        import SavedFilterField = Light.forms.SavedFilterField;

        export type FilterChangeHandler = (newValue: string, $node: JQuery) => void;
        export type SelectRowHandler = (selected: boolean, $tr: JQuery) => void;
        export type SelectAllHandler = (allSelected: boolean) => void;
        export type ReloadHandler = () => void;

        export interface ReloadParams {
            page?: number;
            showall?: string; //'1' или '0'
            sort?: string;
            order?: string;
            filterValues?: string;
            fieldsShow?: string;
        }

        export class Table {
            private $div: JQuery;
            private $table: JQuery;
            private $tbody: JQuery;
            private name: string;
            private linkModule: string;
            private currentPage: number;
            private currentReloadXhr: JQueryXHR | null;

            private $filtersForm: JQuery;
            private filtersForm: Form;
            private filtersResetHandlers: Array<() => void> = [];
            private $filtersWidget: JQuery;
            private isShowedFiltersWidget: Boolean;
            private savedFilters: Array<SavedFilter>;
            private currentSavedFilterName: string;
            private $layer: JQuery;
            private cacheSortable: Array<any> = [];
            private cacheInvisible: { [key: string]: any } = {};
            private notEmptyFilterKeys: Array<any> = [];

            private selectRowHandlers: Array<SelectRowHandler> = [];
            private selectAllHandlers: Array<SelectAllHandler> = [];
            private reloadHandlers: Array<ReloadHandler> = [];

            public reloadUrl: string;

            constructor($div: JQuery) {
                this.$div = $div;
                this.$table = $div.find("table.table");
                this.$tbody = this.$table.children("tbody");
                this.name = $div.attr("data-tablename");
                this.reloadUrl = $div.attr("data-reloadurl");
                this.linkModule = $div.attr("data-linkmodule");

                //Попробуем найти текущую страницу из вёрстки
                //Необходимо хранить текущую страницу для метода reload() (удобно при смене table layout settings и при удалении в ModuleAbstract)
                const $strong = $div.find(".table-pages:first").find("strong");
                this.currentPage = $strong.length > 0 ? Light.parseInt($strong.text()) : 1;

                const that = this;

                //Проверим, есть ли форма фильтров поиском по глобальному идентификатору?
                this.$filtersForm = $(`#${this.name}-filters-form`);
                if (this.$filtersForm.length > 0) {
                    this.filtersResetHandlers = [];

                    //Сохраним ссылку на объект-формы во внутренней переменной
                    this.filtersForm = Light.forms.get(this.name);

                    //Обработчик на сабмит формы
                    this.$filtersForm.on("submit", function(e) {
                        e.preventDefault();
                        that._filtersSubmit();
                    });

                    //Обработчик на кнопку Enter внутри textarea -> должен вызывать submit
                    this.$filtersForm.on("keydown", "textarea", function(e) {
                        if (e.keyCode === 13) {
                            e.preventDefault();
                            that.$filtersForm.trigger("submit");
                        }
                    });

                    this.$filtersForm.find(".filters-reset").on("click", () => {
                        _.forEach(this.filtersForm.getFields(), (fieldParams: Field, fieldKey: string) => {
                            if (!fieldParams.donotreset) {
                                that.filtersForm.setFieldValue(fieldKey, fieldParams.def || "");
                            }
                        });

                        this.filtersResetHandlers.forEach(handler => handler());
                    });

                    this.$filtersForm.find(".filters-history").on("click", function(e) {
                        that.showFiltersWidget();

                        let offset = $(e.currentTarget).offset();
                        offset.top += 40;
                        that.$filtersWidget.offset(offset);

                        that.$filtersWidget.html(`
                            <div class="cssload-container">
                                <div class="cssload-speeding-wheel"></div>
                            </div>
                        `);

                        $.post(
                            "?a=Core&ps=GetHistoryFilters",
                            { TableName: that.getName() },
                            function(response) {
                                let html: string;
                                if (!_.isEmpty(response)) {
                                    html = `<ul>`;
                                    _.each(response, function(filterData) {
                                        html += `<li>`;

                                        let $a = $('<a href="#"></a>');
                                        $a.attr("data-values", JSON.stringify(filterData.Values));

                                        let aHtml = "";
                                        let filtersHtml: Array<string> = [];
                                        _.each(filterData.Values, function(dataFilter) {
                                            let fieldName = that.filtersForm.getFieldName(dataFilter.FieldKey);
                                            if (that.filtersForm.getFieldType(dataFilter.FieldKey) == "Date") {
                                                if (dataFilter.FieldKey.substr(-4) == "From") {
                                                    fieldName += " from";
                                                }
                                                if (dataFilter.FieldKey.substr(-5) == "Until") {
                                                    fieldName += " until";
                                                }
                                            }
                                            let text = dataFilter.Text;
                                            if (
                                                that.filtersForm.getFieldType(dataFilter.FieldKey) == "Select" &&
                                                text == "%"
                                            ) {
                                                text = "any";
                                            }
                                            filtersHtml.push(fieldName + `: <b>` + text + `</b>`);
                                        });
                                        aHtml += `<div class="time"><nobr>` + filterData.DateText + `:</nobr></div>`;
                                        aHtml += `<div class="text">` + filtersHtml.join(`, `) + `</div>`;

                                        $a.html(aHtml);

                                        html += $a[0].outerHTML;

                                        html += `</li>`;
                                    });
                                    html += `</ul>`;
                                } else {
                                    html = `<span class="empty">Истории фильтров не найдено</span>`;
                                }

                                that.$filtersWidget.html(html);
                            },
                            "json"
                        );
                    });

                    this.$filtersForm.find(".filters-save").on("click", function(e) {
                        that.showFiltersWidget();

                        let offset = $(e.currentTarget).offset();
                        offset.top += 40;
                        that.$filtersWidget.offset(offset);

                        that.initBodySavedFilterWidget();
                    });

                    this.$filtersForm.find(".filters-settings").on("click", function() {
                        that.hideNotEmptyInvisibleFilters();

                        that.onSettingsFilters();

                        that.$filtersForm.find("table.filters").sortable({
                            connectWith: "table.filters",
                            items: "> tbody > tr",
                            cursor: "move",
                            handle: "th:not(:empty)"
                        });

                        // кэшируем сортировку и видимость для возврата при случае "Отмены"
                        that.cacheSortable = [];
                        that.cacheInvisible = {};
                        that.$filtersForm.find("table.filters").each(function(this: HTMLFormElement) {
                            that.cacheSortable.push((<any>$(this)).sortable("toArray", { attribute: "data-sort" }));
                            $("> tbody > tr", this).each(function(this: HTMLTableRowElement) {
                                const $tr = $(this);
                                const sortValue = $tr.data("sort");
                                const flagInvisible = $tr.hasClass("filter-invisible");
                                that.cacheInvisible[sortValue] = flagInvisible ? 1 : 0;
                            });
                        });

                        // расставляем "глазки" для настройки видимости
                        that.$filtersForm
                            .find("table.filters > tbody > tr > th:not(:empty)")
                            .each(function(this: HTMLTableHeaderCellElement) {
                                const $th = $(this);
                                const flagInvisible = $th.closest("tr").hasClass("filter-invisible");
                                const dataInvisible = flagInvisible ? "1" : "0";
                                const iconClass = flagInvisible ? "fa-eye-slash" : "fa-eye";
                                const icon = `<span class="filter-hide-icon" data-invisible="${dataInvisible}"><i class="fa ${iconClass}"></i></span>`;
                                $th.prepend(icon);
                            });

                        // отображаем все скрытые фильтры
                        that.$filtersForm.find("table.filters > tbody > tr").removeClass("filter-invisible");
                    });

                    this.$filtersForm.on("click", ".filters-settings-cancel", function() {
                        that.$filtersForm
                            .find("table.filters")
                            .each(function(this: HTMLTableElement, tableIndex: number) {
                                const $table = $(this);
                                _.each(that.cacheSortable[tableIndex], function(sortValue) {
                                    const $tr = that.$filtersForm.find(
                                        'table.filters tr[data-sort="' + sortValue + '"]'
                                    );
                                    if (that.cacheInvisible[sortValue]) {
                                        $tr.addClass("filter-invisible");
                                    }
                                    $table.append($tr);
                                });
                            });

                        that.$filtersForm.find("table.filters").sortable("destroy");
                        that.$filtersForm.find(".filter-hide-icon").remove();

                        that.offSettingsFilters();

                        that.checkOnVoidInvisibleFilters();
                    });

                    this.$filtersForm.on("click", ".filters-settings-save", function() {
                        let sortData: Array<any> = [];
                        let invisibleData: { [key: string]: any } = {};
                        that.$filtersForm.find("table.filters").each(function(this: HTMLTableElement) {
                            sortData.push((<any>$(this)).sortable("toArray", { attribute: "data-sort" }));
                            $("> tbody > tr", this).each(function(this: HTMLTableRowElement) {
                                const sortValue = $(this).data("sort");
                                const flagInvisible = parseInt($(".filter-hide-icon", this).attr("data-invisible"));
                                invisibleData[sortValue] = flagInvisible ? 1 : 0;
                            });
                        });

                        that.$filtersForm
                            .find("table.filters > tbody > tr .filter-hide-icon")
                            .each(function(this: HTMLElement) {
                                const $hideIcon = $(this);
                                if (parseInt($hideIcon.attr("data-invisible"))) {
                                    $hideIcon.closest("tr").addClass("filter-invisible");
                                }
                            });

                        // сбрасываем в дефолтные значения скрытые фильтры
                        _.each(that.filtersForm.getFields(), (fieldParams: Field, fieldKey: string) => {
                            const $fieldNode = that.filtersForm.getFieldNode(fieldKey);
                            if (!$fieldNode.is(":visible")) {
                                that.filtersForm.setFieldValue(fieldKey, fieldParams.def || "");
                            }
                        });

                        that.checkOnVoidInvisibleFilters();

                        that.$filtersForm.find("table.filters").sortable("destroy");
                        that.$filtersForm.find(".filter-hide-icon").remove();

                        that.offSettingsFilters();

                        $.post("?a=Core&ps=SaveSettingsFilters",
                            {
                                TableName: that.getName(),
                                Sort: sortData,
                                Invisible: invisibleData,
                                NewSystemFlag: that.filtersForm.getNewSystemFlag()}, function () {
                            Light.toastSuccess("Настройки фильтров успешно сохранены");
                        });
                    });

                    this.$filtersForm.on("click", ".filters-settings-reset-sort", function() {
                        const defaultSort = that.filtersForm.getDefaultSort();
                        that.$filtersForm.find("table.filters").each(function(this: HTMLTableElement, tableIndex) {
                            const $table = $(this);
                            _.each(defaultSort[tableIndex], function(sortValue) {
                                $table.append(
                                    that.$filtersForm.find('table.filters tr[data-sort="' + sortValue + '"]')
                                );
                            });
                        });
                    });

                    this.$filtersForm.on("click", ".filter-hide-icon", function(this: HTMLElement) {
                        const $hideIcon = $(this);
                        const newInvisible = parseInt($hideIcon.attr("data-invisible")) === 1 ? 0 : 1;
                        $hideIcon
                            .attr("data-invisible", newInvisible)
                            .find("i")
                            .toggleClass("fa-eye-slash", newInvisible === 1)
                            .toggleClass("fa-eye", newInvisible === 0);
                    });

                    this.$filtersForm.on("click", ".filters-settings-all-visible", function() {
                        that.$filtersForm.find(".filter-hide-icon").each(function(this: HTMLElement) {
                            $(this)
                                .attr("data-invisible", "0")
                                .find("i")
                                .removeClass("fa-eye-slash")
                                .addClass("fa-eye");
                        });
                    });

                    this.$filtersForm.on("click", ".filters-settings-all-invisible", function() {
                        that.$filtersForm.find(".filter-hide-icon").each(function(this: HTMLElement) {
                            $(this)
                                .attr("data-invisible", "1")
                                .find("i")
                                .removeClass("fa-eye")
                                .addClass("fa-eye-slash");
                        });
                    });

                    this.$filtersForm.on("click", ".filters-has-active-invisible-values", function(this: HTMLElement) {
                        $(this).tooltip("destroy");
                        $(this).remove();
                        that.showNotEmptyInvisibleFilters();
                    });

                    $(document).keydown(function(e) {
                        if (that.$layer && e.keyCode === 27 && that.$layer.is(":visible")) {
                            that.$filtersForm.find(".filters-settings-cancel").trigger("click");
                        }
                    });

                    //Передадим фокус на элемент FilterName - основное поисковое поле
                    if (this.filtersForm.hasField("FilterName")) {
                        this.filtersForm.getFieldNode("FilterName").focus();
                    }

                    //Подсветим активные фильтры жирным цветом
                    this._filtersUpdateActive();

                    // Инициализация сохраненных фильтров
                    this.savedFilters = this.filtersForm.getSavedFilters();

                    // Инициализация виджета
                    this.initFiltersWidget();

                    this.checkOnVoidInvisibleFilters();
                }

                $div.on("click", ".table-pages a", function(e) {
                    e.preventDefault();

                    const tableTop = $div.offset().top;
                    //Проверим, виден ли верх таблицы при пажинации, если нет, нужно прокрутить
                    const $body = $(document.body);
                    if ($body.scrollTop() > tableTop) {
                        //window.scrollTo($body.scrollLeft(), tableTop - 10);
                        $("html,body").animate({ scrollTop: tableTop - 10 }, 500);
                    }

                    const newPage = Light.parseInt((<HTMLAnchorElement>e.currentTarget).hash.substring(1));
                    that.reload({ page: newPage });
                });

                $div.on("click", "a.table-showall-link", function(e) {
                    e.preventDefault();
                    const showall = (<HTMLAnchorElement>e.currentTarget).hash.substring(1);
                    that.reload({ showall: showall, page: 1 });
                });

                $div.on("click", "a.table-sort-link", function(e) {
                    e.preventDefault();
                    if (Light.module.getState() !== "edit" && Light.module.getState() !== "addnew") {
                        const params = (<HTMLAnchorElement>e.currentTarget).hash.substring(1).split("|");
                        that.reload({ sort: params[0], order: params[1], page: 1 });
                    }
                });

                // Переход по ссылке зашитой внутри строки таблицы при двойном клике
                $div.on("dblclick", "table.table > tbody > tr", function(this: HTMLTableRowElement) {
                    const $tr = $(this);
                    let href = $tr.attr("data-href");
                    if (!href) {
                        // Если внутри самой строки найти href не удалось, попробуем сформировать ссылку из парамтера href у всей таблицы
                        if (that.linkModule) {
                            // У всего контейнера определён параметр linkmodule, попробуем найти id у самой строки
                            const linkId = $tr.attr("data-linkid");
                            if (linkId) {
                                href = `?a=${that.linkModule}&id=${linkId}`;

                                // Попробуем проверить, есть ли дополнительные параметры для url
                                const tableLinkUrlParams = that.$table.attr("data-linkurlparams");
                                if (tableLinkUrlParams) {
                                    href += "&" + tableLinkUrlParams;
                                }

                                const rowLinkUrlParams = $tr.attr("data-linkurlparams");
                                if (rowLinkUrlParams) {
                                    href += "&" + rowLinkUrlParams;
                                }
                            }
                        }
                    }

                    // Если в итоге удалось сформировать ссылку, редиректим страницу
                    if (href) {
                        // Снимаем окраску findid внутри данной таблицы
                        that.$table.find("tr.findid").removeClass("findid");
                        // Перемещаем окраску findid на строку, по которой был совершен двойной клик
                        $tr.addClass("findid");
                        // Переходим на другую страницу
                        document.location.href = href;
                    }
                });

                //Выделение строк в таблице содержащих чекбокс с классом .cb
                $div.on("click", "table.table > tbody > tr", function(this: HTMLTableRowElement, event) {
                    //Делаем выделение строки, только если клик был совершен
                    // не по ссылке
                    // не по кнопке color
                    // и не по какому-либо элементу формы (input, textarea, select, button)
                    const $target = $(event.target);
                    if (
                        $target.closest("a").length > 0 ||
                        $target.hasClass("colorpalette") ||
                        ($target.is("input") && !$target.hasClass("cb") && !$target.hasClass("rb")) ||
                        ($target.hasClass("select2") || $target.closest(".select2").length > 0) ||
                        $target.is("textarea") ||
                        $target.is("select") ||
                        $target.is("button") ||
                        ($target.hasClass("no-select-row") || $target.closest(".no-select-row").length > 0)
                    ) {
                        return;
                    }

                    const $tr = $(this);
                    //Проверим, есть ли внутри этого tr чекбокс с классом cb?
                    const $cb = $tr.find("input[type=checkbox].cb");
                    if ($cb.length > 0) {
                        let currentSelected: boolean = $cb.prop("checked");
                        //Если клик был совершен непосредсвенно на чекбоксе, то $cb.prop("checked") возвращает статус после нажатия
                        if ($target.is($cb)) {
                            currentSelected = !currentSelected;
                        }

                        //В качестве вторго параметра (selected) передаём инвертированное значение текущего статуса
                        //т.е. если сейчас строка выбрана, то нужно снять выделение (передать вторым параметром selected = false)
                        that.selectRow($tr, !currentSelected);
                    } else {
                        //Возможно, в этой сторке есть радио кнопка?
                        const $rb = $tr.find("input[type=radio].rb");
                        if ($rb.length > 0) {
                            //Делаем выделение этой строки, но перед этим снимим выделение со всех других строк
                            $tr
                                .closest("table.table")
                                .find("tr")
                                .removeClass("selected");

                            //Выделяем строку с помощью специального метода в классе Table, чтобы запустить обработчики события
                            that.selectRow($tr, true);
                        }
                    }
                });

                //Выделение всех строк или снятие выделения при клике на чекбокс в заголовке таблицы
                $div.on("click", "table.table > thead .cb-all", function(this: HTMLInputElement) {
                    //Найдём все строки в таблице и пройдём их в цикле
                    that.selectAll(this.checked);
                });

                $div.on("click", ".table-select-all-link, .table-unselect-all-link", function(
                    this: HTMLAnchorElement,
                    e
                ) {
                    e.preventDefault();
                    const allSelected = $(this).hasClass("table-select-all-link");
                    that.selectAll(allSelected);
                });

                $(`a.check-all-link[data-tablename="${this.name}"]`).on("click", function(e) {
                    e.preventDefault();
                    that.selectAll(true);
                });

                $(`a.uncheck-all-link[data-tablename="${this.name}"]`).on("click", function(e) {
                    e.preventDefault();
                    that.selectAll(false);
                });
            }

            initFiltersWidget() {
                const that = this;

                that.isShowedFiltersWidget = false;

                that.$filtersWidget = $(`<div class='widget-block floatingwidget display-none'></div>`);
                that.$filtersWidget.appendTo("body");

                // Проверим, возможно фильтры из истории совпадают с сохраненными фильтрами
                let initFiltersData: Array<SavedFilterField> = [];

                _.each(that.filtersForm.getFields(), (fieldParams: Field, fieldKey: string) => {
                    const fieldValue = (that.filtersForm.getFieldValue(fieldKey) || "").toString();
                    if (fieldValue !== fieldParams.def) {
                        initFiltersData.push({ FieldKey: fieldKey, Value: fieldValue });
                    }
                });

                const initFiltersJson = JSON.stringify(initFiltersData);

                _.each(that.savedFilters, function(savedFiltersData: SavedFilter) {
                    if (initFiltersJson == JSON.stringify(savedFiltersData.FilterValues)) {
                        that.currentSavedFilterName = savedFiltersData.Name;
                    }
                });

                // Сохранить фильтр как RESET
                that.$filtersWidget.on("click", ".like-reset span", function(this: HTMLAnchorElement) {
                    let filtersData: Array<SavedFilterField> = [];

                    _.each(that.filtersForm.getFields(), (_field: Field, fieldKey: string) => {
                        const fieldValue = (that.filtersForm.getFieldValue(fieldKey) || "").toString();
                        filtersData.push({ FieldKey: fieldKey, Value: fieldValue });
                        that.filtersForm.setFieldDefaultValue(fieldKey, fieldValue);
                    });

                    $.post(
                        "?a=Core&ps=SaveFiltersLikeReset",
                        {
                            TableName: that.getName(),
                            FilterValues: filtersData
                        },
                        function() {
                            Light.toastSuccess(`Фильтр успешно сохранен как RESET`);
                        }
                    );

                    that.hideFiltersWidget();
                });

                // При клике на ссылку восстанавливаем значения фильтров
                // Универсально работает как в Истории фильтров, так и в Сохраненных фильтрах
                that.$filtersWidget.on("click", "a", function(this: HTMLAnchorElement, e) {
                    e.preventDefault();

                    const $a = $(this);

                    if ($a.hasClass("delete") || $a.parent().hasClass("delete")) {
                        return;
                    }

                    const filtersData = $(this).data("values");
                    let values: { [key: string]: string } = {};
                    _.each(filtersData, function(val) {
                        values[val.FieldKey] = val.Value;
                    });

                    _.each(that.filtersForm.getFields(), (fieldParams: Field, fieldKey: string) => {
                        let value = fieldParams.def || "";
                        if (_.has(values, fieldKey)) {
                            value = values[fieldKey];
                        }
                        that.filtersForm.setFieldValue(fieldKey, value);
                    });
                    that.filtersResetHandlers.forEach(handler => handler());

                    that._filtersSubmit();

                    that.hideFiltersWidget();

                    // Если это сохраненный фильтр, сохраним значение saved-filter-name в currentSavedFilterName
                    if ($a.data("saved-filter-name")) {
                        that.currentSavedFilterName = $a.data("saved-filter-name");
                    }
                });

                // Сохраняет (обновляет) фильтр
                that.$filtersWidget.on("click", ".save-filter button", function(this: HTMLButtonElement) {
                    const $button = $(this);
                    $button.prop("disabled", true);

                    // проверка на пустое название
                    const $input = $(this)
                        .closest(".save-filter")
                        .find("input");
                    const nameValue = $input.val();
                    if (nameValue == "") {
                        Light.toastError("Введите название для фильтра");
                        $button.prop("disabled", false);
                        return;
                    }

                    let filtersData: Array<SavedFilterField> = [];

                    _.each(that.filtersForm.getFields(), (fieldParams: Field, fieldKey: string) => {
                        const fieldValue = (that.filtersForm.getFieldValue(fieldKey) || "").toString();
                        if (fieldValue !== fieldParams.def) {
                            filtersData.push({ FieldKey: fieldKey, Value: fieldValue });
                        }
                    });

                    // Пройдемся по сохраненным фильтрам
                    // Найдем сперва дубликаты

                    let duplicate;

                    const filtersJson = JSON.stringify(filtersData);

                    _.each(that.savedFilters, function(savedFiltersData: SavedFilter) {
                        if (filtersJson == JSON.stringify(savedFiltersData.FilterValues)) {
                            duplicate = savedFiltersData.Name;
                        }
                    });

                    if (duplicate) {
                        Light.toastError("Такой фильтр уже существует: " + duplicate);
                        $button.prop("disabled", false);
                        return;
                    }

                    // Если найдется фильтр с таким же именем - обновим
                    // Если такого фильтра нет - сохраним новый

                    let flagNew = true;

                    _.each(that.savedFilters, function(savedFilter: SavedFilter) {
                        if (savedFilter.Name == nameValue) {
                            flagNew = false;
                            savedFilter.FilterValues = filtersData;
                            that.saveFilters(nameValue, filtersData, () => {
                                Light.toastSuccess(`Фильтр "${nameValue}" успешно обновлен`);
                                that.currentSavedFilterName = nameValue;
                                that.initBodySavedFilterWidget();
                            });
                        }
                    });

                    if (flagNew) {
                        that.savedFilters.push({ Name: nameValue, FilterValues: filtersData });
                        that.saveFilters(nameValue, filtersData, () => {
                            Light.toastSuccess(`Фильтр "${nameValue}" успешно добавлен`);
                            that.currentSavedFilterName = nameValue;
                            that.initBodySavedFilterWidget();
                        });
                    }
                });

                // Удаляет фильтр
                that.$filtersWidget.on("click", "a .delete", function(this: HTMLAnchorElement, e) {
                    e.preventDefault();

                    const filterName = $(this)
                        .closest("a")
                        .data("saved-filter-name");

                    let indexSavedFilter;

                    _.each(that.savedFilters, function(savedFilterData: SavedFilter, index) {
                        if (savedFilterData.Name == filterName) {
                            indexSavedFilter = index;
                        }
                    });

                    if (indexSavedFilter !== undefined) {
                        that.savedFilters.splice(indexSavedFilter, 1);

                        $.post(
                            "?a=Core&ps=DeleteFilters",
                            {
                                TableName: that.getName(),
                                Name: filterName
                            },
                            function() {
                                Light.toastSuccess(`Фильтр "` + filterName + `" успешно удален`);
                                that.initBodySavedFilterWidget();
                            }
                        );
                    }
                });

                $("body").on("click", function(e) {
                    if (that.isShowedFiltersWidget) {
                        if (
                            !$.contains(that.$filtersWidget.get(0), e.target) &&
                            that.$filtersWidget.get(0) != e.target
                        ) {
                            if (
                                !$(e.target).hasClass("filters-history") &&
                                !$(e.target)
                                    .parent()
                                    .hasClass("filters-history") &&
                                !$(e.target).hasClass("filters-save") &&
                                !$(e.target)
                                    .parent()
                                    .hasClass("filters-save") &&
                                !$(e.target)
                                    .parent()
                                    .hasClass("save-filter-form")
                            ) {
                                that.hideFiltersWidget();
                            }
                        }
                    }
                });
            }

            showFiltersWidget() {
                this.isShowedFiltersWidget = true;
                this.$filtersWidget.removeClass("display-none");
            }

            hideFiltersWidget() {
                this.isShowedFiltersWidget = false;
                this.$filtersWidget.addClass("display-none");
            }

            onSettingsFilters() {
                window.scrollTo(0, 0);

                const $filters = this.$filtersForm.closest(".filters");
                $filters.css("z-index", 10);

                if (!this.$layer) {
                    const $layer = $("body > .layer");
                    if ($layer.length) {
                        this.$layer = $layer;
                    } else {
                        this.$layer = $(`<div class="layer"></div>`);
                        this.$layer.appendTo("body");
                    }
                }
                this.$layer.fadeIn("fast");

                $("body").css("overflow", "hidden");

                const $buttons = this.$filtersForm.find(".filters-buttons");
                $buttons.addClass("display-none");

                const $settingsBitton = $(`<div class="filters-settings-buttons">
                                             <span class="btn waves-effect waves-light bgcolor-btn-green filters-settings-save"><i class="fa fa-save" aria-hidden="true"></i></span>       
                                             <span class="btn-flat waves-effect filters-settings-reset-sort toggle-tooltip" title="Сбросить сортировку фильтров"><i class="fa fa-random" aria-hidden="true"></i></span>
                                             <span class="btn-flat waves-effect filters-settings-all-visible toggle-tooltip" title="Сделать видимыми все фильтры"><i class="fa fa-eye" aria-hidden="true"></i></span>
                                             <span class="btn-flat waves-effect filters-settings-all-invisible toggle-tooltip" title="Сделать невидимыми все фильтры"><i class="fa fa-eye-slash" aria-hidden="true"></i></span>
                                             <span class="btn-flat waves-effect filters-settings-cancel toggle-tooltip" title="Cancel"><i class="fa fa-ban" aria-hidden="true"></i></span>
                                        </div>`);

                Light.initTooltip($settingsBitton);

                $buttons.after($settingsBitton);
            }

            offSettingsFilters() {
                const $filters = this.$filtersForm.closest(".filters");
                $filters.css("z-index", 0);

                this.$layer.fadeOut("fast");

                $("body").css("overflow", "auto");

                const $buttons = this.$filtersForm.find(".filters-buttons");
                $buttons.removeClass("display-none");

                this.$filtersForm.find(".filters-settings-buttons").remove();
            }

            initBodySavedFilterWidget() {
                const that = this;

                let html = "";

                let savedFilterValue = "";
                if (that.currentSavedFilterName) {
                    savedFilterValue = that.currentSavedFilterName;
                }

                html +=
                    `<div class="like-reset">
                                <span>Установить как RESET</span>
                            </div>
                            <div class="save-filter">
                                <div class="save-filter-text">Сохранить фильтр: </div>
                                <div class="save-filter-form">
                                    <input type="text" class="input-default" placeholder="Название" value="` +
                    savedFilterValue +
                    `">
                                    <button>Save</button>
                                </div>
                             </div>`;

                if (!_.isEmpty(that.savedFilters)) {
                    html += `<ul>`;
                    _.each(that.savedFilters, function(savedFiltersData: SavedFilter) {
                        html += `<li>`;

                        let $a = $('<a href="#"></a>');
                        $a.attr("data-values", JSON.stringify(savedFiltersData.FilterValues));
                        $a.attr("data-saved-filter-name", savedFiltersData.Name);

                        $a.html(
                            savedFiltersData.Name +
                                `<span class="delete"><i class="fa fa-trash-o" aria-hidden="true"></i></span>`
                        );

                        html += $a[0].outerHTML;

                        html += `</li>`;
                    });
                    html += `</ul>`;
                }

                that.$filtersWidget.html(html);
            }

            saveFilters(name: string, data: Array<SavedFilterField>, callback: () => void) {
                $.post(
                    "?a=Core&ps=SaveFilters",
                    {
                        TableName: this.getName(),
                        Name: name,
                        FilterValues: data
                    },
                    function() {
                        callback();
                    }
                );
            }

            checkOnVoidInvisibleFilters() {
                if (this.filtersForm.getNoSettings()) {
                    // В данной форме отключены настройки изменения положения элементов
                    return;
                }

                const that = this;
                let notEmptyFilterKeys: Array<string> = [];

                _.each(that.filtersForm.getFields(), (fieldParams: Field, fieldKey: string) => {
                    if (!fieldParams.noCheckInvisible) {
                        const $fieldNode = that.filtersForm.getFieldNode(fieldKey);
                        const fieldValue = that.filtersForm.getFieldValue(fieldKey);
                        if (
                            $fieldNode.closest("tr[data-sort]").length !== 0 &&
                            !$fieldNode.closest("tr[data-sort]").is(":visible") &&
                            fieldValue !== fieldParams.def
                        ) {
                            notEmptyFilterKeys.push(fieldKey);
                        }
                    }
                });

                if (!_.isEmpty(notEmptyFilterKeys)) {
                    that.notEmptyFilterKeys = notEmptyFilterKeys;
                    if (!that.$filtersForm.find(".filters-has-active-invisible-values").length) {
                        const $buttonNotice = $(
                            `<span class="btn-flat waves-effect toggle-tooltip filters-has-active-invisible-values" title="Есть активные, но скрытые фильтры"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></span>`
                        );
                        that.$filtersForm.find(".filters-buttons-icons").append($buttonNotice);
                        Light.initTooltip(that.$filtersForm.find(".filters-buttons-icons"));
                    }
                } else {
                    that.notEmptyFilterKeys = [];
                    const $buttonNotice = that.$filtersForm.find(".filters-has-active-invisible-values");
                    if ($buttonNotice.length) {
                        $buttonNotice.remove();
                    }
                }
            }

            showNotEmptyInvisibleFilters() {
                const that = this;
                _.each(that.notEmptyFilterKeys, function(fieldKey) {
                    const $node = that.filtersForm.getFieldNode(fieldKey);
                    const $tr = $node.closest("tr[data-sort]");
                    if ($tr.hasClass("filter-invisible")) {
                        $tr.removeClass("filter-invisible");
                        $("> th", $tr).prepend(
                            `<span class="invisible-icon"><i class="fa fa-eye-slash" aria-hidden="true"></i></span>`
                        );
                    }
                });
            }

            hideNotEmptyInvisibleFilters() {
                const that = this;
                _.each(that.notEmptyFilterKeys, function(fieldKey) {
                    const $node = that.filtersForm.getFieldNode(fieldKey);
                    const $tr = $node.closest("tr[data-sort]");
                    if (!$tr.hasClass("filter-invisible")) {
                        $tr.addClass("filter-invisible");
                        $("> th .invisible-icon", $tr).remove();
                    }
                });
            }

            getName(): string {
                return this.name;
            }

            getDivNode(): JQuery {
                return this.$div;
            }

            getTableNode(): JQuery {
                return this.$table;
            }

            getTBodyNode(): JQuery {
                return this.$tbody;
            }

            onFiltersReset(handler: () => void): Table {
                this.filtersResetHandlers.push(handler);
                return this;
            }

            onFilterChange(fieldKey: string, handler: FilterChangeHandler): Table {
                this.filtersForm.onFieldChange(fieldKey, handler);
                return this;
            }

            onSelectAll(handler: SelectAllHandler): Table {
                this.selectAllHandlers.push(handler);
                return this;
            }

            onSelectRow(handler: SelectRowHandler): Table {
                this.selectRowHandlers.push(handler);
                return this;
            }

            onReload(handler: ReloadHandler): Table {
                this.reloadHandlers.push(handler);
                return this;
            }

            _filtersSubmit(): void {
                this.hideNotEmptyInvisibleFilters();

                this._blockFitlerButton(true);
                this.$filtersForm.addClass("loading-opacity");
                const reloadParams = {
                    filterValues: JSON.stringify(this.filtersForm.getFieldsValues()),
                    page: 1
                };
                this.reload(reloadParams, () => {
                    this.$filtersForm.removeClass("loading-opacity");

                    this._blockFitlerButton(false);
                    const hiddenInput = this.$div.find("input[name=filterValues]");
                    if (hiddenInput.length > 0) {
                        const newValues = Light.parseJson(hiddenInput.val());
                        _.forEach(newValues, (newValue: string, fieldKey: string) => {
                            this.setFilterValue(fieldKey, newValue);
                        });

                        //Подсветим активные фильтры жирным и семеним иконку у кнопки Filter, если нужно
                        this._filtersUpdateActive();
                    }

                    this.checkOnVoidInvisibleFilters();
                });
            }

            _blockFitlerButton(block: boolean): void {
                //Заблокируем кнопку Filter
                this.$filtersForm.find(".btn-filter").toggleClass("disabled", block);
            }

            _filtersUpdateActive(): void {
                //Сначала уберём подсветку со всех полей
                this.$filtersForm.find(".filter-active").removeClass("filter-active");
                const that = this;
                let foundActive = false;
                _.forEach(this.filtersForm.getFields(), function(fieldParams: Field, fieldKey: string) {
                    const value = that.getFilterValue(fieldKey);
                    if (value != fieldParams.def) {
                        const $field = that.filtersForm.getFieldNode(fieldKey);
                        const $label = $field.prev("label");
                        if ($label.length > 0) {
                            $label.addClass("filter-active");
                            foundActive = true;
                        } else {
                            const $td = $field.closest("td");
                            if ($td.length > 0) {
                                const $th = $td.prev("th");
                                if ($th.length > 0) {
                                    //Значение отличается от значения по умолчанию - подсвечиваем поле
                                    $th.addClass("filter-active");
                                    foundActive = true;
                                }
                            }
                        }
                    }
                });

                //Сменим класс иконки у кнопки фильтров
                //this.$filtersForm
                //    .find(".btn-filter")
                //    .toggleClass('good bold', foundActive)
                //    .find("i")
                //    .toggleClass('sprite-filter', !foundActive)
                //    .toggleClass('sprite-filter-active', foundActive);
            }

            getFilterValue(fieldKey: string): string | number {
                return this.filtersForm.getFieldValue(fieldKey) || "";
            }

            setFilterValue(fieldKey: string, newValue: string | number): Table {
                this.filtersForm.setFieldValue(fieldKey, newValue);
                return this;
            }

            setFilterDefaultValue(fieldKey: string, defaultValue: string): Table {
                this.filtersForm.setFieldDefaultValue(fieldKey, defaultValue);
                return this;
            }

            selectRow($tr: JQuery, selected: boolean): void {
                //Инвертируем выделение строки специальным классом
                $tr.toggleClass("selected", selected);

                //Отмечаем чекбокс или радио-кнопку
                $tr.find("input[type=checkbox].cb, input[type=radio].rb").prop("checked", selected);

                this.selectRowHandlers.forEach((handler: (selected: boolean, $tr: JQuery) => void) => {
                    handler(selected, $tr);
                });

                //После выделения текущей строки, проверим, есть ли чекбокс в заголовке таблицы, нужно ли его переключить?
                const $headCb = this._getHeadCb();
                if ($headCb.length > 0) {
                    //Если есть хотя бы один неотмеченный чекбокс среди строк таблицы - снимаем выделение с headCb
                    const allSelected = this.$tbody.find("input[type=checkbox].cb:not(:checked)").length === 0;
                    $headCb.prop("checked", allSelected);
                }
            }

            _getHeadCb(): JQuery {
                return this.$table.children("thead").find(".cb-all");
            }

            /**
             * Вспомагательная функция для обработчиков в таблицах - выделяет все строки в таблице или снимает выделение
             * у которых есть чекбокс с классом cb
             */
            selectAll(allSelected: boolean): void {
                this.$tbody.find("tr").each(function(this: HTMLTableRowElement) {
                    const $tr = $(this);
                    const $cb = $tr.find("input[type=checkbox].cb");
                    if ($cb.length > 0) {
                        //Выделим строку специальным классом
                        $tr.toggleClass("selected", allSelected);
                        //Выделим чекбокс
                        $cb.prop("checked", allSelected);
                    }
                });

                this._getHeadCb().prop("checked", allSelected);
                this.selectAllHandlers.forEach((handler: SelectAllHandler) => {
                    handler(allSelected);
                });
            }

            reload(data: ReloadParams = {}, callback?: () => void): JQueryXHR {
                this._showProgressbar();
                const newPage = Light.parseInt(data.page);
                if (newPage > 0) {
                    //При данном вызове метода realod меняется страница - сохраним в currentPage
                    this.currentPage = newPage;
                } else {
                    //Если же страница не была передана, то нужно взять из currentPage
                    data.page = this.currentPage;
                }

                //Сделаем затемнение таблицы
                this.$div.addClass("loading-opacity");

                // Выполним ajax запрос к серверу, чтобы обновить таблицу
                // Если уже есть какой-то запрос на обновление этой таблицы, то его нужно прервать
                if (this.currentReloadXhr) {
                    // Если уже есть какой-то ajax запрос в процессе, то его нужно убить
                    this.currentReloadXhr.abort();
                }

                // Запоминаем ссылку на текущий запрос обновления этой таблицы
                this.currentReloadXhr = $.post(this.reloadUrl, data);

                this.currentReloadXhr
                    .done((newTableHtml:string) => {
                        this.$div.html(newTableHtml);
                        //Обновим керующие переменные
                        this.$table = this.$div.find("table.table");
                        this.$tbody = this.$table.find("tbody");
                        //Заново наложим sticky заголовки на таблицу (если только у неё нет специального класса .nosticky)
                        if (!this.$table.hasClass("nosticky")) {
                            const offset = this.$table.attr("data-stickyoffset") || 0;
                            this.$table.stickyTableHeaders({ fixedOffset: offset });
                        }
                    })
                    .always(() => {
                        this.currentReloadXhr = null;

                        this.$div.removeClass("loading-opacity");

                        if (callback) {
                            callback();
                        }

                        this.reloadHandlers.forEach(handler => {
                            handler();
                        });
                    });

                // Возвращаем JQueryXHR
                return this.currentReloadXhr;
            }

            _showProgressbar() {
                this.$div.find(".table-progressbar").css("visibility", "visible");
            }

            /**
             * Возвращает массив из значений заключённых внутри чекбоксов в таблице table-tableName-table
             */
            getSelectedValues(): string[] {
                return this.$tbody
                    .find("input[type=checkbox].cb:checked")
                    .toArray()
                    .map((el: HTMLInputElement) => el.value);
            }

            /**
             * Возвращает массив из значений заключённых внутри чекбоксов в таблице table-tableName-table
             */
            getSelectedRows(): JQuery {
                return this.$tbody.find("input[type=checkbox].cb:checked").closest("tr");
            }

            /**
             * Возвращает значение первой отмеченной радио-точки
             */
            getSelectedRadio(): string {
                return this.$tbody.find("input[type=radio].rb:checked").val();
            }
        }

        const tablesStore: { [key: string]: Table } = {};

        export function init($root: JQuery) {
            $root.find("div.table-div").each(function(this: HTMLDivElement) {
                const $div = $(this);
                const tableObj = new Table($div);
                const name = tableObj.getName();
                tablesStore[name] = tableObj;
            });
        }

        export function get(name: string): Table {
            if (tablesStore[name] === undefined) {
                throw Error("Unknown table: " + name);
            }

            return tablesStore[name];
        }

        export function has(name: string): boolean {
            return tablesStore[name] !== undefined;
        }

        //Инициализируем таблицы
        init($(document.body));
    }
}
