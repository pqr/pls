import * as _ from "lodash";
import {initSelect2} from "./Select2";

declare var window: any;

export const listener = new window.keypress.Listener();

function dateformatToJs(phpDateFormat: string): string {
    return phpDateFormat
        .replace("Y", "yy")
        .replace("m", "mm")
        .replace("d", "dd");
}

$.datepicker.setDefaults({
    changeMonth: true,
    changeYear: true,
    showWeek: true,
    dateFormat: dateformatToJs(window._lightDateFormat),
    showAnim: "",
    firstDay: 1
});

// Закешируем изображение для ajax вызовов
let img = new Image();
img.src = "lightpublic/i/circle.gif";

export function getLang() {
    return window["_lightLang"];
}

export const isLangRu = window["_lightLang"] === "ru";

function initSelectChild($root: JQuery) {
    $root.find("select.select-child").each(function (this: HTMLSelectElement) {
        const $child = $(this);
        const initChildValue = <string>$child.val();
        const childFieldKey = $child.attr("data-ownfieldkey")!;
        const childOriginalHtml = $child.html();
        const parentFieldKeys: Array<string> = $.parseJSON($child.attr("data-parentfieldkeys")!);

        // С сервера может прийти объект relations в котором числовые данные, однако значения атрибута value
        // в теге option всегда будет строковое - из-за этого lodash filter не сможет сопоставить
        // Заранее переведём все значения в relations в
        const relations: Array<{ [key: string]: string | number }> = JSON.parse($child.attr("data-relations")!).map(
            (relation: { [key: string]: string | number }) => {
                for (let parentKey in relation) {
                    relation[parentKey] = relation[parentKey].toString();
                }
                return relation;
            }
        );

        // элемент от которого зависит данный select должен находиться в той же форме
        let $form = $child.closest("form");
        if ($form.length === 0) {
            // Форма не найдена, тогда поиск будем делать в body
            $form = $(document.body);
        }

        // В самом начале загрузки страницы добавить в массив relations ещё один объект - объект содержащий текущие
        // значения всех элементов
        let additionalRelation: { [key: string]: string } = {};
        additionalRelation[childFieldKey] = initChildValue;
        parentFieldKeys.forEach(
            parentFieldKey =>
                (additionalRelation[parentFieldKey] = (getFieldValue($form, parentFieldKey) || "").toString())
        );
        relations.push(additionalRelation);

        const onParentChange = function (initialRender: boolean) {
            const lastChildValue = <string>$child.val();

            // Узнаем значения всех родительских полей
            // Объект parentValues содержит значения родительских элементов в виде объекта {parentFieldKey1: value1, parentFieldKey2: value2}
            let parentValues: { [key: string]: string } = {};
            // Также подготовим флаг, который покажет - нужно ли отображать все child значения? Если найдётся
            // хотя бы одно значения parent элемента отличное от % и '', тогда не все child нужно отображать, будем фильтровать
            let showAllChildValues = true;
            parentFieldKeys.forEach(fieldKey => {
                const parentValue = (getFieldValue($form, fieldKey, "Select") || "").toString();
                if (parentValue !== "%" && parentValue !== "" && parentValue !== "0") {
                    parentValues[fieldKey] = parentValue;
                    showAllChildValues = false;
                }
            });

            if (showAllChildValues) {
                // Отображаем все возможные значения в дочернем элементе
                $child.html(childOriginalHtml);
            } else {
                // Если попали в этот else, значит в каком-то parent что-то выбрно - нужно отобразить не все возможные
                // значения, а лишь некоторые
                const validOptionValues = _(relations)
                // Теперь отфильтруем из массива relations только те кортежи, в которых значения для родительских элементов
                // удовлетворяют значениям parentValues
                    .filter(parentValues)
                    // Из полученного массива (сосотящего из объектов) выберем только колонку со значением
                    // для текущего поля
                    .map(childFieldKey)
                    .value();

                const $tmp = $("<select></select>");
                // Заполним его исходными элементами
                $tmp.html(childOriginalHtml);
                // Пройдём по всем option внутри клона и удалим ненужные
                $("option", $tmp).each(function (this: HTMLOptionElement) {
                    if (
                        this.value !== "%" &&
                        this.value !== "" &&
                        this.value !== "-1" &&
                        _.indexOf(validOptionValues, this.value) === -1
                    ) {
                        $(this).remove();
                    }
                });

                // Теперь переберём все пустые группы
                $tmp.children("optgroup:empty").remove();

                $child.empty().append($tmp.children());
            }

            if (!initialRender) {
                // Если в Child выпадающем списке остался всего один элемент - выберем его автоматически
                const $notEmptyOptions = $child.find("option").not('[value=""]');
                if (
                    $notEmptyOptions.length === 1 &&
                    ($notEmptyOptions.closest("optgroup").length == 0 ||
                        $notEmptyOptions.closest("optgroup").attr("label") != "Inactive")
                ) {
                    $child.val($notEmptyOptions.val()!);
                } else {
                    // Вернём текущее значение child до всех манипуляций с вложенными элементами
                    // Если такой элемент есть среди валидных!
                    if (lastChildValue !== "" && $child.find(`option[value="${lastChildValue}"]`).length === 1) {
                        $child.val(lastChildValue);
                    } else {
                        if (!$child.hasClass("select-select2multiple")) {
                            $child.prop("selectedIndex", 0);
                        }
                    }
                }

                if (lastChildValue !== $child.val()) {
                    $child.trigger("change");
                }
            } else {
                // При начальном рендрингде вернём текущее значение child до всех манипуляций с вложенными элементами
                $child.val(initChildValue);
            }
        };

        // Навесим обработчики on change на все родительские элементы
        parentFieldKeys.forEach(fieldKey => {
            getFieldNode($form, fieldKey).on("change", () => onParentChange(false));
        });

        // В самом начале после загрузки страницы переинициализируем дочерний список на основе значения родитея
        onParentChange(true);
    });
}

let sortablejsLoaded = false;

export function loadSortableJs() {
    return new Promise(function (resolve) {
        if (sortablejsLoaded) {
            // Если Sortable.min.js уже был загружен, то мгновенно резолвим промис и выходим
            resolve();
            return;
        }

        // Асинхронно загружаем js файл библиотеки, которая отвечает на sortable функциональность
        let script = document.createElement("script");
        script.src = "lightpublic/js/Sortable.min.js";
        script.type = "text/javascript";
        script.async = true;
        script.onload = () => {
            // Как только успешно загрузили, заново вызываем вызываем showLayoutDialog()
            sortablejsLoaded = true;
            resolve();
        };
        document.getElementsByTagName("head")[0].appendChild(script);
    });
}

