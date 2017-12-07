export interface Button {
    id?: string;
    text: string;
    isPrimary?: boolean;
    className?: string;
    handler: (dialog: Dialog, buttonId: string) => void;
}
export interface DialogOptions {
    buttonOkText?: string;
    buttonOkHandler?: (dialog: Dialog) => void;
    buttonCancelText?: string;
    buttons?: Array<Button>;
    width?: string;
    height?: string;
    top?: string;
    afterClose?: () => void;
}
export declare class Dialog {
    $div: JQuery | null;
    $divContent: JQuery | null;
    private top;
    private afterClose;
    private destroyAfterClose;
    private $overlay;
    private defaultSubmitHandler;
    constructor(content: any, options?: DialogOptions, destroyAfterClose?: boolean);
    open(): void;
    positiionCenter(): void;
    close(): void;
    blockButtons(): void;
    unblockButtons(): void;
}
