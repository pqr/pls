import { buildMapFromObj, buildObjFromMap, initTooltip, isLangRu, parseJson, toastError } from "./Light";
import * as forms from "./Form";
import { Field, Form } from "./Form";
import { Table } from "./Table";

export class Filters {
    private name: string;

    public form: Form;
    private $form: JQuery;
    private table: Table;
    private $layer:JQuery;

    // Описание схемы
    private noSettings: boolean;

    private $widget: JQuery | undefined;

    public resetHandlers: (() => void)[] = [];

    private layoutForCancel: string[][];
    private invisibleForCancel: Set<string>;

    constructor(table: Table) {
        this.name = table.getName();
        this.table = table;
        this.form = forms.get(this.name);
        this.$form = this.form.getFormNode();

        const $form = this.$form;

        // Передадим фокус на элемент FilterName - основное поисковое поле
        if (this.form.hasField("FilterName")) {
            this.form.getFieldNode("FilterName").focus();
        }

        // Подсветим активные фильтры жирным цветом
        this.highlightActive();

        // Навесим различные обработчики на форму фильтров
        $form.on("submit", this.onSubmit);
        //Обработчик на кнопку Enter внутри textarea -> должен вызывать submit
        $form.on("keydown", "textarea", this.onTextareaKeydown);
        $form.find(".filters-reset").on("click", this.onResetClick);
        $form.find(".filters-history").on("click", this.onHistoryClick);
        $form.find(".filters-save").on("click", this.onSaveClick);

        this.noSettings = $form.attr("data-nosettings") === "1";
        if (!this.noSettings) {
            $form.find(".filters-layout").on("click", this.onShowLayoutClick);
            $form.on("click", ".filters-layout-cancel", this.onLayoutCancelClick);
            $form.on("click", ".filters-layout-save", this.onLayoutSaveClick);
            $form.on("click", ".filters-layout-reset-sort", this.onLayoutResetClick);
            $form.on("click", ".filter-hide-icon", this.onHideIconClick);
            $form.on("click", ".filters-layout-allvisible", this.onAllVisibleClick);
            $form.on("click", ".filters-layout-allinvisible", this.onAllInvisibleClick);

            $(document).keydown((e: any) => {
                if (this.$layer && e.keyCode === 27 && this.$layer.is(":visible")) {
                    $form.find(".filters-layout-cancel").trigger("click");
                }
            });
        }
    }

    getValue(fieldKey: string) {
        return this.form.getFieldValue(fieldKey) || "";
    }

    getValues(): Map<string, string | number> {
        return this.form.getFieldsValues();
    }

    setValue(fieldKey:string, newValue:string|number) {
        this.form.setFieldValue(fieldKey, newValue);
    }

    private renderHtmlIntoWidget(html:string) {
        if (this.$widget) {
            this.$widget.html(html);
        }
    }

    private highlightActive() {
        //Сначала уберём подсветку со всех полей
        this.$form.find(".filter-active").removeClass("filter-active");
        this.form.getFields().forEach((_:any, fieldKey: string) => {
            if (!this.isDefaultValue(fieldKey)) {
                const $field = this.form.getFieldNode(fieldKey);
                const $label = $field.prev("label");
                if ($label.length > 0) {
                    $label.addClass("filter-active");
                } else {
                    const $td = $field.closest("td");
                    if ($td.length > 0) {
                        const $th = $td.prev("th");
                        if ($th.length > 0) {
                            //Значение отличается от значения по умолчанию - подсвечиваем поле
                            $th.addClass("filter-active");
                        }
                    }
                }
            }
        });
    }

    private onSubmit = (e: any) => {
        e.preventDefault();
        this.submit();
    };

    private onTextareaKeydown = (e: any) => {
        if (e.keyCode === 13) {
            e.preventDefault();
            this.submit();
        }
    };

    private reset() {
        this.form.getFields().forEach((field:Field, fieldKey:string) => {
            if (!field.donotreset) {
                this.form.setFieldValue(fieldKey, field.def || "");
            }
        });
    }

    private onResetClick = () => {
        this.reset();
        this.resetHandlers.forEach(handler => handler());
    };