// Вспомагательная функция - вычисляет css свойства для индикатора активного таба
// Принимает активную ссылку
function calcTabsIndicatorCss($a: JQuery) {
    const $li = $a.closest("li");
    const $ul = $li.closest("ul");
    return {
        left: $li.position().left + $ul.scrollLeft()!,
        width: $li.outerWidth(),
        backgroundColor: $a.css("color")
    };
}

function rerenderTabsIndicator($root: JQuery) {
    $root
        .find("ul.tabs")
        .not(".nojs")
        .each(function (this: HTMLUListElement) {
            const $ul = $(this);
            const activeHash = "#" + $ul.attr("data-active");

            // Анимируем индикатор
            const {left, width, backgroundColor} = calcTabsIndicatorCss($ul.find(`a[href="${activeHash}"]`));
            $ul
                .children(".indicator")
                .css({backgroundColor})
                .animate({left, width});
        });
}

function initTabs($root: JQuery) {
    $root
        .find("ul.tabs")
        .not(".nojs")
        .each(function (this: HTMLUListElement) {
            const $ul = $(this);
            let activeHash: string;

            // Если в ul в data атрибуте задан активный таб, то используем его в качестве активного в самом начале
            // и тут же проверим, что такой таб вообще существует
            if ($ul.attr("data-active") && $ul.find(`a[href="#${$ul.attr("data-active")}"]`).length !== 0) {
                activeHash = "#" + $ul.attr("data-active");
            } else {
                // Если жэ data-атрибут не задан, то используем первый попавшийся таб
                activeHash = $ul.find("a:first").prop("hash");
                // Сохраним значение активного таба в data атрибуте, чтобы из любого js скрипта можно было его получить
                $ul.attr("data-active", activeHash.substring(1));
            }

            // Пройдём по всем ссылкам, скроем все слои, кроме активного
            // Кроме этого нужно найти самый большой по вертикали слой и всем остальным слоям добавить min-height,
            // чтобы не было дёрганий по вертикали при переключении табов
            const links = <Array<any>>$ul.find("a").get();
            const maxHeight = Math.max.apply(null, links.map(link => $(link.hash).outerHeight()));
            links.forEach(link => {
                const $div = $(link.hash);
                if (link.hash !== activeHash) {
                    // Это не активный таб. Найдём div, который соответсвует этой ссылке и скроем его
                    $div.hide();
                } else {
                    // Если же таб активный, на всякий случай сделаем show. В большестве случаев начальная вёрстка содержит
                    // все табы в display: block, т.е. этот show ничего не сделает. Но есть модули, когда удобнее изначально
                    // на сервере скрыть все табы, сделав по умолчанию display: none в css файле - тогда при инициализации
                    // для активного таба обязательно нужно убрать этот display: none - запустив show()
                    $div.show();
                }

                // Всем слоям присавиваем свойство minHeight равное максимально найденной высоте
                $div.css({minHeight: maxHeight});
            });

            // Создадим плавающий индикатор под табами
            let $indicator = $('<div class="indicator"></div>')
                .appendTo($ul)
                .css(<JQuery.PlainObject>calcTabsIndicatorCss($ul.find(`a[href="${activeHash}"]`)));

            $ul.on("click", "a", function (this: HTMLAnchorElement, e: any) {
                e.preventDefault();
                const $a = $(this);

                $(activeHash).hide();
                activeHash = this.hash;

                const $currentDiv = $(activeHash);
                $currentDiv.show();

                // Анимируем индикатор
                const {left, width, backgroundColor} = calcTabsIndicatorCss($a);
                $indicator.css({backgroundColor}).animate({left, width});

                $ul.trigger("tabs:activate", activeHash);

                // Сохраним значение активного таба в data атрибуте, чтобы из любого js скрипта можно было его получить
                $ul.attr("data-active", activeHash.substring(1));

                // Автоматически сохраним в базу в настройки пользователя, если у данного ul задан id
                setUserParam(getModule() + ":tabs:" + ($ul.attr("id") || ""), activeHash.substring(1));

                // Если внутри таба были вложенные табы, то в них надо бы обновить их собственные индикаторы активности
                // Запустим специальный метод, который надёт все вложенные табы и обновит индикаторы
                rerenderTabsIndicator($currentDiv);
            });
        });
}

export function initTooltip($root: JQuery) {
    $root.find(".toggle-tooltip").tooltip();
}

export function showFlashMessagess(messagess: Array<{ type: string; msg: string }>): void {
    // Проверим, есть ли сейчас какой-то активный диалог на экране?
    if ($(".lightdialogoverlay").length > 0) {
        // Выводим сообщения в виде тостов
        showToastMessagess(messagess);
    } else {
        // noinspection TypeScriptValidateTypes
        $("#flash-messages")
            .fadeOut(0)
            .empty()
            .append(
                messagess
                    .map(
                        ({type, msg}) => `<div class="alert alert-${type === "error" ? "danger" : type}">${msg}</div>`
                    )
                    .join("")
            )
            .fadeIn();
    }
}

export function showToastMessagess(messagess: Array<{ type: string; msg: string }>): void {
    messagess.forEach(typeAndMsg => {
        toast(typeAndMsg.type, typeAndMsg.msg);
    });
}

let $toastContainer: JQuery | null = null;

export function toast(type: string, msg: string): void {
    if ($toastContainer === null) {
        $toastContainer = $('<div id="toast-container">').appendTo(document.body);
    }

    // Create toast
    const $toast = $("<div></div>")
        .addClass("toast alert-" + type)
        .css({top: "35px", opacity: 0})
        .html(msg)
        .appendTo($toastContainer)
        .animate({
            top: "0px",
            opacity: 1
        });

    setTimeout(() => {
        $toast.animate({marginTop: "-40px", opacity: 0}, 400, () => $toast.remove());
    }, 4000);
}

export function toastInfo(msg: string) {
    toast("info", msg);
}

export function toastSuccess(msg: string) {
    toast("success", msg);
}

export function toastWarning(msg: string) {
    toast("warning", msg);
}

