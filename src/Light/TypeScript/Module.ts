import { listener, myParseInt, getPageParam, parseJson, toastError, getModule, isLangRu } from "./Light";
import * as form from "./Form";
import * as table from "./Table";
import { Dialog, DialogOptions } from "./Dialog";

export type ModuleState = "list" | "addnew" | "view" | "edit";

const state: ModuleState = getPageParam("state");

// Если сейчас режим Edit или AddNew - проинициализируется jQuery объект формы редактирования
// он понадобится во многих методах дальше
let editForm: form.Form;
const objectId: number = myParseInt(getPageParam("id"));
let validationHandler: () => boolean;

function blockSaveButtons(block: boolean) {
    editForm
        .getFormNode()
        .find(".btn-save")
        .prop("disabled", block);
}

function showErrorDialog(responseText: string) {
    // Уберём красную окраску у старых проблемных полей
    $(".input-error").removeClass("input-error");

    // Окрасим новые проблемные поля в красный и добавим title, заодно соберём все сообщения для диалога
    // в отдельный массив
    let dialogBodyHtml;
    let hasCriticalError = false;

    interface ErrorItem {
        msg: string;
        fieldKey?: string;
        critical?: boolean;
    }

    const errors: ErrorItem[] | false | null = parseJson(responseText, false);
    if (errors === false || errors === null) {
        // Не удалось распарсить json, выведем весь текст ошибки как есть!
        dialogBodyHtml = responseText;
        hasCriticalError = true;
    } else {
        // Подсветим все поля с ошибками
        errors.forEach(err => {
            if (err.fieldKey) {
                editForm.highlightErrorField(err.fieldKey);
            }
        });

        // Сформируем тело для диалога из списка ошибок
        if (errors.length > 1) {
            dialogBodyHtml = `<ol class="save-errors">${errors.map(({ msg }) => `<li>${msg}</li>`).join("")}</ol>`;
        } else {
            dialogBodyHtml = errors[0].msg;
        }

        // Проверим, были ли среди ошибок критчные? (хотя бы одна)
        hasCriticalError = errors.some(({ critical }) => critical !== undefined && critical);
    }

    const closeMsg = isLangRu ? "Закрыть" : "Close";
    const cancelMsg = isLangRu ? "Отменить" : "Cancel";
    const options: DialogOptions = {
        buttonCancelText: hasCriticalError ? closeMsg : cancelMsg,
        width: "500px",
        afterClose: () => {
            $(".input-error:first").focus();
        }
    };

    if (!hasCriticalError) {
        // Если не было критичных ошибок, то добавим кнопку 'Confirm Save'
        options.buttonOkText = isLangRu ? "Подтвердить" : "Confirm Save";
        options.buttonOkHandler = function(dialog) {
            dialog.close();
            // Вызываем метод сохранения с параметром true - чтобы в ajax запрос к серверу передать GET параметр
            // saveanyway=1 - в этом случае на серверной стороне будут проигнорированы все некритиные ошибки
            save(true);
        };
    }

    new Dialog(dialogBodyHtml, options).open();
}

/**
 * Эта функция может вызываться из validationHandler. Например, VInvoice диалоговое окно
 */
export function save(saveanyway: boolean, additionalData?: Object) {
    blockSaveButtons(true);
    const $editForm = editForm.getFormNode();

    let postData = $editForm.serialize();
    let url = <string>$editForm.attr("action")!;
    if (saveanyway) {
        url += "&saveanyway=1";
    }

    if (additionalData) {
        //Если в этот метод были принудительно переданы какие-то данные, то добавим их в POST запрос
        //Это могут быть данные из диалога подтверждающего сохранение (если такой был при налаичии не критичных ошибок)
        postData += "&" + $.param(additionalData);
    }

    $.post(url, postData)
        .done((_: any, __: any, jqXHR: any) => {
            //В заголовках ответа должен прийти Location куда перенаправить пользователя после сохранения
            if (!jqXHR.getResponseHeader("x-light-redirect")) {
                //Если же нет этого заголовка для перенаправления, то разблокируем кнопки Save и выведем диалог с ошибками
                blockSaveButtons(false);
                //Если в пришедшем ajax ответе от сервера есть Header заголовок указывающий на то, что ответ содержит в себе
                //какое-то диалоговое окно, то в этом методе ничего делать не будем. Диалог отрисуется средствами js
                if (!jqXHR.getResponseHeader("x-light-showdialog")) {
                    //Нет url для редиректа, значит какая-то ошибка в sql или ешё где - выведем текст ошибки
                    showErrorDialog(jqXHR.responseText);
                }
            }
        })
        .fail((jqXHR: any) => {
            blockSaveButtons(false);
            //Если в пришедшем ajax ответе от срвера есть Header заголовок указывающий на то, что ответ содержит в себе
            //какое-то диалоговое окно, то в этом методе ничего делать не будем. Диалог отрисуется средствами js
            if (!jqXHR.getResponseHeader("x-light-showdialog")) {
                showErrorDialog(jqXHR.responseText);
            }
        });
}