    private submit() {
        const $form = this.$form;
        $form.find(".btn-filter").prop("disabled", true);
        $form.addClass("loading-opacity");
        const reloadParams = {
            filterValues: JSON.stringify(buildObjFromMap(this.form.getFieldsValues())),
            page: 1
        };
        this.table.reload(reloadParams, () => {
            this.$form.removeClass("loading-opacity");
            $form.find(".btn-filter").prop("disabled", false);
            const hiddenInput = this.table.getDivNode().find("input[name=filterValues]");
            if (hiddenInput.length > 0) {
                const newValues = parseJson(<string>hiddenInput.val());
                for (let fieldKey in newValues) {
                    this.setValue(fieldKey, newValues[fieldKey]);
                }

                //Подсветим активные фильтры жирным и семеним иконку у кнопки Filter, если нужно
                this.highlightActive();

                // Запустим механизм скрытия скрытых фильтров заново
                // это нужно чтобы отобразить те фильтры, у которых значение отличается от значения по умолчанию
                this.refreshInvisibleClasses();
            }
        });
    }

    private showWidget(clickTarget: HTMLElement): JQuery {
        const $widget = $(`<div class='widget-block floatingwidget'>
                                    <div class="cssload-container">
                                        <div class="cssload-speeding-wheel"></div>
                                    </div>
                                 </div>`);

        $widget.appendTo("body");

        // Сохранить фильтр как RESET
        $widget.on("click", ".like-reset span", this.onWidgetLikeResetClick);

        // При клике на ссылку восстанавливаем значения фильтров
        // Универсально работает как в Истории фильтров, так и в Сохраненных фильтрах
        $widget.on("click", "a[data-filtersjson]", this.onWidgetSelectFiltersClick);

        // Сохраняет (обновляет) фильтр
        $widget.on("click", ".save-filter button", this.onWidgetSaveCurrentClick);

        // Удаляет фильтр
        $widget.on("click", ".del-link", this.onWidgetDeleteSavedClick);


        let offset = $(clickTarget).offset()!;
        offset.top += 40;
        $widget.offset(offset);

        // Сохраняем в своействе класса, чтобы потом можно было сделать hide (удалив элемет из dom)
        this.$widget = $widget;

        setTimeout(() => {
            $("body").on("click", this.onBodyClickTryHideWidget);
        }, 0);

        return $widget;
    }

    private hideWidget() {
        if (this.$widget) {
            this.$widget.remove();
            this.$widget = undefined;

            $("body").off("click", this.onBodyClickTryHideWidget);
        }
    }

    private onWidgetLikeResetClick = (e: any) => {
        e.preventDefault();

        const currentValues = this.getValues();
        // Устанавливаем новое default значение для текущей формы
        currentValues.forEach((currentValue: string | number, fieldKey: string) => {
            this.form.setFieldDefaultValue(fieldKey, currentValue);
        });

        // Отправляем запрос на сервер
        $.post("?a=Core&ps=FiltersSaveLikeReset&TableName=" + encodeURIComponent(this.name), {
            Defaults: buildObjFromMap(currentValues)
        });

        this.hideWidget();
    };

    private onWidgetSelectFiltersClick = (e: any) => {
        e.preventDefault();

        const $a = $(e.currentTarget);

        // Сначала сбрасываем все значения в значения по умолчанию
        this.form.getFields().forEach((field, fieldKey) => {
            this.form.setFieldValue(fieldKey, field.def || "");
        });

        // Затем выставляем в соответсвие с сохранёнными
        const savedValues: Map<string, string | number> = buildMapFromObj(parseJson($a.attr("data-filtersjson")!, {}));
        savedValues.forEach((value, fieldKey) => {
            this.form.setFieldValue(fieldKey, value);
        });

        this.resetHandlers.forEach(handler => handler());

        this.submit();

        this.hideWidget();

    };

    private onWidgetSaveCurrentClick = (e: any) => {
        e.preventDefault();

        const buttonEl = e.currentTarget;
        let newName = <string>$(buttonEl)
            .closest(".save-filter")
            .find("input")
            .val();

        newName = newName.trim();

        if (newName == "") {
            toastError("Введите название для фильтра");
            return;
        }

        buttonEl.disabled = true;

        $.post("?a=Core&ps=FiltersSave&TableName=" + encodeURIComponent(this.name), {
            Name: newName,
            Filters: buildObjFromMap(this.getValues())
        }).done((savedHtml:string) => {
            this.renderHtmlIntoWidget(savedHtml);
            this.$widget!.find("input.input-default").val(newName);
        });
    };