export function toastError(msg: string) {
    toast("error", msg);
}

let ajaxCounter = 0;

$(document)
    .ajaxSend((_, XMLHttpRequest, ajaxOptions) => {
        ajaxCounter++;
        // Отображаем глобальный прогресс бар (если его ещё нет)
        if (
            !document.getElementById("globalprogress") &&
            (ajaxOptions.headers === undefined ||
                (ajaxOptions.headers !== undefined && ajaxOptions.headers["X-No-Loading-Ajax"] === undefined))
        ) {
            $('<div id="globalprogress"><div></div></div>').appendTo("body");
        }
        if (ajaxOptions.type === "POST") {
            XMLHttpRequest.setRequestHeader("X-CSRF-Token", (window as any)._lightCsrfToken);
        }
    })
    .ajaxComplete((_, XMLHttpRequest) => {
        ajaxCounter--;
        if (ajaxCounter < 0) {
            ajaxCounter = 0;
        }
        if (ajaxCounter === 0) {
            // Скрываем глобальный прогресс бар
            $("#globalprogress").remove();
        }
        if (XMLHttpRequest.getResponseHeader) {
            if (XMLHttpRequest.getResponseHeader("x-light-redirect")) {
                document.location.href = XMLHttpRequest.getResponseHeader("x-light-redirect")!;
            } else if (XMLHttpRequest.getResponseHeader("x-light-redirect-post")) {
                //От сервера поступила инструкцию перенаправить пользователя на другюу страницу используя
                //POST запрос - это может быть создание оплаты из большого количества счетов, например
                const $form = $("<form></form>").attr({
                    method: "post",
                    action: XMLHttpRequest.getResponseHeader("x-light-redirect-post")
                });

                const data: { [key: string]: any } = parseJson(XMLHttpRequest.responseText);
                for (let key in data) {
                    $form.append(
                        $("<input>").attr({
                            type: "hidden",
                            name: key,
                            value: data[key]
                        })
                    );
                }

                $form
                    .append(`<input type="hidden" name="csrf_token" value="${(window as any)._lightCsrfToken}">`)
                    .appendTo("body")
                    .submit();
            }

            //Проверим наличие header заголовка, в котором находится js скрипт для выполнения
            if (XMLHttpRequest.getResponseHeader("x-light-jseval")) {
                eval(XMLHttpRequest.getResponseHeader("x-light-jseval")!);
            }

            //Проверим наличие header заголовка, в котором находятся flash msg
            if (XMLHttpRequest.getResponseHeader("x-light-flashmsg")) {
                showFlashMessagess(parseJson(XMLHttpRequest.getResponseHeader("x-light-flashmsg")!, []));
            }

            //Иногда flash msg так много, что они не помещаются в header заголовок, поэтому приходят в теле ajax ответа
            if (XMLHttpRequest.getResponseHeader("x-light-flashmsg-body")) {
                showFlashMessagess(parseJson(XMLHttpRequest.responseText, []));
            }

            if (XMLHttpRequest.getResponseHeader("x-light-toastmsg")) {
                showToastMessagess(parseJson(XMLHttpRequest.getResponseHeader("x-light-toastmsg")!, []));
            }

            if (XMLHttpRequest.getResponseHeader("x-light-redirect-blank")) {
                window.open(XMLHttpRequest.getResponseHeader("x-light-redirect-blank")!, "_blank");
            }
        }
    });

//Навесим стандартные делегаты на весь документ
const $body = $(document.body);

$body.on("focus", "input[type=text].input-date:not(.hasDatepicker)", function (this: HTMLInputElement) {
    $(this).datepicker();
});

$body.on("change", "input[type=date]", function (this: HTMLInputElement) {
    $(this).toggleClass("empty-date", this.value === "");
});

/**
 * Метод вызывается из addStandartListenters
 * Основная задача: пройти по всем input[type=date] на странице и сделать две операции:
 * 1) если браузер не поддерживает type=date нативно, то преобазовать value в beauty формат и сменить атрибут на type=text
 * // https://stackoverflow.com/questions/18020950/how-to-make-input-type-date-supported-on-all-browsers-any-alternatives
 * 2) если же поддерживает, то для пустых полей ввода добавить класс empty-date (чтобы сделать более незаметными плейсхолдер dd.mm.yyyy)
 * @param {JQuery} $root
 */
function fallbackInputTypeDate($root: JQuery) {
    $root.find("input[type=date]").each(function (this: HTMLInputElement) {
        if (this.type !== "date") {
            this.setAttribute("type", "text");
            if (this.value !== "") {
                this.value = getBeautyDate(this.value);
            }
        } else if (this.value === "") {
            $(this).addClass("empty-date");
        }
    });
}

$body.on("focus", "input[type=text].input-datetime:not(.hasDatepicker)", function (this: HTMLInputElement) {
    const $input = $(this);
    ($input as any).datetimepicker({
        timepickerHrs: true,
        timepickerMin: true,
        allowTimes: false,
        step: 1,
        dayOfWeekStart: 1,
        format: window["_lightDateTimeFormat"],
        lang: window["_lightLang"]
    });
    //$input.blur();
    //$input.focus();
});

// Проверим, есть ли в документе элементы SelectAddNew
$body.on("change keyup", "select.select-addnew", function (this: HTMLSelectElement) {
    $("#" + this.id + "New").toggleClass("display-none", this.value !== "+");
});

// Логируем все клики по ссылкам mailto: чтобы собрать статистику, какие ссылки реально используются
$body.on("click", "a[href^='mailto:']", function (this: HTMLAnchorElement) {
    $.post("?a=Core&ps=Metric", {
        ActionType: "mailto",
        TimeMs: 0,
        Payload: this.href
    });
});

