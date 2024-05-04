import { Form } from "./Form";
export declare type FiltersSubmitAction = (filterValues: any) => void;
export declare class Filters {
    private name;
    private submitAction;
    form: Form;
    private $form;
    private $layer;
    private allowLayoutChange;
    private $widget;
    private $fromUntilPopup;
    resetHandlers: (() => void)[];
    private layoutForCancel;
    private invisibleForCancel;
    private sortables;
    constructor(name: string, submitAction: FiltersSubmitAction, resetAction: () => void);
    getValue(fieldKey: string): string | number;
    getValues(): Map<string, string | number>;
    setValue(fieldKey: string, newValue: string | number): void;
    setValuesAfterReload(newValues: any): void;
    private renderHtmlIntoWidget;
    private highlightActive;
    private opacityForSelectAny;
    private onFormSubmit;
    private onTextareaKeydown;
    private reset;
    private onResetClick;
    private blockFilterButton;
    private unblockFilterButton;
    submit(): Promise<void>;
    private showWidget;
    private hideWidget;
    private onWidgetLikeResetClick;
    private onWidgetSelectFiltersClick;
    private onWidgetSaveCurrentClick;
    private onWidgetDeleteSavedClick;
    private onHistoryClick;
    private onSaveClick;
    private onFromUntilIconClick;
    private showFromUntilPopup;
    private onFromUntilPopupLinkClick;
    private onBodyClickTryHideFromUntilPopup;
    private hideFromUtilPopup;
    /**
     * Собирает текущий layout на основе вёрстки
     * Применяется в начальный момент времени, когда пользователь только нажал кнопку настроек, чтобы сохранить в this.layoutForCancel
     * и при нажатии кнопки "применить настройки", когда нужно отправить на сервер все настройки сделанные пользователем
     */
    private getLayoutFromMarkup;
    /**
     * Возвращает Set из ключей templatekey у котоырх в вёрстке стоит класс filter-invisible
     * @returns {string[]}
     */
    private getInvisibleFromMakrup;
    /**
     * Отображает иконки с глазками
     * Вызывается при начале работы в режиме настроек
     */
    private showEyeIcons;
    private onShowLayoutClick;
    onLayoutCancelClick: (e: any) => void;
    onLayoutSaveClick: (e: any) => void;
    private sortablesDistroy;
    private onLayoutResetClick;
    /**
     * Перемещает элементы в вёрстке в соответсве с переданным layout
     * Вызывается в двух случаях: когда пользователь что-то поменял в расположении фильтров и затем нажал Cancel
     * либо когда пользователь нажал "сбросить положение фильтров по умолчанию"
     * @param {string[][]} layout
     */
    private applyLayout;
    /**
     * Добавляет класс filter-invisible ко всем строкам которые скрыты и в которых значение пусто
     * @param {Set<string>} invisibleTemplateKeys
     */
    private addInvisibleClasses;
    private refreshInvisibleClasses;
    private onHideIconClick;
    private onAllVisibleClick;
    private onAllInvisibleClick;
    private showLayout;
    hideLayout(): void;
    isDefaultValue(fieldKey: string): boolean;
    private onBodyClickTryHideWidget;
}