    private onWidgetDeleteSavedClick = (e: any) => {
        e.preventDefault();

        const toBeDeletedName = $(e.currentTarget)
            .prev("a")
            .text();

        $.post("?a=Core&ps=FiltersDelete&TableName=" + encodeURIComponent(this.name), {
            Name: toBeDeletedName
        }).done((savedHtml:string) => {
            // Если успешно удалили то от сервера приходит обновлённый список всех сохранённых настроек
            this.renderHtmlIntoWidget(savedHtml);
        });
    };

    private onHistoryClick = (e: any) => {
        e.preventDefault();

        this.showWidget(e.currentTarget);

        $.ajax("?a=Core&ps=FiltersGetHistoryHtml&TableName=" + encodeURIComponent(this.name)).done((responseHtml:string) => {
            this.renderHtmlIntoWidget(responseHtml);
        });
    };

    private onSaveClick = (e: any) => {
        e.preventDefault();
        this.showWidget(e.currentTarget);
        $.ajax("?a=Core&ps=FiltersGetSavedHtml&TableName=" + encodeURIComponent(this.name)).done((savedHtml:string) => {
            this.renderHtmlIntoWidget(savedHtml);
        });
    };

    /**
     * Собирает текущий layout на основе вёрстки
     * Применяется в начальный момент времени, когда пользователь только нажал кнопку настроек, чтобы сохранить в this.layoutForCancel
     * и при нажатии кнопки "применить настройки", когда нужно отправить на сервер все настройки сделанные пользователем
     */
    private getLayoutFromMarkup():string[][]
    {
        const layoutFromMarkup: string[][] = [];
        this.$form.find("table.filters").each(function(this: HTMLTableElement, tableIndex: number) {
            const $table = $(this);
            layoutFromMarkup[tableIndex] = [];
            $table.find("tr[data-templatekey]").each(function(this: HTMLTableRowElement) {
                const $tr = $(this);
                const templateKey = <string>$tr.attr('data-templatekey')!;
                layoutFromMarkup[tableIndex].push(templateKey);
            });
        });

        return layoutFromMarkup;
    }

    /**
     * Возвращает Set из ключей templatekey у котоырх в вёрстке стоит класс filter-invisible
     * @returns {string[]}
     */
    private getInvisibleFromMakrup(): Set<string>
    {
        const invisibleFromMarkup = new Set<string>();
        this.$form.find("tr[data-templatekey].filter-invisible").each(function(this:HTMLTableRowElement) {
            invisibleFromMarkup.add($(this).attr('data-templatekey')!);
        });

        return invisibleFromMarkup;
    }

    /**
     * Отображает иконки с глазками
     * Вызывается при начале работы в режиме настроек
     */
    private showEyeIcons() {
        this.$form.find("tr[data-templatekey]").each(function(this:HTMLTableRowElement) {
            const $tr = $(this);
            const invisible = $tr.hasClass('filter-invisible');
            const iconClass = invisible ? "fa-eye-slash" : "fa-eye";
            const icon = `<i class="filter-hide-icon fa ${iconClass}"></i>`;
            $tr.find('th').prepend(icon);
        });
    }

    private onShowLayoutClick = (e: any) => {
        e.preventDefault();

        this.showLayout();

        const $form = this.$form;

        // Запоминаем текущую сортировку и видимость для возврата при случае "Отмены"
        this.layoutForCancel = this.getLayoutFromMarkup();
        this.invisibleForCancel = this.getInvisibleFromMakrup();

        // Расставляем "глазки" в каждый th
        this.showEyeIcons();

        // Отображаем все скрытые фильтры
        $form.find(".filter-invisible").removeClass("filter-invisible");

        $form.find("table.filters").sortable({
            connectWith: "table.filters",
            items: "> tbody > tr",
            cursor: "move",
            handle: "th:not(:empty)"
        });
    };