$body.on("keypress", "input.input-int", function (this: HTMLInputElement, e: any) {
    const code = e.which;
    //Опием разрешенные зачения и возьмём отрицание - если отрицание true - нужно предотвратить вставку
    if (
        !(
            (code >= 48 && code <= 57) /* цифры */ ||
            code === 8 /* backspace */ ||
            code === 13 /* enter */ ||
            code === 0 ||
            e.ctrlKey ||
            e.metaKey
        )
    ) {
        e.preventDefault();

        //Если это был знак минус, который мы только что заблокировали, вставим его вручную в начало строки
        if (code === 45) {
            if (this.value.substring(0, 1) !== "-") {
                this.value = "-" + this.value;
            }
        }
    }
});
$body.on("keypress", "input.input-float", function (this: HTMLInputElement, e: any) {
    const code = e.which;
    //Опием разрешенные зачения и возьмём отрицание - если отрицание true - нужно предотвратить вставку
    if (
        !(
            (code >= 48 && code <= 57) /* цифры */ ||
            code === 45 /* минус */ ||
            code === 46 /* точка */ ||
            code === 44 /* запятая */ ||
            code === 8 /* backspace */ ||
            code === 13 /* enter */ ||
            code === 0 ||
            e.ctrlKey ||
            e.metaKey
        )
    ) {
        e.preventDefault();
    }

    //Если это была точка или запятая, разрешим вставку только если десятичный разделитель ещё не присутсвует внутри строки
    if (code === 46 /* точка */ || code === 44 /* запятая */) {
        if (this.value.indexOf(".") !== -1 || this.value.indexOf(",") !== -1) {
            e.preventDefault();
        }
    }
});

$body.on("click", "a.showhide", function (this: HTMLAnchorElement, e: JQuery.Event) {
    e.preventDefault();
    const $link = $(this);

    // Сначала пытаемся найти полный текст слева от ссылки
    const $full = $link.siblings(".truncated-full");
    const $part = $link.siblings(".truncated-part");

    let newFullTextState: "showed" | "hidden";
    newFullTextState = $full.hasClass("display-none") ? "showed" : "hidden";

    $full.toggleClass("display-none", newFullTextState === "hidden");
    $part.toggleClass("display-none", newFullTextState === "showed");

    // Ещё можно поменять текс на ссылке, если ссылка содержала слово Show или Hide
    if (this.innerHTML === "Show" && newFullTextState === "showed") {
        this.innerHTML = "Hide";
    }
    if (this.innerHTML === "Hide" && newFullTextState === "hidden") {
        this.innerHTML = "Show";
    }
});

$body.on("click", 'a[href$="#ajax"], a[href$="#ajax-post"]', function (this: HTMLAnchorElement, e: any) {
    if (this.hash !== "#ajax-post" && (e.ctrlKey || e.shiftKey || e.metaKey)) {
        // Если это не POST ссылка (т.е. ссылка для обычного GET запроса)
        // и пользователь зажал Ctrl или Shift - значит пользователь хочет открыть ссылку в новом окне
        // в этом случае не будем делать e.preventDefault(); чтобы позволить браузеру открыть новое окно по ссылке
        // Естественно на сервер придёт не ajax запрос, а обычный GET запрос и этот момент нужно учесть
        // при разработке функции на серверной стороне
        return;
    }

    e.preventDefault();
    const $link = $(this);
    // Проверим, возможно ajax запрос от этой ссылки сейчас в процессе? Тогда второй запрос отравлять не нужно!
    if ($link.hasClass("inprogress")) {
        return;
    }

    // Добавим специальный класс, чтобы невозможно было кликнуть по этой ссылке второй раз, пока она в делает запрос
    $link.addClass("inprogress");

    let $newImg: JQuery;
    const $icon = $link.find("img, i.fa");
    if ($icon.length > 0) {
        // Внутри этой ссылки могла быть иконка material icon или font awsome
        $icon.addClass("visibility-hidden");
        $newImg = $("<img>")
            .prop("src", "lightpublic/i/circle.gif")
            .css({position: "absolute", marginTop: 3, marginLeft: 3});
        $link.prepend($newImg);
    }
    const ajaxSettings: { [key: string]: any } = {method: this.hash === "#ajax-post" ? "post" : "get"};

    // Если у ссылки был атрибут post-data, додавим данные к post запросу (в атрибуте должен лежать json)
    const postDataAttr = this.getAttribute("data-post");
    if (postDataAttr) {
        const postData: { [key: string]: any } | null = parseJson(postDataAttr, null);
        if (postData) {
            ajaxSettings["data"] = postData;
        }
    }
    $.ajax(this.href, ajaxSettings).always(() => {
        // После завершения ajax запроса убираем со ссылки специальный класс, который предовтвращал двойное нажатие
        $link.removeClass("inprogress");

        // Если внутри ссылки были иконки, то нужно их вернуть, убрав спинер
        if ($newImg) {
            $newImg.remove();
        }
        if ($icon.length > 0) {
            $icon.removeClass("visibility-hidden");
        }
    });
});
$body.on("click", 'a[href$="#post"], a[data-post]:not([href$="#ajax-post"])', function (
    this: HTMLAnchorElement,
    e: JQuery.Event
) {
    e.preventDefault();

    const $form = $("<form></form>")
        .attr({
            method: "post",
            action: this.href
        })
        .append(`<input type="hidden" name="csrf_token" value="${(window as any)._lightCsrfToken}">`);

    const postDataAttr = this.getAttribute("data-post");
    if (postDataAttr) {
        const postData: { [key: string]: string } | null = parseJson(postDataAttr, null);
        if (postData) {
            for (let key in postData) {
                $("<input></input>")
                    .attr("type", "hidden")
                    .attr("name", key)
                    .attr("value", postData[key])
                    .appendTo($form);
            }
        }
    }

    $form.appendTo("body").submit();
});

interface AcParams {
    sourceJsFunction?: string;
    source?: string;
    onSelectJsFunction?: string;
    resultFormatterJsFunction?: string;
    width?: number;
    delimChar?: string;
}