export function getState(): ModuleState {
    return state;
}

export function isView(): boolean {
    return state === "view";
}

export function isAddNewOrEdit(): boolean {
    return state === "addnew" || state === "edit";
}

export function getObjectId(): number {
    return objectId;
}

export function getModuleSessionParams(): string {
    return getPageParam("moduleSessionParams");
}

export function getFieldNode(fieldKey: string): JQuery {
    return editForm.getFieldNode(fieldKey);
}

export function getFieldValue(fieldKey: string): any {
    return editForm.getFieldValue(fieldKey);
}

export function setFieldValue(fieldKey: string, newValue: any) {
    editForm.setFieldValue(fieldKey, newValue);
}

export function onFieldChange(fieldKey: string, handler: (newValue: any, $input: JQuery) => void) {
    editForm.onFieldChange(fieldKey, handler);
}

export function highlightErrorField(fieldKey: string) {
    getFieldNode(fieldKey).addClass("input-error");
}

export function ajaxChangeField(id: number, fieldKey: string, newValue: string): any {
    return $.post(`?a=${getModule()}&id=${id}&action=AjaxChangeField`, {
        fieldKey: fieldKey,
        value: newValue
    });
}

export function setValidationHandler(handler: () => boolean): void {
    validationHandler = handler;
}

//Обработчик сохранения
const $body = $("body");
$body.on("click", "#edit-form .btn-save", function(e: any) {
    if ($(e.currentTarget).hasClass("disabled")) {
        return;
    }

    $(".input-error").removeClass("input-error");
    // Возможно, пользователь установил некую функцию validationHandler
    // нужно её запустить и проверить валидность формы
    if (typeof validationHandler !== "function" || validationHandler()) {
        save(false);
    }
});

$body.on("click", ".listmassaction__selectall", (e: any) => {
    e.preventDefault();
    let module = getModule();
    if (module === "Reportmenu") {
        const urlParams = new URLSearchParams(location.search);
        module = "Report" + urlParams.get("class");
    }
    table.get(module).selectAll(true);
});

$body.on("click", ".listmassaction__selectnone", (e: any) => {
    e.preventDefault();
    let module = getModule();
    if (module === "Reportmenu") {
        const urlParams = new URLSearchParams(location.search);
        module = "Report" + urlParams.get("class");
    }
    table.get(module).selectAll(false);
});

$body.on("change", "#massaction-select", function(this: HTMLSelectElement, e: any) {
    e.preventDefault();

    // Узнаем значение в выпадающем списке - именно он будет именем того massaction, который нужно выполнить
    const select = this;
    const action = select.value;

    let ids;

    const module = getModule();
    let reportClass;
    if (module === "Reportmenu") {
        const urlParams = new URLSearchParams(location.search);
        reportClass = urlParams.get("class");
    }

    //Соберём значения чекбоксов в таблице и отправим их ajax POST запросом
    try {
        let moduleName = module;
        if (module === "Reportmenu") {
            moduleName = "Report" + reportClass;
        }
        ids = table.get(moduleName).getSelectedValues();
    } catch (e) {
        // Почему-то на экране на нашлась таблица с именем _lightModule - ничего не делаем
        return;
    }
    if (ids.length === 0) {
        const msg = isLangRu ? "Выберите строки галочками" : "Select rows with checkboxes";
        toastError(msg);
        $(this).val("");
        return;
    }

    //Проверим, если была попытка отправить запрос на массовое удаление, нужно переспросить пользователя
    const confirmMsg = isLangRu ? "Удалить выбранные записи?" : "Delete selected records?";
    if (action === "delete" && !confirm(confirmMsg)) {
        select.value = "";
        return;
    }

    let url = `?a=${getModule()}`;
    if (module === "Reportmenu") {
        url = `?a=Reportmenu&class=${reportClass}`;
    }
    url += `&p=massaction&action=${action}`;

    // Узнаем, следует ли открывать новое окно при сабмите?
    if (
        $(select)
            .find(":selected")
            .hasClass("massaction-target-blank")
    ) {
        // Нужно открывать в новом окне, т.к. в выбранном option есть специальный css class
        // Чтобы открыть в новом окне и при этом сделать POST запрос - создадим временную форму и засабмитем её!
        $("<form></form>")
            .attr({ action: url, method: "post", target: "_blank" })
            .append(`<input type="text" name="CheckedIds" value="${ids.join(",")}">`)
            .append(`<input type="hidden" name="csrf_token" value="${(window as any)._lightCsrfToken}">`)
            .appendTo("body")
            .submit()
            .remove();

        // Сбрасываем выбор в выпадающем списке
        this.value = "";
    } else {
        // У этого option не было пометки откывать в новом окне - просто делаем AJAX POST запрос
        // Заблокируем выпадающий список
        select.disabled = true;
        $.post(url, { CheckedIds: ids.join(",") }).always(() => {
            select.disabled = false;
            // Сбрасываем выбор в выпадающем списке
            select.value = "";
        });
    }
});

