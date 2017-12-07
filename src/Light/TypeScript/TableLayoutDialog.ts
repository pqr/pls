import {Dialog} from "./Dialog";
import * as table from "./Table";
import { myParseInt, toastSuccess } from "./Light";

let dialog: Dialog,
    $shownUl: JQuery,
    $hiddenUl: JQuery,
    $layoutsDefault: JQuery,
    $layoutsUser: JQuery,
    $newLayoutInput: JQuery,
    $filter: JQuery,
    $btnSaveLayout: JQuery;

export interface Layout {
    name: string;
    fieldKeys: string[];
}

export interface Field {
    key: string;
    name: string;
    title: string;
}

export type DialogSubmitCallback = (shownFieldKeys: Array<string>) => void;

interface CurrentState {
    tableName: string;
    layouts: Layout[];
    allFields: Field[];
    initialShownFieldKeys: string[];
    dialogSubmitCallback: DialogSubmitCallback | null;
}

let currentState: CurrentState = {
    tableName: "",
    layouts: [],
    allFields: [],
    initialShownFieldKeys: [],
    dialogSubmitCallback: null
};

/**
     * Эта функция вызывается всего один раз при первом клике на любую кнопку Table layout settings
     */
function initDialog() {
    $btnSaveLayout = $("#tablelayoutdialog__btnsave").on("click", e => {
        e.preventDefault();
        const layoutName = $newLayoutInput.val();
        if (layoutName == "") {
            //нельзя сохранять настройку с пустым именем
            return;
        }
        ajaxCore({
            layoutName: layoutName,
            fieldsShow: getShownFieldKeys().join("|")
        }).done(() => {
            toastSuccess(`Настройки сохранены: <strong>${layoutName}</strong>`);
        });
    });

    $newLayoutInput = $("#tablelayoutdialog__newlayout"); //.on('key', userLayoutSaveByEnter, 'enter');

    $("#tablelayoutdialog__linkall").on("click", e => {
        e.preventDefault();
        updateSortables(currentState.allFields.map(f => f.key));
    });
    $("#tablelayoutdialog__linknone").on("click", e => {
        e.preventDefault();
        updateSortables([]);
    });

    $layoutsDefault = $("#tablelayoutdialog__layoutsdefault").on("click", "a", e => {
        e.preventDefault();
        const layoutIndex = myParseInt((<HTMLAnchorElement>e.currentTarget).hash.substring(1));
        const fieldsShow = currentState.layouts[layoutIndex].fieldKeys;
        updateSortables(fieldsShow);
    });

    $layoutsUser = $("#tablelayoutdialog__layoutsuser")
        .on("click", "a", e => {
            e.preventDefault();
            const a = <HTMLAnchorElement>e.currentTarget;
            $newLayoutInput.val(a.innerText);
            updateSortables(a.hash.substring(1).split("|"));
        })
        .on("click", "i", e => {
            ajaxCore({ delid: (<HTMLElement>e.currentTarget).getAttribute("data-delid") });
        });

    $shownUl = $("#tablelayoutdialog__showul").sortable({ connectWith: "#tablelayoutdialog__hideul" });
    $hiddenUl = $("#tablelayoutdialog__hideul").sortable({ connectWith: "#tablelayoutdialog__showul" });

    $filter = $("#tablelayoutdialog__filter").on("keyup change", e => {
        const filterValLower = (<HTMLInputElement>e.currentTarget).value.toLowerCase();
        if (filterValLower === "") {
            $hiddenUl.find("li.display-none").removeClass("display-none");
        } else {
            $hiddenUl.find("li").each(function(this: HTMLLIElement) {
                const $li = $(this);
                $li.toggleClass(
                    "display-none",
                    $li
                        .text()
                        .toLowerCase()
                        .indexOf(filterValLower) === -1 &&
                        ($li.attr("title") || "").toLowerCase().indexOf(filterValLower) === -1
                );
            });
        }
    });

    const $content = $("#tablelayoutdialog");
    const options = {
        width: "770px",
        buttonOkText: "Apply new Layout",
        buttonOkHandler: () => {
            dialog.close();
            currentState.dialogSubmitCallback!(getShownFieldKeys());
        }
    };

    dialog = new Dialog($content, options, false);

    $content.removeClass("display-none");
}