//Новый подход к Autocomplete: любое поле может быть Autocomplete, если у него поередлён атрибут data-ac
$body.on("focus", "input[data-ac], textarea[data-ac]", function (this: HTMLInputElement) {
    const $input = $(this);
    //Проверим, что у этого элемента ещё нет дата-атрибута ui-autocomplete
    if ($input.data("ui-autocomplete")) {
        //Если же он есть, значит на данном элементе уже инициализировался виджет Autocomplete
        return;
    }

    //Сформируем конфигурационный объект для виджета на основе параметров переданных в атрибуте data-ac
    let config: any = {};
    const acParams = <AcParams>parseJson($input.attr("data-ac")!);
    if (acParams.sourceJsFunction) {
        const sourceFn = eval(acParams.sourceJsFunction);
        config.source = (request: any, responseCallback: any) => {
            const url = sourceFn() + "&term=" + encodeURIComponent(request.term);
            $.ajax(url, {dataType: "json", success: responseCallback});
        };
    } else if (acParams.source) {
        if (acParams.source.substring(0, 1) === "?") {
            //В качестве параметра source на php стороне был определён полный url для запроса начинающийся со знака ?
            config.source = acParams.source;
        } else {
            //В качестве параметра source на php стороне была задана лишь таблица, будем делать запрос к модулю core
            config.source = "?a=Core&ac=core&source=" + acParams.source;
        }
    }

    if (acParams.onSelectJsFunction) {
        const selectFn = eval(acParams.onSelectJsFunction);
        config.select = function (this: any, _: any, ui: any) {
            selectFn.call(this, ui.item);
        };
    }

    if (acParams.width) {
        config.width = acParams.width;
    }

    if (acParams.delimChar) {
        config.delimChar = acParams.delimChar;
    }

    //Наконец применим виджет к текущему полю ввода
    $input.autocomplete(config);

    if (acParams.resultFormatterJsFunction) {
        //Хак для изменения форматирования вывода подсказок: патчим метод _renderItem в виджете
        const resultFormatterFn = eval(acParams.resultFormatterJsFunction);
        (<{ _renderItem: any }>$input.data("ui-autocomplete"))._renderItem = function (this: any, ul: any, item: any) {
            return (
                $("<li>")
                //.data("item.autocomplete", item)
                /* вызываем определённый пользователем метод resultFormatterFn в scope = $(inputEl) */
                    .append("<div>" + resultFormatterFn.call(this.element, item) + "</div>")
                    .appendTo(ul)
            );
        };
    }
});

$body.on("submit", "form", function (this: HTMLFormElement, e: JQuery.Event) {
    if (!this.action) {
        // Если у этой формы нет атрибута action, занчит это какая-то хитрая форма, которая обратабывается
        // исключительно javascript кодом, не будем её трогать
        return;
    }

    const $form = $(this);
    const $formSaveButtons = $form.find(".btn-save, button[type=submit], input[type=submit]");
    $formSaveButtons.prop("disabled", true);

    if (this.action.substr(-5, 5) === "#ajax" && $form.closest(".lightdialog__content").length === 0) {
        // action этой формы оканчивается на #ajax - это признак того, что форму нужно отрпавить ajax запросом
        // и форма не находится внутри какого либо диалогового окна
        // (ajax формы диалоговых окон обрабатываются внутри Dialog.ts)
        e.preventDefault();

        //Отправляем POST запрос на сервер. В случае ошибки - нужно разблокировать кнопки
        //Если всё успешно - тут мы ничего не делаем. Сервер сам должен решить что делать, например вернуть x-light-redirect
        const url = this.action;
        const postData = $form.serialize();
        $.post(url, postData)
            .done((_, __, jqXHR) => {
                if (!jqXHR.getResponseHeader("x-light-redirect")) {
                    // Если от сервера не пришла инструкция с редиректом, то разблокируем кнопку
                    $formSaveButtons.prop("disabled", false);
                }
            })
            .fail(() => {
            $formSaveButtons.prop("disabled", false);
        });
    }
});

function initNextPrevLinkKeypress(keys: string) {
    listener.simple_combo(keys, function (e: KeyboardEvent) {
        const targetTag = (<Element>e.target).tagName.toLowerCase();
        if (targetTag !== "input" && targetTag !== "textarea") {
            const selector = keys === "ctrl right" ? ".next-link" : ".prev-link";
            const $link = $("#page-head-right").find(selector);
            if ($link.length > 0) {
                document.location.href = $link.attr("href")!;
                return false;
            }
        }
        //Возвращаем true, чтобы выполниеть default действие на этом хоткее
        return true;
    });
}

initNextPrevLinkKeypress("ctrl right");
initNextPrevLinkKeypress("ctrl left");

export function setUserParam(name: string, value: string, successCallback?: (confirmedValue: string) => void) {
    $.post("index.php?a=Core&ps=userparam", {param: name, value: value}, successCallback || null, "text");
}

export function getUserParam(name: string, successCallback: (value: string) => void) {
    $.get("index.php?a=Core&ps=userparam", {param: name}, successCallback, "text");
}

export function getModule() {
    return (window as any)._lightModule;
}

/**
 * Возвращает указанный параметр, который опередлён в json сгенерированном на стороне сервера в глобальном объекте window
 */
export function getPageParam(paramName: string) {
    return (window as any)._lightPageParams[paramName];
}

export function blockButtons(selector: any, block: boolean) {
    $(selector)
        .blur()
        .prop("disabled", block);
}

export function parseJson(stringJson: string, returnOnError: any = {}) {
    try {
        return JSON.parse(stringJson);
    } catch (x) {
        //Если неудачно распарсили - возвращается пустой объект или то, что передано во втором параметре
        return returnOnError;
    }
}

export function buildMapFromObj(obj: { [key: string]: any }): Map<string, any> {
    const map = new Map<string, any>();
    Object.keys(obj).forEach(key => {
        map.set(key, obj[key]);
    });
    return map;
}

export function buildObjFromMap(m: Map<string, any>): { [key: string]: any } {
    const obj: { [key: string]: any } = {};
    m.forEach((val, key) => {
        obj[key] = val;
    });
    return obj;
}

/**
 * Округляет до двух знаков полсе запятой
 */
export function round(value: number | string, precision: number = 2): number {
    // Оставляем только 2 знака после запятой (или сколько указано в precision)
    const multiplier = Math.pow(10, precision);
    return Math.round(multiplier * myParseFloat(value)) / multiplier;
}

/**
 * Отличие от стандартного метода myParseInt - если не удалось распарсить, этот метод возвращает 0
 */
export function myParseInt(value: any): number {
    if (value === undefined || value === null) {
        return 0;
    }

    if (typeof value === "string") {
        value = parseInt(value.replace(/\s/g, ""), 10);
    }
    if (isNaN(value)) {
        value = 0;
    }

    return value;
}

/**
 * Отличие от стандартного метода myParseFloat - если не удалось распарсить, этот метод возвращает 0
 */
export function myParseFloat(value: any): number {
    //Если пользователи вводили запятую вместо точки, заменим
    //Также удаляем все пробелы
    if (value === undefined || value === null) {
        return 0;
    }

    if (typeof value === "string") {
        value = parseFloat(value.replace(",", ".").replace(/\s/g, ""));
    }

    if (isNaN(value)) {
        value = 0;
    }

    return value;
}

