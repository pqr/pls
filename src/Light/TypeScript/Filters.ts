import {
    buildMapFromObj,
    buildObjFromMap,
    initTooltip,
    isLangRu,
    loadSortableJs,
    parseJson,
    toastError
} from "./Light";
import * as forms from "./Form";
import { Field, Form } from "./Form";
import { Table } from "./Table";

declare var Sortable: any;
declare var dayjs: any;

export class Filters {
    private name: string;

    public form: Form;
    private $form: JQuery;
    private table: Table;
    private $layer: JQuery;

    // Описание схемы
    private allowLayoutChange: boolean;

    private $widget: JQuery | undefined;
    private $fromUntilPopup: JQuery | undefined;

    public resetHandlers: (() => void)[] = [];

    private layoutForCancel: string[][];
    private invisibleForCancel: Set<string>;

    // Массив в котором будут храниться инстансы Sortable объектов,
    // чтобы при закрытии режима настроек сделать им всем destroy()
    private sortables: any[] = [];

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
        $form.find(".Filters-FromUntilIcon").on("click", this.onFromUntilIconClick);

        this.allowLayoutChange = $form.attr("data-allowlayoutchange") === "1";
        if (this.allowLayoutChange) {
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

    setValue(fieldKey: string, newValue: string | number) {
        this.form.setFieldValue(fieldKey, newValue);
    }

    private renderHtmlIntoWidget(html: string) {
        if (this.$widget) {
            this.$widget.html(html);
        }
    }

    private highlightActive() {
        //Сначала уберём подсветку со всех полей
        this.$form.find(".filter-active").removeClass("filter-active");
        this.form.getFields().forEach((_: any, fieldKey: string) => {
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
        this.form.getFields().forEach((field: Field, fieldKey: string) => {
            if (!field.donotreset) {
                this.form.setFieldValue(fieldKey, field.def || "");
            }
        });
    }

    private onResetClick = () => {
        if (this.table.isLoading()) {
            // Если в момент нажатия RESET уже происходила какая-то загрузка данных, то её нужно прервать,
            this.table.cancelReload();
        }
        this.unblockFilterButton();
        this.reset();
        this.resetHandlers.forEach(handler => handler());
    };

    private blockFilterButton() {
        this.$form
            .addClass("loading-opacity")
            .find(".btn-filter")
            .prop("disabled", true);
    }

    private unblockFilterButton() {
        this.$form
            .removeClass("loading-opacity")
            .find(".btn-filter")
            .prop("disabled", false);
    }

    submit() {
        this.blockFilterButton();
        const reloadParams = {
            filterValues: JSON.stringify(buildObjFromMap(this.form.getFieldsValues())),
            page: 1
        };
        this.table.reload(reloadParams, () => {
            const hiddenInput = this.table.getDivNode().find("input[name=filterValues]");
            if (hiddenInput.length > 0) {
                const newValues = parseJson(<string>hiddenInput.val());
                for (let fieldKey in newValues) {
                    this.setValue(fieldKey, newValues[fieldKey]);
                }

                // Подсветим активные фильтры жирным и семеним иконку у кнопки Filter, если нужно
                this.highlightActive();

                // Запустим механизм скрытия скрытых фильтров заново
                // это нужно чтобы отобразить те фильтры, у которых значение отличается от значения по умолчанию
                this.refreshInvisibleClasses();
            }

            this.unblockFilterButton();
        });
    }

    private showWidget(clickTarget: HTMLElement) {
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

        // Делаем задержку в сабмите формы, чтобы успели загрузиться selec2ajax опции
        // это грязный хак - совсем не факт, что опции успеют загрузиться за 1 секунду
        setTimeout(() => {
            this.submit();
        }, 1000);

        // Кнопку фильтрации блокируем сразу, чтобы у пользователя сложилось впечатление, что загрузка уже идёт
        // хотя на самом деле мы ждём 1 сек перед реальным submit()
        this.blockFilterButton();

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
        }).done((savedHtml: string) => {
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
        }).done((savedHtml: string) => {
            // Если успешно удалили то от сервера приходит обновлённый список всех сохранённых настроек
            this.renderHtmlIntoWidget(savedHtml);
        });
    };

    private onHistoryClick = (e: any) => {
        e.preventDefault();

        this.showWidget(e.currentTarget);

        $.ajax("?a=Core&ps=FiltersGetHistoryHtml&TableName=" + encodeURIComponent(this.name)).done(
            (responseHtml: string) => {
                this.renderHtmlIntoWidget(responseHtml);
            }
        );
    };

    private onSaveClick = (e: any) => {
        e.preventDefault();
        this.showWidget(e.currentTarget);
        $.ajax("?a=Core&ps=FiltersGetSavedHtml&TableName=" + encodeURIComponent(this.name)).done(
            (savedHtml: string) => {
                this.renderHtmlIntoWidget(savedHtml);
            }
        );
    };

    private onFromUntilIconClick = (e: any) => {
        e.preventDefault();
        this.showFromUntilPopup(e.target);
    };

    private showFromUntilPopup(clickTarget: HTMLElement) {
        const $fromUntilPopup = $(
            `<div class="floatingwidget FromUntilPopup">
            <ul class="FromUntilPopup-List">
                <li class="FromUntilPopup-Item"><a class="FromUntilPopup-Link" href="#today">Сегодня</a></li>
                <li class="FromUntilPopup-Item"><a class="FromUntilPopup-Link" href="#yesterday">Вчера</a></li>
                <li class="FromUntilPopup-Item"><a class="FromUntilPopup-Link" href="#week">На этой неделе</a></li>
                <li class="FromUntilPopup-Item"><a class="FromUntilPopup-Link" href="#month">В этом месяце</a></li>
                <li class="FromUntilPopup-Item"><a class="FromUntilPopup-Link" href="#prevmonth">В прошлом месяце</a></li>
                <li class="FromUntilPopup-Item"><a class="FromUntilPopup-Link" href="#year">В этом году</a></li>
            </ul>
        </div>`
        );

        $fromUntilPopup.on("click", "a", this.onFromUntilPopupLinkClick);

        // Вставляем в вёрстку
        $fromUntilPopup.insertAfter(clickTarget);

        // Перемещаем палитру непосретсвенно рядом с нажатой кнопкой
        let offset = $(clickTarget).offset()!;
        if (offset !== undefined) {
            offset.top += 17;
            $fromUntilPopup.offset(offset);
        }

        // Сохраняем в своействе класса, чтобы потом можно было сделать hide (удалив элемет из dom)
        this.$fromUntilPopup = $fromUntilPopup;

        setTimeout(() => {
            $("body").on("click", this.onBodyClickTryHideFromUntilPopup);
        }, 0);
    }

    private onFromUntilPopupLinkClick = (e: any) => {
        if (!this.$fromUntilPopup) {
            return;
        }

        e.preventDefault();
        const interval = e.target.hash.replace('#', '');
        const dj = dayjs();
        let fromDate, untilDate;
        switch (interval) {
            case "today":
                fromDate = dj;
                untilDate = fromDate;
                break;
            case "yesterday":
                fromDate = dj.subtract(1, 'day');
                untilDate = fromDate;
                break;
            case "week":
                fromDate = dj.startOf('week');
                untilDate = dj.endOf('week');
                break;
            case "month":
                fromDate = dj.startOf('month');
                untilDate = dj.endOf('month');
                break;
            case "prevmonth":
                fromDate = dj.subtract(1, 'month').startOf('month');
                untilDate = fromDate.endOf('month');
                break;
            case "year":
                fromDate = dj.startOf('year');
                untilDate = fromDate.endOf('year');
                break;

            default:
                throw Error('From-Until Popup: unknown interval ' + interval);

        }

        const $inputs = this.$fromUntilPopup.siblings('input.input-date');
        $inputs.eq(0).val(fromDate.format('DD.MM.YYYY'));
        $inputs.eq(1).val(untilDate.format('DD.MM.YYYY'));

        this.hideFromUtilPopup();
    };

    private onBodyClickTryHideFromUntilPopup = (e: any) => {
        if (this.$fromUntilPopup && !this.$fromUntilPopup.get(0).contains(e.target)) {
            this.hideFromUtilPopup();
        }
    };

    private hideFromUtilPopup() {
        if (this.$fromUntilPopup) {
            this.$fromUntilPopup.remove();
            this.$fromUntilPopup = undefined;

            $("body").off("click", this.onBodyClickTryHideFromUntilPopup);
        }
    }

    /**
     * Собирает текущий layout на основе вёрстки
     * Применяется в начальный момент времени, когда пользователь только нажал кнопку настроек, чтобы сохранить в this.layoutForCancel
     * и при нажатии кнопки "применить настройки", когда нужно отправить на сервер все настройки сделанные пользователем
     */
    private getLayoutFromMarkup(): string[][] {
        const layoutFromMarkup: string[][] = [];
        this.$form.find("ul.filters__list").each(function(this: any, colIndex: number) {
            const $ul = $(this);
            layoutFromMarkup[colIndex] = [];
            $ul.find("li[data-templatekey]").each(function(this: any) {
                const $li = $(this);
                const templateKey = <string>$li.attr("data-templatekey")!;
                layoutFromMarkup[colIndex].push(templateKey);
            });
        });

        return layoutFromMarkup;
    }

    /**
     * Возвращает Set из ключей templatekey у котоырх в вёрстке стоит класс filter-invisible
     * @returns {string[]}
     */
    private getInvisibleFromMakrup(): Set<string> {
        const invisibleFromMarkup = new Set<string>();
        this.$form.find("li[data-templatekey].filter-invisible").each(function(this: HTMLLIElement) {
            invisibleFromMarkup.add($(this).attr("data-templatekey")!);
        });

        return invisibleFromMarkup;
    }

    /**
     * Отображает иконки с глазками
     * Вызывается при начале работы в режиме настроек
     */
    private showEyeIcons() {
        this.$form.find("li[data-templatekey]").each(function(this: HTMLLIElement) {
            const $li = $(this);
            const invisible = $li.hasClass("filter-invisible");
            const iconClass = invisible ? "fa-eye-slash" : "fa-eye";
            const icon = `<i class="filter-hide-icon fa ${iconClass}"></i>`;
            $li.find(".filter__label").prepend(icon);
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

        /*
        $form.find("table.filters").sortable({
            connectWith: "table.filters",
            items: "> tbody > tr",
            cursor: "move",
            handle: "th:not(:empty)"
        });
        */
        loadSortableJs().then(() => {
            $form.find("ul.filters__list").each((_: any, ul: HTMLUListElement) => {
                const sortable = Sortable.create(ul, {
                    group: "filters",
                    filter: ".filters__buttons, .filters__layoutbuttons", // Selectors that do not lead to dragging (String or Function)
                    animation: 100
                });
                // Сохраним в специальный массив, чтобы при закрытии сделать destroy
                this.sortables.push(sortable);
            });
        });
    };

    onLayoutCancelClick = (e: any) => {
        e.preventDefault();

        const $form = this.$form;

        // Отключаем виджет сортировки
        //$form.find("table.filters").sortable("destroy");
        this.sortablesDistroy();
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

        // Соберём набор полей которые пользователь отметил иконкой "скрыть" (перечёркнутый глаз)
        const newInvisible = new Set<string>();
        $form.find(".fa-eye-slash").each(function(this: HTMLElement) {
            const $li = $(this).closest("li[data-templatekey]");
            if ($li.length === 1) {
                newInvisible.add($li.attr("data-templatekey")!);
            }
        });

        // Отправляем новые настройки на сервер
        $.post("?a=Core&ps=FiltersSaveLayout&TableName=" + encodeURIComponent(this.name), {
            Layout: newLayout,
            Invisible: Array.from(newInvisible)
        });

        // Отключаем jQuery плагин sortable
        //$form.find("table.filters").sortable("destroy");
        this.sortablesDistroy();

        // Физически скрываем все отмеченные перечёкнутым глазом фильтроы
        this.addInvisibleClasses(newInvisible);

        // Убираем все иконки с глазами
        $form.find(".filter-hide-icon").remove();

        // Скрываем форму настроек
        this.hideLayout();
    };

    private sortablesDistroy() {
        // Удаляем все объекы виджета Sortable накопленные в специальном массиве
        this.sortables.forEach((sortable: any) => {
            sortable.destroy();
        });
        // B очищаем этот специальный массив целиком
        this.sortables = [];
    }

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
    private applyLayout(layout: string[][]) {
        const $form = this.$form;

        // Перебирем колонки, которые уже есть в вёрстке
        $form.find("ul.filters__list").each(function(this: HTMLUListElement, colIndex: number) {
            const $ul = $(this);
            // Внутри каждой колонки применяем полученный порядок полей пришедший в параметре layout
            if (layout[colIndex] !== undefined) {
                layout[colIndex].forEach((templateKey: string) => {
                    // Вынимаем элемент вёрски где-бы он ни находился и просто доабвляем в конец текущей колонки
                    $ul.append($form.find('li[data-templatekey="' + templateKey + '"]'));
                });
            }
        });

        // Кнопки фильтров расположим в первой колонке в самом конце
        $form
            .find("ul.filters__list")
            .first()
            .append($form.find("li.filters__buttons"));
        $form
            .find("ul.filters__list")
            .first()
            .append($form.find("li.filters__layoutbuttons"));
    }

    /**
     * Добавляет класс filter-invisible ко всем строкам которые скрыты и в которых значение пусто
     * @param {Set<string>} invisibleTemplateKeys
     */
    private addInvisibleClasses(invisibleTemplateKeys: Set<string>) {
        if (!this.allowLayoutChange) {
            // Если для данного модуля в целом отключена система настроек layout,
            // то и скрывать ничего не будем
            return;
        }

        const $form = this.$form;
        // Сначала удаляем все старые наложенные классы на всякий случай
        $form.remove(".filter-invisible");
        // Теперь накладываем классы заново
        invisibleTemplateKeys.forEach(templateKey => {
            if (this.isDefaultValue(templateKey)) {
                // Скрываем только если поле имеет значение по-умолчанию
                // При проверке на default может быть не соответствие: templateKey не всегда совпадает с реальным fieldKey
                // В случае если среди фильтров отсутствует поле с ключём templateKey, метод isDefaultValue вернёт true
                // и значит мы скроем такую строку не проверив на самом деле что внутри
                $form.find(`li[data-templatekey="${templateKey}"]`).addClass("filter-invisible");
            }
        });
    }

    private refreshInvisibleClasses() {
        if (this.allowLayoutChange) {
            this.addInvisibleClasses(this.getInvisibleFromMakrup());
        }
    }

    private onHideIconClick = (e: any) => {
        e.preventDefault();
        const $icon = $(e.currentTarget);
        const isCurrentInvisible = $icon.hasClass("fa-eye-slash");
        const newInvisible = !isCurrentInvisible;
        $icon.toggleClass("fa-eye-slash", newInvisible).toggleClass("fa-eye", !newInvisible);
    };

    private onAllVisibleClick = (e: any) => {
        e.preventDefault();
        // Убираем все перечёркнутые глазки, добавляем обычные глазки
        this.$form
            .find(".filter-hide-icon")
            .removeClass("fa-eye-slash")
            .addClass("fa-eye");
    };

    private onAllInvisibleClick = (e: any) => {
        e.preventDefault();
        // Убираем все обычные глазки, добавляем перечёркнутые глазки
        this.$form
            .find(".filter-hide-icon")
            .removeClass("fa-eye")
            .addClass("fa-eye-slash");
    };

    private showLayout() {
        window.scrollTo(0, 0);

        const $form = this.$form;

        $form.closest(".filters").addClass("filters__layoutmode");
        $form.find("input, select, textarea").prop("disabled", true);

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

        const $buttons = $form.find(".filters__buttons").addClass("display-none");

        const textResetSort = isLangRu ? "Сбросить сортировку фильтров" : "Reset to default layout";
        const textShowAll = isLangRu ? "Сделать все фильтры видимыми" : "Show all";
        const textHideAll = isLangRu ? "Сделать все фильтры невидимыми" : "Hide all";
        const textCancel = isLangRu ? "Отмена" : "Cancel";

        const $layoutButtons = $(`<li class="filters__layoutbuttons">
                                             <span class="btn waves-effect bgcolor-btn-green filters-layout-save"><i class="fa fa-save"></i></span>       
                                             <span class="btn-flat waves-effect filters-layout-reset-sort toggle-tooltip" title="${textResetSort}"><i class="fa fa-random"></i></span>
                                             <span class="btn-flat waves-effect filters-layout-allvisible toggle-tooltip" title="${textShowAll}"><i class="fa fa-eye"></i></span>
                                             <span class="btn-flat waves-effect filters-layout-allinvisible toggle-tooltip" title="${textHideAll}"><i class="fa fa-eye-slash"></i></span>
                                             <span class="btn-flat waves-effect filters-layout-cancel toggle-tooltip" title="${textCancel}"><i class="fa fa-ban"></i></span>
                                        </li>`);

        initTooltip($layoutButtons);

        $buttons.after($layoutButtons);
    }

    hideLayout() {
        const $form = this.$form;

        $form.closest(".filters").removeClass("filters__layoutmode");
        $form.find("input, select, textarea").prop("disabled", false);
        this.$layer.fadeOut("fast");

        $("body").css("overflow", "auto");

        $form.find(".filters__buttons").removeClass("display-none");
        $form.find(".filters__layoutbuttons").remove();
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

    private onBodyClickTryHideWidget = (e: any) => {
        if (this.$widget && !$.contains(this.$widget.get(0), e.target)) {
            this.hideWidget();
        }
    };
}