    onLayoutCancelClick = (e: any) => {
        e.preventDefault();

        const $form = this.$form;

        // Отключаем виджет сортировки
        $form.find("table.filters").sortable("destroy");
        // Убираем все иконки-глазки
        $form.find(".filter-hide-icon").remove();

        // Возвращаем положение всех строк в начальное (которое было предварительно сохранено в this.layoutForCancel)
        this.applyLayout(this.layoutForCancel);
        // Возвращаем классы filter-invisible на те строки, которые были сохранены в this.invisibleForCancel
        this.addInvisibleClasses(this.invisibleForCancel);

        // Убираем панель с кнопками для режима редактирования settings
        this.hideLayout();
    };

    onLayoutSaveClick = (e: any) => {
        e.preventDefault();

        const $form = this.$form;

        const newLayout: string[][] = this.getLayoutFromMarkup();
        const newInvisible = new Set<string>();

        // Соберём набор полей которые пользователь отметил иконкой "скрыть" (перечёркнутый глаз)
        $form.find(".fa-eye-slash").each(function(this: HTMLElement) {
            const $tr = $(this).closest('tr[data-templatekey]');
            if ($tr.length === 1) {
                newInvisible.add($tr.attr('data-templatekey')!);
            }
        });

        // Отправляем новые настройки на сервер
        $.post("?a=Core&ps=FiltersSaveLayout&TableName=" + encodeURIComponent(this.name), {
            Layout: newLayout,
            Invisible: Array.from(newInvisible)
        });

        // Отключаем jQuery плагин sortable
        $form.find("table.filters").sortable("destroy");

        // Физически скрываем все отмеченные перечёкнутым глазом фильтроы
        this.addInvisibleClasses(newInvisible);

        // Убираем все иконки с глазами
        $form.find(".filter-hide-icon").remove();

        // Скрываем форму настроек
        this.hideLayout();
    };

    private onLayoutResetClick = (e: any) => {
        e.preventDefault();
        // Когда пользователь нажал кнопку "сбросить layout по умолчанию", на лету парсим атрибут формы data-defaultlayout
        const defaultLayout = parseJson(this.$form.attr("data-defaultlayout")!, []);
        // И применяем layout занный в атрибует формы как layout по умолчанию
        this.applyLayout(defaultLayout);
    };

    /**
     * Перемещает элементы в вёрстке в соответсве с переданным layout
     * Вызывается в двух случаях: когда пользователь что-то поменял в расположении фильтров и затем нажал Cancel
     * либо когда пользователь нажал "сбросить положение фильтров по умолчанию"
     * @param {string[][]} layout
     */
    private applyLayout(layout:string[][]) {
        const $form = this.$form;

        // Перебирем колонки, которые уже есть в вёрстке (table.filters)
        $form.find("table.filters").each(function(this: HTMLTableElement, tableIndex: number) {
            const $table = $(this);
            // Внутри каждой колонки применяем полученный порядок полей пришедший в параметре layout
            if (layout[tableIndex] !== undefined) {
                layout[tableIndex].forEach((templateKey: string) => {
                    // Вынимаем элемент вёрски где-бы он ни находился и просто доабвляем в конец текущей колонки
                    $table.append($form.find('table.filters tr[data-templatekey="' + templateKey + '"]'));
                });
            }
        });

        // Кнопки фильтров расположим в первой колонке в самом конце
        $form.find("table.filters").first().append($form.find('.filters-buttons').closest('tr'))
    }

    /**
     * Добавляет класс filter-invisible ко всем строкам которые скрыты и в которых значение пусто
     * @param {Set<string>} invisibleTemplateKeys
     */
    private addInvisibleClasses(invisibleTemplateKeys: Set<string>) {

        if (this.noSettings) {
            // Если для данного модуля в целом отключена система настроек layout,
            // то и скрывать ничего не будем
            return;
        }

        const $form = this.$form;
        // Сначала удаляем все старые наложенные классы на всякий случай
        $form.remove(".filter-invisible");
        // Теперь накладываем классы заново
        invisibleTemplateKeys.forEach((templateKey) => {
            if (this.isDefaultValue(templateKey)) {
                // Скрываем только если поле имеет значение по-умолчанию
                // При проверке на default может быть не соответствие: templateKey не всегда совпадает с реальным fieldKey
                // В случае если среди фильтров отсутствует поле с ключём templateKey, метод isDefaultValue вернёт true
                // и значит мы скроем такую строку не проверив на самом деле что внутри
                $form.find(`tr[data-templatekey="${templateKey}"]`).addClass('filter-invisible');
            }
        });
    }