export function getBeautyMoney(num: any, short = false, currencyIso: string = "", zeroEmpty = false): string {
    num = myParseFloat(num);
    if (zeroEmpty && num == 0) {
        return "";
    }

    num = round(num);

    // Запомним знак
    const minus = num < 0 ? "-" : "";

    // Целая часть, обязательно сначала взять abs
    const leftStr = Math.floor(Math.abs(num)).toString();

    // Вставим в целую часть пробелы через каждые три символа
    // Для этого каждые три символа из левой части запишем в массив leftBlocks
    const leftBlocks = [];
    // Узнаем кол-во блоков
    const rounds = Math.ceil(leftStr.length / 3);
    // Идём справа налево, вырезая подстроки по 3 символа
    let start;
    let len;
    for (let i = 0; i < rounds; i++) {
        //Начальный символ блока
        start = leftStr.length - 3 * (i + 1);
        //Длина блока
        if (start >= 0) {
            //Не вышли за границу строки слева, можем вырезать полный блок 3 символа
            len = 3;
        } else {
            //Вышли за границу строки слева. Переменная start отрицательна и содержит таким образом кол-во символов, на которое вылезли
            len = 3 + start; //на самом деле здесь вычитание, т.к. переменная start отрицательная
            start = 0;
        }

        //Записываем очередной блок в начало массива
        leftBlocks.unshift(leftStr.substr(start, len));
    }

    // Подготовим выходной результат
    let result = '<span class="nobr">' + minus + leftBlocks.join("&nbsp;");

    // Найдём правую дробную часть
    let rightStr = "";
    // Обязательно взять abs, чтобы исключить знак минус - он может помещать при выделении дробной части
    const num100 = Math.abs(Math.round(num * 100)).toString();
    // Если после умножения на 100 число получислоь из одной цифры, значит изначально это было что-то типа 0.0x
    if (num100.length == 1) {
        rightStr = "0" + num100;
    } else {
        // substring принимает пораметрами startIndex, endIndex - стандартная функция в отличие от substr
        rightStr = num100.substring(num100.length - 2);
    }

    if (!short || rightStr != "00") {
        result += '<span class="cnt">.' + rightStr + "</span>";
    }

    if (currencyIso) {
        result += " " + currencyIso;
    }

    result += "</span>";

    return result;
}

export function getBeautyMoneyShort(num: any, currencyIso: string = ""): string {
    return getBeautyMoney(num, true, currencyIso);
}

export function getBeautyDate(date: any, fullyear: boolean = true): string {
    if (date === undefined) {
        return "";
    }

    if (typeof date === "string" && (date == "" || date == "0000-00-00")) {
        return "";
    }

    const mysqlDate = getMySQLDate(date);
    if (mysqlDate == "") {
        return "";
    }

    let tdate = mysqlDate;
    let time = null;
    if (date.length == 19) {
        let dateTime = mysqlDate.split(" ");
        tdate = dateTime[0];
        time = dateTime[1];
    }

    const parts = tdate.split("-");
    if (parts.length !== 3) {
        return date;
    }
    let year = parts[0];
    const month = parts[1];
    const day = parts[2];

    if (!fullyear) {
        year = year.substring(2);
    }

    let dateString = day + "." + month + "." + year;

    if (time != null) {
        dateString += " " + time;
    }

    return dateString;
}

/**
 * Принимает объект типа Date или строку DD.MM.YYYY и возвращает строку в формате MySQL 'YYYY-MM-DD';
 */
export function getMySQLDate(date: any): string {
    if (date === null || (typeof date === "string" && (date == "" || date == "0000-00-00"))) {
        return "";
    }

    if (typeof date !== "object") {
        if (date[2] == ".") {
            //Это формат DD.MM.YYYY
            if (date.length == 10) {
                date = date.split(".");
                return date[2] + "-" + date[1] + "-" + date[0];
            } else if (date.length == 19) {
                //Это формат DD.MM.YYYY HH:mm:ii
                const datetime = date.split(" ");
                date = datetime[0].split(".");
                return date[2] + "-" + date[1] + "-" + date[0] + " " + datetime[1];
            }
        }

        //Это неизвестный формат строки, вернём как есть и будь что будет!
        return date;
    }

    const year = date.getFullYear();
    let month = date.getMonth() + 1;
    let day = date.getDate();

    if (month < 10) {
        month = "0" + month.toString();
    }
    if (day < 10) {
        day = "0" + day.toString();
    }

    return year + "-" + month + "-" + day;
}

export function nl2br(string: any): string {
    return string.toString().replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, "$1" + "<br>" + "$2");
}

export function htmlspecialchars(html: string): string {
    const div = document.createElement("div");
    div.appendChild(document.createTextNode(html));
    return div.innerHTML;
}

let __rootCounter = 0;
let __cacheFieldNodes: { [key: string]: JQuery } = {};

export function getFieldValue($root: JQuery, fieldName: string, type?: string): string | number | null {
    const $node = getFieldNode($root, fieldName, type);
    if ($node.length === 0) {
        return null;
    }

    return getNodeValue($node, type);
}

export function getFieldNode($root: JQuery, fieldName: string, type?: string): JQuery {
    //noinspection TypeScriptValidateTypes
    let rootUniqueCounter = $root.data("__lightRootCounter");
    if (!rootUniqueCounter) {
        __rootCounter++;
        rootUniqueCounter = __rootCounter;
        //noinspection TypeScriptValidateTypes
        $root.data("__lightRootCounter", rootUniqueCounter);
    }
    let cacheKey = rootUniqueCounter + "-" + fieldName + "-" + type;
    if (!__cacheFieldNodes[cacheKey]) {
        switch (type) {
            case "Week":
                __cacheFieldNodes[cacheKey] = $root.find(
                    `input[name="${fieldName}[Week]"], input[name="${fieldName}[Year]"]`
                );
                break;

            case "CheckboxSet":
                __cacheFieldNodes[cacheKey] = $root.find(`input[name="${fieldName}[]"]`);
                break;

            case "MoneyWithCurrency":
                __cacheFieldNodes[cacheKey] = $root.find(
                    `input[name="${fieldName}[Amount]"], select[name="${fieldName}[CurrencyISO]"]`
                );
                break;

            case "Select":
                let $node = $root.find(`select[name="${fieldName}"]`);
                if ($node.length === 0) {
                    // Попробуем найти такое же поле, но с учётом возможного multiple, т.е. с [] в конце имени
                    $node = $root.find(`select[name="${fieldName}[]"]`);
                }
                __cacheFieldNodes[cacheKey] = $node;
                break;

            case "Select2Multiple":
            case "Select2ChildMultiple":
                __cacheFieldNodes[cacheKey] = $root.find("#" + fieldName);
                break;

            default:
                __cacheFieldNodes[cacheKey] = $root.find(`[name="${fieldName}"]`);
                break;
        }

        // если Node по-прежнему не найден, попробуем найти label с атрибутом for = fieldName
        if (__cacheFieldNodes[cacheKey].length == 0) {
            __cacheFieldNodes[cacheKey] = $root
                .find(`label[for="${fieldName}"]`)
                .closest("tr")
                .children("td");
        }
    }

    return __cacheFieldNodes[cacheKey];
}