$body.on("click", "a.mushowhide", function(this: HTMLAnchorElement, e: any) {
    e.preventDefault();
    const $link = $(this);

    // Текущее состояние хранится справа от ссылки в скрытом поле
    const $state = $link.next("input[type=hidden]");

    let newState: "showed" | "hidden";
    newState = $state.val() === "hidden" ? "showed" : "hidden";

    $state.val(newState);

    // Теперь найдём само поле в соседнем td и отобразим или скроем его
    $link
        .parent()
        .next("td")
        .find(".mufield")
        .toggleClass("display-none", newState === "hidden");
});

function initShowHideListeners() {
    const $editForm = editForm.getFormNode();
    $editForm.find("div[data-showhide-when-fieldkey]").each(function(this: HTMLDivElement) {
        const $div = $(this);
        const fieldKey = $div.attr("data-showhide-when-fieldkey")!;
        let showWhenFieldValue = $div.attr("data-showhide-when-fieldvalue");
        if (showWhenFieldValue !== undefined) {
            showWhenFieldValue = JSON.parse(showWhenFieldValue);
        }
        onFieldChange(fieldKey, (newValue: any) => {
            newValue = parseInt(newValue);
            let show;
            if (showWhenFieldValue !== undefined) {
                if (Array.isArray(showWhenFieldValue)) {
                    show = showWhenFieldValue.indexOf(newValue) !== -1;
                } else {
                    show = newValue == showWhenFieldValue;
                }
            } else {
                show = !!newValue;
            }

            if (show) {
                $div.slideDown();
            } else {
                $div.slideUp();
            }
        });
    });
}

listener.simple_combo("ctrl e", function() {
    if (state === "view") {
        document.location.href = `?a=${getModule()}&editid=${objectId}&${getModuleSessionParams()}`;
    }
});

listener.simple_combo("ctrl s", function() {
    if (state === "edit" || state === "addnew") {
        $("#edit-form")
            .find(".btn-save")
            .first()
            .trigger("click");
    }
});

if (state === "view") {
    // В режиме просмтра найдём ссылку на редактирование и добавим к ней обработчик клика
    $(`a[href*="editid=${objectId}"]`).on("click", (e: any) => {
        e.currentTarget.href += `&scrollTop=${document.documentElement.scrollTop}`;
    });
} else if (state === "edit" || state === "addnew") {
    editForm = form.get("edit-form");
    initShowHideListeners();
    // Передаём фокус на элемент Name, но в следующем event loop цикле,
    // чтобы успели приинициализироваться все прочие JS файлы
    setTimeout(() => {
        editForm
            .getFormNode()
            .find("input[name=Name]")
            .focus();
    }, 0);
} else if (state === "list") {
    // В самом начел загрузки обновим таблицу в режиме List отдельным ajax запросом
    try {
        const tableName = getModule();
        const listTable = table.get(tableName);
        listTable.initMassactionListeners();
        const findid = getPageParam("listInitialFindid");
        const page = getPageParam("listInitialPage");
        // В массив отправляемых данных поставляе начальную страницу с которой нужно начат рендеринг
        let data: any = { page: page };
        if (findid) {
            data[`${tableName}_findid`] = findid;
        }
        listTable.reload(data);
    } catch (e) {
        console.log(e);
    }
}