function updateSortables(showFieldKeys: Array<string>) {
    //Очистим оба списка в DOM
    $filter.val("");
    $shownUl.empty();
    $hiddenUl.empty();

    // Перебираем все поля и добавляем их либо в блок отображаемых, либо в блок скрытых
    // Подготовим накопительный массив полей, которые потом будут отрендрены в блок отображаемых в нужном порядке
    let toShow: Array<Field> = [];
    currentState.allFields.forEach(field => {
        // Определим позицию в блоке Shown в которую вставить текущее поле
        const showIndex = showFieldKeys.indexOf(field.key);
        if (showIndex === -1) {
            // Этого поля нет в массиве отображаемых, сразу вставляем его в список скрытых полей
            appendLi($hiddenUl, field.key, field.name, field.title);
        } else {
            // Определили позицию под которой нужно вставить в блок отображаемых полей. Но сразу вставлять в DOM не будем, накопим
            // в подготовительном массиве
            toShow[showIndex] = field;
        }
    });

    // Отрендрим накопленный массив отображаемых полей
    toShow.forEach(field => appendLi($shownUl, field.key, field.name, field.title));

    // После изменения DOM дерева внутри списков Sortable, нужно вызвать метод sync()
    $shownUl.sortable("refresh");
    $hiddenUl.sortable("refresh");
}

const appendLi = function($ul: JQuery, fieldKey: string, fieldName: string, fieldTitle: string) {
    //С помощью css класса подсветим поле фиолетовым или голубым
    const cssClass =
        currentState.initialShownFieldKeys.indexOf(fieldKey) === -1
            ? "tablelayoutdialog__field_hidden"
            : "tablelayoutdialog__field_shown";
    $("<li>")
        .text(fieldName)
        .attr("data-fieldkey", fieldKey)
        .attr("title", fieldTitle)
        .addClass(cssClass)
        .appendTo($ul);
};

/**
     * Возвращает массив из ключей полей, которые сейчас находятся в левом блоке (в блоке отображаемы)
     * Этот метод вызывается при сабмите диалога и при сохранении пользовательских настроек
     */
function getShownFieldKeys(): Array<string> {
    const shownFieldKeys: Array<string> = [];
    $shownUl.find("li").each(function(this: HTMLLIElement) {
        shownFieldKeys.push(this.getAttribute("data-fieldkey")!);
    });
    return shownFieldKeys;
}

function ajaxCore(postdata?: Object) {
    const url = "?a=Core&ps=table&table=" + currentState.tableName;
    $btnSaveLayout.prop("disabled", true);

    return $.ajax(url, <JQuery.AjaxSettings>{
        cache: false,
        data: postdata,
        type: "post"
    })
        .done(userLayoutsHtml => {
            // В ответ всегда приходит html код для вставки в виде списка пользовательских layouts
            $layoutsUser.html(userLayoutsHtml);
        })
        .always(() => {
            $btnSaveLayout.prop("disabled", false);
        });
}

/**
     * Этот метод специально сделан экспортируемым, чтобы можно было отображать диалоговое окно выбора полей
     * не только по стандартной ссылке "Table layout settings", но и в SPA приложении
     */
export function showLayoutDialog(
    tableName: string,
    allFields: Array<Field>,
    initialShownFieldKeys: Array<string>,
    layouts: Array<Layout>,
    userLayoutsHtml: string,
    dialogSubmitCallback: DialogSubmitCallback
) {
    if (!dialog) {
        initDialog();
    }

    currentState.tableName = tableName;
    currentState.allFields = allFields;
    currentState.initialShownFieldKeys = initialShownFieldKeys;
    currentState.layouts = layouts;
    currentState.dialogSubmitCallback = dialogSubmitCallback;

    updateSortables(initialShownFieldKeys);

    // Отрендрим блок с layouts default
    $layoutsDefault.empty();
    currentState.layouts.forEach((layout, index) => {
        $layoutsDefault.append(`<li><a href="#${index}">${layout.name}</a></li>`);
    });

    $layoutsUser.html(userLayoutsHtml);
    $newLayoutInput.val("");

    //Отобразим диалог
    dialog.open();
}

// На все кнопки Table Layout Settings вешаем обрабочик клика
$("body").on("click", "a.table-layoutsettings", e => {
    e.preventDefault();
    const tableName: string = $(e.currentTarget)
        .closest("div.table-div")
        .attr("data-tablename")!;
    const tableObj = table.get(tableName);
    $.ajax(tableObj.reloadUrl + "&loadTableLayoutSettings=1").done(settingsFromServer => {
        const { allFields, shownFieldKeys, layouts, userLayoutsHtml } = settingsFromServer;
        showLayoutDialog(
            tableName,
            allFields,
            shownFieldKeys,
            layouts,
            userLayoutsHtml,
            (shownFieldKeys: string[]) => {
                tableObj.reload({ fieldsShow: shownFieldKeys.join("|") });
            }
        );
    });
});
