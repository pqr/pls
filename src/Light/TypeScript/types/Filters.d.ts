import { Form } from "./Form";
import { Table } from "./Table";
export declare class Filters {
    private name;
    form: Form;
    private $form;
    private table;
    private $layer;
    private noSettings;
    private $widget;
    resetHandlers: (() => void)[];
    private layoutForCancel;
    private invisibleForCancel;
    constructor(table: Table);
    getValue(fieldKey: string): string | number;
    getValues(): Map<string, string | number>;
    setValue(fieldKey: string, newValue: string | number): void;
    private renderHtmlIntoWidget(html);
    private highlightActive();
    private onSubmit;
    private onTextareaKeydown;
    private reset();
    private onResetClick;
    private submit();
    private showWidget(clickTarget);
    private hideWidget();
    private onWidgetLikeResetClick;
    private onWidgetSelectFiltersClick;
    private onWidgetSaveCurrentClick;
    private onWidgetDeleteSavedClick;
    private onHistoryClick;
    private onSaveClick;
    /**
     * Собирает текущий layout на основе вёрстки
     * Применяется в начальный момент времени, когда пользователь только нажал кнопку настроек, чтобы сохранить в this.layoutForCancel
     * и при нажатии кнопки "применить настройки", когда нужно отправить на сервер все настройки сделанные пользователем
     */
    private getLayoutFromMarkup();
    /**
     * Возвращает Set из ключей templatekey у котоырх в вёрстке стоит класс filter-invisible
     * @returns {string[]}
     */
    private getInvisibleFromMakrup();
    /**
     * Отображает иконки с глазками
     * Вызывается при начале работы в режиме настроек
     */
    private showEyeIcons();
    private onShowLayoutClick;
    onLayoutCancelClick: (e: any) => void;
    onLayoutSaveClick: (e: any) => void;
    private onLayoutResetClick;
    /**
     * Перемещает элементы в вёрстке в соответсве с переданным layout
     * Вызывается в двух случаях: когда пользователь что-то поменял в расположении фильтров и затем нажал Cancel
     * либо когда пользователь нажал "сбросить положение фильтров по умолчанию"
     * @param {string[][]} layout
     */
    private applyLayout(layout);
    /**
     * Добавляет класс filter-invisible ко всем строкам которые скрыты и в которых значение пусто
     * @param {Set<string>} invisibleTemplateKeys
     */
    private addInvisibleClasses(invisibleTemplateKeys);
    private refreshInvisibleClasses();
    private onHideIconClick;
    private onAllVisibleClick;
    private onAllInvisibleClick;
    private showLayout();
    hideLayout(): void;
    isDefaultValue(fieldKey: string): boolean;
    private onBodyClickTryHideWidget;
}