export function getNodeValue($node: JQuery, type?: string): string | number {
    let value;

    //Если тип не передан явно, то попробуем определить его по data-type атрибуту в теге
    if (type === undefined) {
        type = $node.attr("data-type");
    }

    switch (type) {
        case "Week":
            //Для фильтра типа Week нужно собразть значения из двух полей и объединить через дефис
            value = $node.eq(1).val() + "-" + $node.eq(0).val();
            if (value === "-") {
                value = "";
            }
            break;

        case "CheckboxSet":
            //Для фильтра типа CheckboxSet нужно собрать значения из всех отмеченных чекбоксов
            // через вертикальный разделитель
            value = $node
                .toArray()
                .filter((el: HTMLInputElement) => el.checked)
                .map((el: HTMLInputElement) => el.value)
                .join("|");
            if (value !== "") {
                value = "|" + value + "|";
            }
            break;

        case "Select":
            value = $node.val();
            if (Array.isArray(value)) {
                value = value.filter((v: any) => v !== "");
                if (value.length > 0) {
                    value = "|" + value.join("|") + "|";
                } else {
                    value = "%";
                }
            }
            break;

        case "Select2Multiple":
        case "Select2ChildMultiple":
            value = $node.val();
            if (value) {
                value = (value as string[]).join("|");
                if (value !== "") {
                    value = "|" + value + "|";
                }
            }
            if (_.isEmpty(value)) {
                value = "%";
            }
            break;

        case "Checkbox":
            value = $node.prop("checked") ? 1 : 0;
            break;

        case "Int":
        case "Color":
            value = myParseInt($node.val());
            break;

        case "Float":
        case "Money":
            value = myParseFloat($node.val());
            break;

        case "MoneyWithCurrency":
            //Для MoneyWithCurrency нужно вернуть строку где сначала идёт валюта, а потом сумма через пробел
            value = $node.eq(1).val() + " " + myParseFloat($node.eq(0).val());
            break;

        case "Date":
        case "DateTime":
            if ($node.prop("type") === "date") {
                // это нативное поле даты, возвращаем значение как есть, оно уже в формате YYYY-MM-DD
                value = $node.val();
            } else {
                value = getMySQLDate($node.val());
            }
            break;

        default:
            value = $node.val();
    }

    return value as string | number;
}

export function parseTextValue(text: string, type: string): string | number {
    let value;
    switch (type) {
        case "Checkbox":
            value = text.indexOf("Yes") != -1 ? 1 : 0;
            break;

        case "Int":
            value = myParseInt(text);
            break;

        case "Float":
        case "Money":
            value = myParseFloat(text);
            break;

        case "MoneyWithCurrency":
            if (text && text.search(" ") !== -1) {
                const parts = text.split(" "); // "100 123.22 USD"
                const currencyIso = parts.pop();
                value = currencyIso + " " + myParseFloat(parts.join(""));
            } else {
                value = "";
            }
            break;

        case "Date":
            value = getMySQLDate(text);
            break;

        default:
            value = text;
    }

    return value;
}

export function setFieldValue($root: JQuery, fieldName: string, newValue: any, type?: string): void {
    const $node = getFieldNode($root, fieldName, type);

    if ($node.length > 0) {
        setNodeValue($node, newValue, type);
    }
}

export function setNodeValue($node: JQuery, newValue: any, type?: string, triggerChange = true): void {
    switch (type) {
        case "Week":
            const parts1 = newValue.split("-");
            $node.eq(1).val(!parts1[0] || parts1[0] == "0000" ? "" : parts1[0]);
            $node.eq(0).val(!parts1[1] || parts1[1] == "00" ? "" : parts1[1]);
            break;

        case "Checkbox":
            newValue = newValue === 1 || newValue === "1" || newValue === true;
            $node.prop("checked", newValue);
            break;

        case "CheckboxSet":
            //Пройдём по всем чекбоксам внутри этого набора (node - это NodeList) и отметим те, которые упоминаются в newValue
            $node.each(function (this: HTMLInputElement) {
                const cbValue = this.value;
                if (newValue.indexOf("|" + cbValue + "|") !== -1) {
                    $node.prop("checked", true);
                } else {
                    $node.prop("checked", false);
                }
            });
            break;

        case "Date":
        case "DateTime":
            if ($node.prop("type") === "date") {
                // Это нативное поле input type=date, присваиваем значение в формате YYYY-MM-DD
                $node.val(newValue);
            } else {
                $node.val(getBeautyDate(newValue, true));
            }
            break;

        case "Month":
            if (newValue.indexOf("-") !== -1) {
                const parts = newValue.split("-");
                newValue = parts[1] + "/" + parts[0];
            }
            $node.val(newValue);
            break;

        case "Float":
            newValue = round(newValue, 4);
            if (newValue == 0) {
                newValue = "";
            }
            $node.val(newValue);
            break;
        case "Money":
            newValue = round(newValue, 2);
            if (newValue == 0) {
                newValue = "";
            }
            $node.val(newValue);
            break;

        case "MoneyWithCurrency":
            let newAmount;
            if ($.isNumeric(newValue)) {
                //Если передано только число - меняем только поле Amount
                newAmount = newValue;
            } else {
                //В противном случае, похоже, получили и валюту и число 'USD 1234.23'
                const parts = newValue.split(" ");
                $node.eq(1).val(parts[0]);
                newAmount = parts[1];
            }

            newAmount = round(newAmount);
            if (newAmount == 0) {
                newAmount = "";
            }
            $node.eq(0).val(newAmount);

            break;

        case "Select":
        case "SelectChild":
            const select2widget: any = ($node.get(0) as any).select2widget;
            if (select2widget) {
                // Это особый select2 описанный в Select2.ts
                select2widget.change(newValue);
            } else {
                if ($node.attr("multiple")) {
                    const parts = newValue.split("|").filter((n: string) => n !== "");
                    $node.val(parts);
                } else {
                    // Проверим, что новое значение есть среди option в этом select элементе
                    if ($node.find(`option[value='${newValue}']`).length > 0) {
                        $node.val(newValue);
                    }
                }
            }
            break;

        case "Select2Multiple":
        case "Select2ChildMultiple":
            const parts = newValue.split("|").filter((n: string) => n !== "");
            $node.val(parts);
            break;

        default:
            $node.val(newValue);
    }

    if (triggerChange && $node.trigger !== undefined) {
        $node.trigger("change");
    }
}