    private refreshInvisibleClasses()
    {
        if (!this.noSettings) {
            this.addInvisibleClasses(this.getInvisibleFromMakrup());
        }
    }

    private onHideIconClick = (e: any) => {
        e.preventDefault();
        const $icon = $(e.currentTarget);
        const isCurrentInvisible = $icon.hasClass("fa-eye-slash");
        const newInvisible = !isCurrentInvisible;
        $icon
            .toggleClass("fa-eye-slash", newInvisible)
            .toggleClass("fa-eye", !newInvisible);
    };

    private onAllVisibleClick = (e: any) => {
        e.preventDefault();
        // Убираем все перечёркнутые глазки, добавляем обычные глазки
        this.$form.find(".filter-hide-icon").removeClass("fa-eye-slash").addClass("fa-eye");
    };

    private onAllInvisibleClick = (e: any) => {
        e.preventDefault();
        // Убираем все обычные глазки, добавляем перечёркнутые глазки
        this.$form.find(".filter-hide-icon").removeClass("fa-eye").addClass("fa-eye-slash");
    };

    private showLayout() {
        window.scrollTo(0, 0);

        const $form = this.$form;

        $form.closest(".filters").css("z-index", 10);

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

        const $buttons = $form.find(".filters-buttons").addClass("display-none");

        const textResetSort = isLangRu ? "Сбросить сортировку фильтров" : "Reset to default layout";
        const textShowAll = isLangRu ? "Сделать все фильтры видимыми" : "Show all";
        const textHideAll = isLangRu ? "Сделать все фильтры невидимыми" : "Hide all";
        const textCancel = isLangRu ? "Отмена" : "Cancel";

        const $layoutButtons = $(`<div class="filters-layout-buttons">
                                             <span class="btn waves-effect waves-light bgcolor-btn-green filters-layout-save"><i class="fa fa-save" aria-hidden="true"></i></span>       
                                             <span class="btn-flat waves-effect filters-layout-reset toggle-tooltip" title="${textResetSort}"><i class="fa fa-random" aria-hidden="true"></i></span>
                                             <span class="btn-flat waves-effect filters-layout-allvisible toggle-tooltip" title="${textShowAll}"><i class="fa fa-eye" aria-hidden="true"></i></span>
                                             <span class="btn-flat waves-effect filters-layout-allinvisible toggle-tooltip" title="${textHideAll}"><i class="fa fa-eye-slash" aria-hidden="true"></i></span>
                                             <span class="btn-flat waves-effect filters-layout-cancel toggle-tooltip" title="${textCancel}"><i class="fa fa-ban" aria-hidden="true"></i></span>
                                        </div>`);

        initTooltip($layoutButtons);

        $buttons.after($layoutButtons);
    }

    hideLayout() {
        const $form = this.$form;

        $form.closest(".filters").css("z-index", 0);
        this.$layer.fadeOut("fast");

        $("body").css("overflow", "auto");

        $form.find(".filters-buttons").removeClass("display-none");
        $form.find(".filters-layout-buttons").remove();
    }

    isDefaultValue(fieldKey: string) {
        const field = this.form.getField(fieldKey);
        if (!field) {
            // Такого поля не существует, вернём true как будто у него стоит значение по умолчанию
            return true;
        }
        if (field.def === null || field.def === undefined) {
            // У этого поля не определено значение по умолчанию, вернём true, как будто оно и стоит
            return true;
        }
        const value = this.form.getFieldValue(fieldKey);
        if (value === null || value === undefined) {
            // Текущее значение не удалось определить, вернём true
            return true;
        }

        // Если текущее значение определено и занчение по умолчанию опеределено, то сравним их!
        return value.toString() === field.def.toString();
    }

    private onBodyClickTryHideWidget = (e:any) => {
        if (this.$widget
            && !$.contains(this.$widget.get(0), e.target)) {
            this.hideWidget();
        }
    };
}