export function onNodeChange(
    $node: JQuery,
    handler: (newValue: string | number, $node: JQuery) => void,
    type?: string
) {
    const changeHandlerWrapper = function () {
        const newValue = getNodeValue($node, type);
        handler(newValue, $node);
    };

    switch (type) {
        case "Checkbox":
        case "CheckboxSet":
            $node.on("click", changeHandlerWrapper);
            break;

        case "Select2Multiple":
        case "Select2ChildMultiple":
            $node.on("change", changeHandlerWrapper);
            break;

        default:
            $node.on("change keyup", changeHandlerWrapper);
    }
}

export function getBeautyValue(value: any, type: string): string {
    let beauty;
    switch (type) {
        case "Week":
            beauty = value.Week + "'" + value.Year;
            break;

        case "Checkbox":
            const text = value ? "Yes" : "No";
            beauty = `<span class="${text.toLocaleLowerCase()}>${text}</span>`;
            break;

        case "Date":
            beauty = getBeautyDate(value);
            break;

        case "Money":
            beauty = getBeautyMoney(value);
            break;

        case "MoneyWithCurrency":
            if (value instanceof Map) {
                // Map с разбивкой по валютам
                const beautyLines: string[] = [];
                value.forEach((amount: number, currencyIso: string) => {
                    if (amount != 0) {
                        beautyLines.push(getBeautyMoney(amount, false, currencyIso));
                    }
                });
                beauty = beautyLines.join("<br>");
            } else if (value) {
                const [currencyIso, amount] = value.split(" ");
                beauty = getBeautyMoney(amount, false, currencyIso);
            } else {
                beauty = "";
            }
            break;

        default:
            beauty = value;
    }

    return beauty;
}

export function getMoneyWithCurrencyAsTouple(value: string): [string, number] {
    let amount = 0;
    let currencyIso = "";

    if (value && value.search(" ") !== -1) {
        const parts = value.split(" ");
        currencyIso = parts[0];
        amount = myParseFloat(parts[1]);
    }

    return [currencyIso, amount];
}

export function postMetric(actionType: string, timeMs: number, payload: string | Object) {
    $.post("?a=Core&ps=Metric", {ActionType: actionType, TimeMs: timeMs, Payload: payload});
}

export function iconExcel() {
    return '<i class="fa file-excel-o"></i>';
}

export function iconFolderFull() {
    return '<i class="fa fa-folder icon-folderfull"></i>';
}

export function calcTotalPerCurrency(values: string[]): Map<string, number> {
    const totalPerCurrency = new Map<string, number>();
    values.forEach((value: string) => {
        const [currencyIso, amount] = getMoneyWithCurrencyAsTouple(value);
        if (amount == 0) {
            return;
        }
        if (!totalPerCurrency.has(currencyIso)) {
            // Нашли какую-то новую валюту, которая не встречалась ранее
            totalPerCurrency.set(currencyIso, amount);
        } else {
            // Эта валюта уже есть в Map totalPerCurrency, прибавляем текующую сумму amount
            totalPerCurrency.set(currencyIso, totalPerCurrency.get(currencyIso)! + amount);
        }
    });

    return totalPerCurrency;
}

export function ajaxGetCurrRate(
    date: string,
    currencyFrom: string,
    currencyTo: string,
    callback: (currRate: any) => void
) {
    if (date.length !== 10) {
        return;
    }
    const url = `?a=Core&ps=GetRate&date=${date}&currencyfrom=${currencyFrom}&currencyto=${currencyTo}`;
    $.ajax(url).done((currRate: any) => {
        callback(currRate);
        toastSuccess(`Курс ${currencyFrom}/${currencyTo}: ${currRate}`);
    });
}

const keypairs = [
    ["q", "й"],
    ["w", "ц"],
    ["e", "у"],
    ["r", "к"],
    ["t", "е"],
    ["y", "н"],
    ["u", "г"],
    ["i", "ш"],
    ["o", "щ"],
    ["p", "з"],
    ["[", "х"],
    ["]", "ъ"],
    ["\\", "ё"],
    ["a", "ф"],
    ["s", "ы"],
    ["d", "в"],
    ["f", "а"],
    ["g", "п"],
    ["h", "р"],
    ["j", "о"],
    ["k", "л"],
    ["l", "д"],
    [";", "ж"],
    ["z", "я"],
    ["x", "ч"],
    ["c", "с"],
    ["v", "м"],
    ["b", "и"],
    ["n", "т"],
    ["m", "ь"],
    [",", "б"],
    [".", "ю"]
];

function findOppositeLetter(letter: string) {
    for (let i = 0; i < keypairs.length; i++) {
        const keypair = keypairs[i];
        if (keypair[0] === letter) {
            return keypair[1];
        }
        if (keypair[1] === letter) {
            return keypair[0];
        }
    }

    return letter;
}

export function getOppositeString(str: string): string {
    return str
        .split("")
        .map(findOppositeLetter)
        .join("");
}

export function addStandardListeners($root: JQuery) {
    initSelectChild($root);
    initSelect2($root);
    initTabs($root);
    initTooltip($root);
    fallbackInputTypeDate($root);
}

export function getVatAmount(amount: number, vatPercent: number) {
    if (vatPercent <= 0) {
        return 0;
    }
    return round(((amount / (1 + (vatPercent / 100))) - amount) * -1);
}
