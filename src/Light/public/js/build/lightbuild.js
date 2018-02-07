window["Light"] =
/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 7);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var _ = __webpack_require__(8);
exports.listener = new window.keypress.Listener();
function dateformatToJs(phpDateFormat) {
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
var img = new Image();
img.src = "lightpublic/i/circle.gif";
function getLang() {
    return window['_lightLang'];
}
exports.getLang = getLang;
exports.isLangRu = window['_lightLang'] === "ru";
function initSelectChild($root) {
    $root.find("select.select-child").each(function () {
        var $child = $(this);
        var initChildValue = $child.val();
        var childFieldKey = $child.attr("data-ownfieldkey");
        var childOriginalHtml = $child.html();
        var parentFieldKeys = $.parseJSON($child.attr("data-parentfieldkeys"));
        // С сервера может прийти объект relations в котором числовые данные, однако значения атрибута value
        // в теге option всегда будет строковое - из-за этого lodash filter не сможет сопоставить
        // Заранее переведём все значения в relations в
        var relations = JSON.parse($child.attr("data-relations")).map(function (relation) {
            for (var parentKey in relation) {
                relation[parentKey] = relation[parentKey].toString();
            }
            return relation;
        });
        // элемент от которого зависит данный select должен находиться в той же форме
        var $form = $child.closest("form");
        if ($form.length === 0) {
            // Форма не найдена, тогда поиск будем делать в body
            $form = $(document.body);
        }
        // В самом начале загрузки страницы добавить в массив relations ещё один объект - объект содержащий текущие
        // значения всех элементов
        var additionalRelation = {};
        additionalRelation[childFieldKey] = initChildValue;
        parentFieldKeys.forEach(function (parentFieldKey) {
            return (additionalRelation[parentFieldKey] = (getFieldValue($form, parentFieldKey) || "").toString());
        });
        relations.push(additionalRelation);
        var onParentChange = function (initialRender) {
            var lastChildValue = $child.val();
            // Узнаем значения всех родительских полей
            // Объект parentValues содержит значения родительских элементов в виде объекта {parentFieldKey1: value1, parentFieldKey2: value2}
            var parentValues = {};
            // Также подготовим флаг, который покажет - нужно ли отображать все child значения? Если найдётся
            // хотя бы одно значения parent элемента отличное от % и '', тогда не все child нужно отображать, будем фильтровать
            var showAllChildValues = true;
            parentFieldKeys.forEach(function (fieldKey) {
                var parentValue = (getFieldValue($form, fieldKey, "Select") || "").toString();
                if (parentValue !== "%" && parentValue !== "") {
                    parentValues[fieldKey] = parentValue;
                    showAllChildValues = false;
                }
            });
            if (showAllChildValues) {
                // Отображаем все возможные значения в дочернем элементе
                $child.html(childOriginalHtml);
            }
            else {
                // Если попали в этот else, значит в каком-то parent что-то выбрно - нужно отобразить не все возможные
                // значения, а лишь некоторые
                var validOptionValues_1 = _(relations)
                    .filter(parentValues)
                    .map(childFieldKey)
                    .value();
                var $tmp = $("<select></select>");
                // Заполним его исходными элементами
                $tmp.html(childOriginalHtml);
                // Пройдём по всем option внутри клона и удалим ненужные
                $("option", $tmp).each(function () {
                    if (this.value !== "%" &&
                        this.value !== "" &&
                        this.value !== "-1" &&
                        _.indexOf(validOptionValues_1, this.value) === -1) {
                        $(this).remove();
                    }
                });
                // Теперь переберём все пустые группы
                $tmp.children("optgroup:empty").remove();
                $child.empty().append($tmp.children());
            }
            if (!initialRender) {
                // Если в Child выпадающем списке остался всего один элемент - выберем его автоматически
                var $notEmptyOptions = $child.find("option").not('[value=""]');
                if ($notEmptyOptions.length === 1) {
                    $child.val($notEmptyOptions.val());
                }
                else {
                    // Вернём текущее значение child до всех манипуляций с вложенными элементами
                    // Если такой элемент есть среди валидных!
                    if (lastChildValue !== "" && $child.find("option[value=\"" + lastChildValue + "\"]").length === 1) {
                        $child.val(lastChildValue);
                    }
                    else {
                        $child.prop("selectedIndex", 0);
                    }
                }
                if (lastChildValue !== $child.val()) {
                    $child.trigger("change");
                }
            }
            else {
                // При начальном рендрингде вернём текущее значение child до всех манипуляций с вложенными элементами
                $child.val(initChildValue);
            }
        };
        // Навесим обработчики on change на все родительские элементы
        parentFieldKeys.forEach(function (fieldKey) {
            getFieldNode($form, fieldKey).on("change", function () { return onParentChange(false); });
        });
        // В самом начале после загрузки страницы переинициализируем дочерний список на основе значения родитея
        onParentChange(true);
    });
}
function escapeHtml(text) {
    var map = {
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
function formatResultSelect2(option) {
    if (option.loading) {
        return;
    }
    var originalOption = $(option.element);
    var icon = originalOption.data("icon");
    var cssClass = originalOption.attr("class");
    if (!icon && !cssClass) {
        return option.text;
    }
    var title = option.title;
    var text = option.text;
    var out = "";
    if (title || cssClass) {
        out = '<span class="' + cssClass;
        if (title !== "") {
            out += ' toggle-tooltip" title="' + escapeHtml(title) + '"';
        }
        else {
            out += '"';
        }
        out += ">";
    }
    if (icon !== undefined) {
        if (icon.indexOf("fa-") !== 0) {
            icon = "fa-" + icon;
        }
        out += '<i class="fa ' + icon + '"></i> ';
    }
    out += escapeHtml(text);
    if (title || cssClass) {
        out += "</span>";
    }
    return $(out);
}
function initSelect2($root) {
    if (jQuery().select2) {
        $root.find("select.select-select2").select2({
            templateResult: formatResultSelect2,
            templateSelection: formatResultSelect2
        });
        $root.find("select.select-select2multiple").select2({
            templateResult: formatResultSelect2,
            templateSelection: formatResultSelect2,
            placeholder: "",
            allowClear: true
        });
    }
}
exports.initSelect2 = initSelect2;
// Вспомагательная функция - вычисляет css свойства для индикатора активного таба
// Принимает активную ссылку
function calcTabsIndicatorCss($a) {
    var $li = $a.closest("li");
    var $ul = $li.closest("ul");
    return {
        left: $li.position().left + $ul.scrollLeft(),
        width: $li.outerWidth(),
        backgroundColor: $a.css("color")
    };
}
function rerenderTabsIndicator($root) {
    $root
        .find("ul.tabs")
        .not(".nojs")
        .each(function () {
        var $ul = $(this);
        var activeHash = "#" + $ul.attr("data-active");
        // Анимируем индикатор
        var _a = calcTabsIndicatorCss($ul.find("a[href=\"" + activeHash + "\"]")), left = _a.left, width = _a.width, backgroundColor = _a.backgroundColor;
        $ul
            .children(".indicator")
            .css({ backgroundColor: backgroundColor })
            .animate({ left: left, width: width });
    });
}
function initTabs($root) {
    $root
        .find("ul.tabs")
        .not(".nojs")
        .each(function () {
        var $ul = $(this);
        var activeHash;
        // Если в ul в data атрибуте задан активный таб, то используем его в качестве активного в самом начале
        // и тут же проверим, что такой таб вообще существует
        if ($ul.attr("data-active") && $ul.find("a[href=\"#" + $ul.attr("data-active") + "\"]").length !== 0) {
            activeHash = "#" + $ul.attr("data-active");
        }
        else {
            // Если жэ data-атрибут не задан, то используем первый попавшийся таб
            activeHash = $ul.find("a:first").prop("hash");
            // Сохраним значение активного таба в data атрибуте, чтобы из любого js скрипта можно было его получить
            $ul.attr("data-active", activeHash.substring(1));
        }
        // Пройдём по всем ссылкам, скроем все слои, кроме активного
        // Кроме этого нужно найти самый большой по вертикали слой и всем остальным слоям добавить min-height,
        // чтобы не было дёрганий по вертикали при переключении табов
        var links = $ul.find("a").get();
        var maxHeight = Math.max.apply(null, links.map(function (link) { return $(link.hash).outerHeight(); }));
        links.forEach(function (link) {
            var $div = $(link.hash);
            if (link.hash !== activeHash) {
                // Это не активный таб. Найдём div, который соответсвует этой ссылке и скроем его
                $div.hide();
            }
            else {
                // Если же таб активный, на всякий случай сделаем show. В большестве случаев начальная вёрстка содержит
                // все табы в display: block, т.е. этот show ничего не сделает. Но есть модули, когда удобнее изначально
                // на сервере скрыть все табы, сделав по умолчанию display: none в css файле - тогда при инициализации
                // для активного таба обязательно нужно убрать этот display: none - запустив show()
                $div.show();
            }
            // Всем слоям присавиваем свойство minHeight равное максимально найденной высоте
            $div.css({ minHeight: maxHeight });
        });
        // Создадим плавающий индикатор под табами
        var $indicator = $('<div class="indicator"></div>')
            .appendTo($ul)
            .css(calcTabsIndicatorCss($ul.find("a[href=\"" + activeHash + "\"]")));
        $ul.on("click", "a", function (e) {
            e.preventDefault();
            var $a = $(this);
            $(activeHash).hide();
            activeHash = this.hash;
            var $currentDiv = $(activeHash);
            $currentDiv.show();
            // Анимируем индикатор
            var _a = calcTabsIndicatorCss($a), left = _a.left, width = _a.width, backgroundColor = _a.backgroundColor;
            $indicator.css({ backgroundColor: backgroundColor }).animate({ left: left, width: width });
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
function initTooltip($root) {
    $root.find(".toggle-tooltip").tooltip();
}
exports.initTooltip = initTooltip;
function showFlashMessagess(messagess) {
    // Проверим, есть ли сейчас какой-то активный диалог на экране?
    if ($(".lightdialogoverlay").length > 0) {
        // Выводим сообщения в виде тостов
        showToastMessagess(messagess);
    }
    else {
        // noinspection TypeScriptValidateTypes
        $("#flash-messages")
            .fadeOut(0)
            .empty()
            .append(messagess
            .map(function (_a) {
            var type = _a.type, msg = _a.msg;
            return "<div class=\"alert alert-" + (type === "error" ? "danger" : type) + "\">" + msg + "</div>";
        })
            .join(""))
            .fadeIn();
    }
}
exports.showFlashMessagess = showFlashMessagess;
function showToastMessagess(messagess) {
    messagess.forEach(function (typeAndMsg) {
        toast(typeAndMsg.type, typeAndMsg.msg);
    });
}
exports.showToastMessagess = showToastMessagess;
var $toastContainer = null;
function toast(type, msg) {
    if ($toastContainer === null) {
        $toastContainer = $('<div id="toast-container">').appendTo(document.body);
    }
    // Create toast
    var $toast = $("<div></div>")
        .addClass("toast alert-" + type)
        .css({ top: "35px", opacity: 0 })
        .html(msg)
        .appendTo($toastContainer)
        .animate({
        top: "0px",
        opacity: 1
    });
    setTimeout(function () {
        $toast.animate({ marginTop: "-40px", opacity: 0 }, 400, function () { return $toast.remove(); });
    }, 4000);
}
exports.toast = toast;
function toastInfo(msg) {
    toast("info", msg);
}
exports.toastInfo = toastInfo;
function toastSuccess(msg) {
    toast("success", msg);
}
exports.toastSuccess = toastSuccess;
function toastWarning(msg) {
    toast("warning", msg);
}
exports.toastWarning = toastWarning;
function toastError(msg) {
    toast("error", msg);
}
exports.toastError = toastError;
var ajaxCounter = 0;
$(document)
    .ajaxSend(function (_, XMLHttpRequest, ajaxOptions) {
    ajaxCounter++;
    // Отображаем глобальный прогресс бар (если его ещё нет)
    if (!document.getElementById("globalprogress")) {
        $('<div id="globalprogress"><div></div></div>').appendTo("body");
    }
    if (ajaxOptions.type === "POST") {
        XMLHttpRequest.setRequestHeader("X-CSRF-Token", window._lightCsrfToken);
    }
})
    .ajaxComplete(function (_, XMLHttpRequest) {
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
            document.location.href = XMLHttpRequest.getResponseHeader("x-light-redirect");
        }
        else if (XMLHttpRequest.getResponseHeader("x-light-redirect-post")) {
            //От сервера поступила инструкцию перенаправить пользователя на другюу страницу используя
            //POST запрос - это может быть создание оплаты из большого количества счетов, например
            var $form = $("<form></form>").attr({
                method: "post",
                action: XMLHttpRequest.getResponseHeader("x-light-redirect-post")
            });
            var data = parseJson(XMLHttpRequest.responseText);
            for (var key in data) {
                $form.append($("<input>").attr({
                    type: "hidden",
                    name: key,
                    value: data[key]
                }));
            }
            $form
                .append("<input type=\"hidden\" name=\"csrf_token\" value=\"" + window._lightCsrfToken + "\">")
                .appendTo("body")
                .submit();
        }
        //Проверим наличие header заголовка, в котором находится js скрипт для выполнения
        if (XMLHttpRequest.getResponseHeader("x-light-jseval")) {
            eval(XMLHttpRequest.getResponseHeader("x-light-jseval"));
        }
        //Проверим наличие header заголовка, в котором находятся flash msg
        if (XMLHttpRequest.getResponseHeader("x-light-flashmsg")) {
            showFlashMessagess(parseJson(XMLHttpRequest.getResponseHeader("x-light-flashmsg"), []));
        }
        //Иногда flash msg так много, что они не помещаются в header заголовок, поэтому приходят в теле ajax ответа
        if (XMLHttpRequest.getResponseHeader("x-light-flashmsg-body")) {
            showFlashMessagess(parseJson(XMLHttpRequest.responseText, []));
        }
        if (XMLHttpRequest.getResponseHeader("x-light-toastmsg")) {
            showToastMessagess(parseJson(XMLHttpRequest.getResponseHeader("x-light-toastmsg"), []));
        }
        if (XMLHttpRequest.getResponseHeader("x-light-redirect-blank")) {
            window.open(XMLHttpRequest.getResponseHeader("x-light-redirect-blank"), "_blank");
        }
    }
});
//Навесим стандартные делегаты на весь документ
var $body = $(document.body);
$body.on("focus", "input[type=text].input-date:not(.hasDatepicker)", function () {
    $(this)
        .datepicker()
        .blur()
        .focus();
});
$body.on("change", "input[type=date]", function () {
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
function fallbackInputTypeDate($root) {
    $root.find("input[type=date]").each(function () {
        if (this.type !== "date") {
            this.setAttribute("type", "text");
            if (this.value !== "") {
                this.value = getBeautyDate(this.value);
            }
        }
        else if (this.value === "") {
            $(this).addClass("empty-date");
        }
    });
}
$body.on("focus", "input[type=text].input-datetime:not(.hasDatepicker)", function () {
    var $input = $(this);
    $input.datetimepicker({
        timepickerHrs: true,
        timepickerMin: true,
        allowTimes: false,
        step: 1,
        dayOfWeekStart: 1,
        format: window['_lightDateTimeFormat'],
        lang: window['_lightLang']
    });
    //$input.blur();
    //$input.focus();
});
// Проверим, есть ли в документе элементы SelectAddNew
$body.on("change keyup", "select.select-addnew", function () {
    $("#" + this.id + "New").toggleClass("display-none", this.value !== "+");
});
// Логируем все клики по ссылкам mailto: чтобы собрать статистику, какие ссылки реально используются
$body.on("click", "a[href^='mailto:']", function () {
    $.post("?a=Core&ps=Metric", {
        ActionType: "mailto",
        TimeMs: 0,
        Payload: this.href
    });
});
$body.on("keypress", "input.input-int", function (e) {
    var code = e.which;
    //Опием разрешенные зачения и возьмём отрицание - если отрицание true - нужно предотвратить вставку
    if (!((code >= 48 && code <= 57) /* цифры */ ||
        code === 8 /* backspace */ ||
        code === 13 /* enter */ ||
        code === 0 ||
        e.ctrlKey ||
        e.metaKey)) {
        e.preventDefault();
        //Если это был знак минус, который мы только что заблокировали, вставим его вручную в начало строки
        if (code === 45) {
            if (this.value.substring(0, 1) !== "-") {
                this.value = "-" + this.value;
            }
        }
    }
});
$body.on("keypress", "input.input-float", function (e) {
    var code = e.which;
    //Опием разрешенные зачения и возьмём отрицание - если отрицание true - нужно предотвратить вставку
    if (!((code >= 48 && code <= 57) /* цифры */ ||
        code === 45 /* минус */ ||
        code === 46 /* точка */ ||
        code === 44 /* запятая */ ||
        code === 8 /* backspace */ ||
        code === 13 /* enter */ ||
        code === 0 ||
        e.ctrlKey ||
        e.metaKey)) {
        e.preventDefault();
    }
    //Если это была точка или запятая, разрешим вставку только если десятичный разделитель ещё не присутсвует внутри строки
    if (code === 46 /* точка */ || code === 44 /* запятая */) {
        if (this.value.indexOf(".") !== -1 || this.value.indexOf(",") !== -1) {
            e.preventDefault();
        }
    }
});
$body.on("click", "a.showhide", function (e) {
    e.preventDefault();
    var $link = $(this);
    // Сначала пытаемся найти полный текст слева от ссылки
    var $full = $link.siblings(".truncated-full");
    var $part = $link.siblings(".truncated-part");
    var newFullTextState;
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
$body.on("click", 'a[href$="#ajax"], a[href$="#ajax-post"]', function (e) {
    if (this.hash !== "#ajax-post" && (e.ctrlKey || e.shiftKey || e.metaKey)) {
        // Если это не POST ссылка (т.е. ссылка для обычного GET запроса)
        // и пользователь зажал Ctrl или Shift - значит пользователь хочет открыть ссылку в новом окне
        // в этом случае не будем делать e.preventDefault(); чтобы позволить браузеру открыть новое окно по ссылке
        // Естественно на сервер придёт не ajax запрос, а обычный GET запрос и этот момент нужно учесть
        // при разработке функции на серверной стороне
        return;
    }
    e.preventDefault();
    var $link = $(this);
    // Проверим, возможно ajax запрос от этой ссылки сейчас в процессе? Тогда второй запрос отравлять не нужно!
    if ($link.hasClass("inprogress")) {
        return;
    }
    // Добавим специальный класс, чтобы невозможно было кликнуть по этой ссылке второй раз, пока она в делает запрос
    $link.addClass("inprogress");
    var $newImg;
    var $icon = $link.find("img, i.fa");
    if ($icon.length > 0) {
        // Внутри этой ссылки могла быть иконка material icon или font awsome
        $icon.addClass("visibility-hidden");
        $newImg = $("<img>")
            .prop("src", "lightpublic/i/circle.gif")
            .css({ position: "absolute", marginTop: 3, marginLeft: 3 });
        $link.prepend($newImg);
    }
    var ajaxSettings = { method: this.hash === "#ajax-post" ? "post" : "get" };
    // Если у ссылки был атрибут post-data, додавим данные к post запросу (в атрибуте должен лежать json)
    var postDataAttr = this.getAttribute("data-post");
    if (postDataAttr) {
        var postData = parseJson(postDataAttr, null);
        if (postData) {
            ajaxSettings["data"] = postData;
        }
    }
    $.ajax(this.href, ajaxSettings).always(function () {
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
$body.on("click", 'a[href$="#post"], a[data-post]:not([href$="#ajax-post"])', function (e) {
    e.preventDefault();
    var $form = $("<form></form>")
        .attr({
        method: "post",
        action: this.href
    })
        .append("<input type=\"hidden\" name=\"csrf_token\" value=\"" + window._lightCsrfToken + "\">");
    var postDataAttr = this.getAttribute("data-post");
    if (postDataAttr) {
        var postData = parseJson(postDataAttr, null);
        if (postData) {
            for (var key in postData) {
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
//Новый подход к Autocomplete: любое поле может быть Autocomplete, если у него поередлён атрибут data-ac
$body.on("focus", "input[data-ac], textarea[data-ac]", function () {
    var $input = $(this);
    //Проверим, что у этого элемента ещё нет дата-атрибута ui-autocomplete
    if ($input.data("ui-autocomplete")) {
        //Если же он есть, значит на данном элементе уже инициализировался виджет Autocomplete
        return;
    }
    //Сформируем конфигурационный объект для виджета на основе параметров переданных в атрибуте data-ac
    var config = {};
    var acParams = parseJson($input.attr("data-ac"));
    if (acParams.sourceJsFunction) {
        var sourceFn_1 = eval(acParams.sourceJsFunction);
        config.source = function (request, responseCallback) {
            var url = sourceFn_1() + "&term=" + encodeURIComponent(request.term);
            $.ajax(url, { dataType: "json", success: responseCallback });
        };
    }
    else if (acParams.source) {
        if (acParams.source.substring(0, 1) === "?") {
            //В качестве параметра source на php стороне был определён полный url для запроса начинающийся со знака ?
            config.source = acParams.source;
        }
        else {
            //В качестве параметра source на php стороне была задана лишь таблица, будем делать запрос к модулю core
            config.source = "?a=Core&ac=core&source=" + acParams.source;
        }
    }
    if (acParams.onSelectJsFunction) {
        var selectFn_1 = eval(acParams.onSelectJsFunction);
        config.select = function (_, ui) {
            selectFn_1.call(this, ui.item);
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
        var resultFormatterFn_1 = eval(acParams.resultFormatterJsFunction);
        $input.data("ui-autocomplete")._renderItem = function (ul, item) {
            return ($("<li>")
                .append("<div>" + resultFormatterFn_1.call(this.element, item) + "</div>")
                .appendTo(ul));
        };
    }
});
$body.on("submit", "form", function (e) {
    if (!this.action) {
        // Если у этой формы нет атрибута action, занчит это какая-то хитрая форма, которая обратабывается
        // исключительно javascript кодом, не будем её трогать
        return;
    }
    var $form = $(this);
    var $formSaveButtons = $form.find(".btn-save, button[type=submit]");
    $formSaveButtons.prop("disabled", true);
    if (this.action.substr(-5, 5) === "#ajax" && $form.closest(".lightdialog__content").length === 0) {
        // action этой формы оканчивается на #ajax - это признак того, что форму нужно отрпавить ajax запросом
        // и форма не находится внутри какого либо диалогового окна
        // (ajax формы диалоговых окон обрабатываются внутри Dialog.ts)
        e.preventDefault();
        //Отправляем POST запрос на сервер. В случае ошибки - нужно разблокировать кнопки
        //Если всё успешно - тут мы ничего не делаем. Сервер сам должен решить что делать, например вернуть x-light-redirect
        var url = this.action;
        var postData = $form.serialize();
        $.post(url, postData).fail(function () {
            $formSaveButtons.prop("disabled", false);
        });
    }
});
function initNextPrevLinkKeypress(keys) {
    exports.listener.simple_combo(keys, function (e) {
        var targetTag = e.target.tagName.toLowerCase();
        if (targetTag !== "input" && targetTag !== "textarea") {
            var selector = keys === "ctrl right" ? ".next-link" : ".prev-link";
            var $link = $("#page-head-right").find(selector);
            if ($link.length > 0) {
                document.location.href = $link.attr("href");
                return false;
            }
        }
        //Возвращаем true, чтобы выполниеть default действие на этом хоткее
        return true;
    });
}
initNextPrevLinkKeypress("ctrl right");
initNextPrevLinkKeypress("ctrl left");
function setUserParam(name, value, successCallback) {
    $.post("index.php?a=Core&ps=userparam", { param: name, value: value }, successCallback || null, "text");
}
exports.setUserParam = setUserParam;
function getUserParam(name, successCallback) {
    $.get("index.php?a=Core&ps=userparam", { param: name }, successCallback, "text");
}
exports.getUserParam = getUserParam;
function getModule() {
    return window._lightModule;
}
exports.getModule = getModule;
/**
 * Возвращает указанный параметр, который опередлён в json сгенерированном на стороне сервера в глобальном объекте window
 */
function getPageParam(paramName) {
    return window._lightPageParams[paramName];
}
exports.getPageParam = getPageParam;
function blockButtons(selector, block) {
    $(selector)
        .blur()
        .prop("disabled", block);
}
exports.blockButtons = blockButtons;
function parseJson(stringJson, returnOnError) {
    if (returnOnError === void 0) { returnOnError = {}; }
    try {
        return JSON.parse(stringJson);
    }
    catch (x) {
        //Если неудачно распарсили - возвращается пустой объект или то, что передано во втором параметре
        return returnOnError;
    }
}
exports.parseJson = parseJson;
function buildMapFromObj(obj) {
    var map = new Map();
    Object.keys(obj).forEach(function (key) {
        map.set(key, obj[key]);
    });
    return map;
}
exports.buildMapFromObj = buildMapFromObj;
function buildObjFromMap(m) {
    var obj = {};
    m.forEach(function (val, key) {
        obj[key] = val;
    });
    return obj;
}
exports.buildObjFromMap = buildObjFromMap;
/**
 * Округляет до двух знаков полсе запятой
 */
function round(value, precision) {
    if (precision === void 0) { precision = 2; }
    // Оставляем только 2 знака после запятой (или сколько указано в precision)
    var multiplier = Math.pow(10, precision);
    return Math.round(multiplier * myParseFloat(value)) / multiplier;
}
exports.round = round;
/**
 * Отличие от стандартного метода myParseInt - если не удалось распарсить, этот метод возвращает 0
 */
function myParseInt(value) {
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
exports.myParseInt = myParseInt;
/**
 * Отличие от стандартного метода myParseFloat - если не удалось распарсить, этот метод возвращает 0
 */
function myParseFloat(value) {
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
exports.myParseFloat = myParseFloat;
function getBeautyMoney(num, short, currencyIso, zeroEmpty) {
    if (short === void 0) { short = false; }
    if (currencyIso === void 0) { currencyIso = ""; }
    if (zeroEmpty === void 0) { zeroEmpty = false; }
    num = myParseFloat(num);
    if (zeroEmpty && num == 0) {
        return "";
    }
    num = round(num);
    // Запомним знак
    var minus = num < 0 ? "-" : "";
    // Целая часть, обязательно сначала взять abs
    var leftStr = Math.floor(Math.abs(num)).toString();
    // Вставим в целую часть пробелы через каждые три символа
    // Для этого каждые три символа из левой части запишем в массив leftBlocks
    var leftBlocks = [];
    // Узнаем кол-во блоков
    var rounds = Math.ceil(leftStr.length / 3);
    // Идём справа налево, вырезая подстроки по 3 символа
    var start;
    var len;
    for (var i = 0; i < rounds; i++) {
        //Начальный символ блока
        start = leftStr.length - 3 * (i + 1);
        //Длина блока
        if (start >= 0) {
            //Не вышли за границу строки слева, можем вырезать полный блок 3 символа
            len = 3;
        }
        else {
            //Вышли за границу строки слева. Переменная start отрицательна и содержит таким образом кол-во символов, на которое вылезли
            len = 3 + start; //на самом деле здесь вычитание, т.к. переменная start отрицательная
            start = 0;
        }
        //Записываем очередной блок в начало массива
        leftBlocks.unshift(leftStr.substr(start, len));
    }
    // Подготовим выходной результат
    var result = '<span class="nobr">' + minus + leftBlocks.join("&nbsp;");
    // Найдём правую дробную часть
    var rightStr = "";
    // Обязательно взять abs, чтобы исключить знак минус - он может помещать при выделении дробной части
    var num100 = Math.abs(Math.round(num * 100)).toString();
    // Если после умножения на 100 число получислоь из одной цифры, значит изначально это было что-то типа 0.0x
    if (num100.length == 1) {
        rightStr = "0" + num100;
    }
    else {
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
exports.getBeautyMoney = getBeautyMoney;
function getBeautyMoneyShort(num, currencyIso) {
    if (currencyIso === void 0) { currencyIso = ""; }
    return getBeautyMoney(num, true, currencyIso);
}
exports.getBeautyMoneyShort = getBeautyMoneyShort;
function getBeautyDate(date, fullyear) {
    if (fullyear === void 0) { fullyear = true; }
    if (date === undefined) {
        return "";
    }
    if (typeof date === "string" && (date == "" || date == "0000-00-00")) {
        return "";
    }
    var mysqlDate = getMySQLDate(date);
    if (mysqlDate == "") {
        return "";
    }
    var tdate = mysqlDate;
    var time = null;
    if (date.length == 19) {
        var dateTime = mysqlDate.split(" ");
        tdate = dateTime[0];
        time = dateTime[1];
    }
    var parts = tdate.split("-");
    if (parts.length !== 3) {
        return date;
    }
    var year = parts[0];
    var month = parts[1];
    var day = parts[2];
    if (!fullyear) {
        year = year.substring(2);
    }
    var dateString = day + "." + month + "." + year;
    if (time != null) {
        dateString += " " + time;
    }
    return dateString;
}
exports.getBeautyDate = getBeautyDate;
/**
 * Принимает объект типа Date или строку DD.MM.YYYY и возвращает строку в формате MySQL 'YYYY-MM-DD';
 */
function getMySQLDate(date) {
    if (date === null || (typeof date === "string" && (date == "" || date == "0000-00-00"))) {
        return "";
    }
    if (typeof date !== "object") {
        if (date[2] == ".") {
            //Это формат DD.MM.YYYY
            if (date.length == 10) {
                date = date.split(".");
                return date[2] + "-" + date[1] + "-" + date[0];
            }
            else if (date.length == 19) {
                //Это формат DD.MM.YYYY HH:mm:ii
                var datetime = date.split(" ");
                date = datetime[0].split(".");
                return date[2] + "-" + date[1] + "-" + date[0] + " " + datetime[1];
            }
        }
        //Это неизвестный формат строки, вернём как есть и будь что будет!
        return date;
    }
    var year = date.getFullYear();
    var month = date.getMonth() + 1;
    var day = date.getDate();
    if (month < 10) {
        month = "0" + month.toString();
    }
    if (day < 10) {
        day = "0" + day.toString();
    }
    return year + "-" + month + "-" + day;
}
exports.getMySQLDate = getMySQLDate;
function nl2br(string) {
    return string.toString().replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, "$1" + "<br>" + "$2");
}
exports.nl2br = nl2br;
function htmlspecialchars(html) {
    var div = document.createElement("div");
    div.appendChild(document.createTextNode(html));
    return div.innerHTML;
}
exports.htmlspecialchars = htmlspecialchars;
var __rootCounter = 0;
var __cacheFieldNodes = {};
function getFieldValue($root, fieldName, type) {
    var $node = getFieldNode($root, fieldName, type);
    if ($node.length === 0) {
        return null;
    }
    return getNodeValue($node, type);
}
exports.getFieldValue = getFieldValue;
function getFieldNode($root, fieldName, type) {
    //noinspection TypeScriptValidateTypes
    var rootUniqueCounter = $root.data("__lightRootCounter");
    if (!rootUniqueCounter) {
        __rootCounter++;
        rootUniqueCounter = __rootCounter;
        //noinspection TypeScriptValidateTypes
        $root.data("__lightRootCounter", rootUniqueCounter);
    }
    var cacheKey = rootUniqueCounter + "-" + fieldName + "-" + type;
    if (!__cacheFieldNodes[cacheKey]) {
        switch (type) {
            case "Week":
                __cacheFieldNodes[cacheKey] = $root.find("input[name=\"" + fieldName + "[Week]\"], input[name=\"" + fieldName + "[Year]\"]");
                break;
            case "CheckboxSet":
                __cacheFieldNodes[cacheKey] = $root.find("input[name=\"" + fieldName + "[]\"]");
                break;
            case "MoneyWithCurrency":
                __cacheFieldNodes[cacheKey] = $root.find("input[name=\"" + fieldName + "[Amount]\"], select[name=\"" + fieldName + "[CurrencyISO]\"]");
                break;
            case "Select2Multiple":
                __cacheFieldNodes[cacheKey] = $root.find("#" + fieldName);
                break;
            default:
                __cacheFieldNodes[cacheKey] = $root.find("*[name=\"" + fieldName + "\"]");
                break;
        }
        // если Node по-прежнему не найден, попробуем найти label с атрибутом for = fieldName
        if (__cacheFieldNodes[cacheKey].length == 0) {
            __cacheFieldNodes[cacheKey] = $root
                .find("label[for=\"" + fieldName + "\"]")
                .closest("tr")
                .children("td");
        }
    }
    return __cacheFieldNodes[cacheKey];
}
exports.getFieldNode = getFieldNode;
function getNodeValue($node, type) {
    var value;
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
                .filter(function (el) { return el.checked; })
                .map(function (el) { return el.value; })
                .join("|");
            if (value !== "") {
                value = "|" + value + "|";
            }
            break;
        case "Select2Multiple":
            value = $node.val();
            if (value) {
                value = value.join("|");
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
            if ($node.prop('type') === 'date') {
                // это нативное поле даты, возвращаем значение как есть, оно уже в формате YYYY-MM-DD
                value = $node.val();
            }
            else {
                value = getMySQLDate($node.val());
            }
            break;
        default:
            value = $node.val();
    }
    return value;
}
exports.getNodeValue = getNodeValue;
function parseTextValue(text, type) {
    var value;
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
                var parts = text.split(" "); // "100 123.22 USD"
                var currencyIso = parts.pop();
                value = currencyIso + " " + myParseFloat(parts.join(""));
            }
            else {
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
exports.parseTextValue = parseTextValue;
function setFieldValue($root, fieldName, newValue, type) {
    var $node = getFieldNode($root, fieldName, type);
    if ($node.length > 0) {
        setNodeValue($node, newValue, type);
    }
}
exports.setFieldValue = setFieldValue;
function setNodeValue($node, newValue, type, triggerChange) {
    if (triggerChange === void 0) { triggerChange = true; }
    switch (type) {
        case "Week":
            var parts1 = newValue.split("-");
            $node.eq(1).val(!parts1[0] || parts1[0] == "0000" ? "" : parts1[0]);
            $node.eq(0).val(!parts1[1] || parts1[1] == "00" ? "" : parts1[1]);
            break;
        case "Checkbox":
            newValue = newValue === 1 || newValue === "1" || newValue === true;
            $node.prop("checked", newValue);
            break;
        case "CheckboxSet":
            //Пройдём по всем чекбоксам внутри этого набора (node - это NodeList) и отметим те, которые упоминаются в newValue
            $node.each(function () {
                var cbValue = this.value;
                if (newValue.indexOf("|" + cbValue + "|") !== -1) {
                    $node.prop("checked", true);
                }
                else {
                    $node.prop("checked", false);
                }
            });
            break;
        case "Date":
        case "DateTime":
            if ($node.prop('type') === 'date') {
                // Это нативное поле input type=date, присваиваем значение в формате YYYY-MM-DD
                $node.val(newValue);
            }
            else {
                $node.val(getBeautyDate(newValue, true));
            }
            break;
        case "Month":
            if (newValue.indexOf("-") !== -1) {
                var parts_1 = newValue.split("-");
                newValue = parts_1[1] + "/" + parts_1[0];
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
            var newAmount = void 0;
            if ($.isNumeric(newValue)) {
                //Если передано только число - меняем только поле Amount
                newAmount = newValue;
            }
            else {
                //В противном случае, похоже, получили и валюту и число 'USD 1234.23'
                var parts_2 = newValue.split(" ");
                $node.eq(1).val(parts_2[0]);
                newAmount = parts_2[1];
            }
            newAmount = round(newAmount);
            if (newAmount == 0) {
                newAmount = "";
            }
            $node.eq(0).val(newAmount);
            break;
        case "Select":
        case "SelectChild":
            //Проверим, что новое значение есть среди option в этом select элементе
            if ($node.find("option[value='" + newValue + "']").length > 0) {
                $node.val(newValue);
            }
            break;
        case "Select2Multiple":
            var parts = newValue.split("|").filter(function (n) { return n !== ""; });
            $node.val(parts);
            break;
        default:
            $node.val(newValue);
    }
    if (triggerChange && $node.trigger !== undefined) {
        $node.trigger("change");
    }
}
exports.setNodeValue = setNodeValue;
function onNodeChange($node, handler, type) {
    var changeHandlerWrapper = function () {
        var newValue = getNodeValue($node, type);
        handler(newValue, $node);
    };
    switch (type) {
        case "Checkbox":
        case "CheckboxSet":
            $node.on("click", changeHandlerWrapper);
            break;
        case "Select2Multiple":
            $node.on("change", changeHandlerWrapper);
            break;
        default:
            $node.on("change keyup", changeHandlerWrapper);
    }
}
exports.onNodeChange = onNodeChange;
function getBeautyValue(value, type) {
    var beauty;
    switch (type) {
        case "Week":
            beauty = value.Week + "'" + value.Year;
            break;
        case "Checkbox":
            var text = value ? "Yes" : "No";
            beauty = "<span class=\"" + text.toLocaleLowerCase() + ">" + text + "</span>";
            break;
        case "Date":
            beauty = getBeautyDate(value);
            break;
        case "Money":
            beauty = getBeautyMoney(value);
            break;
        case "MoneyWithCurrency":
            if (value) {
                var parts = value.split(" ");
                beauty = getBeautyMoney(parts[1], false, parts[0]);
            }
            else {
                beauty = "";
            }
            break;
        default:
            beauty = value;
    }
    return beauty;
}
exports.getBeautyValue = getBeautyValue;
function getMoneyWithCurrencyAsTouple(value) {
    var amount = 0;
    var currencyIso = "";
    if (value && value.search(" ") !== -1) {
        var parts = value.split(" ");
        currencyIso = parts[0];
        amount = myParseFloat(parts[1]);
    }
    return [currencyIso, amount];
}
exports.getMoneyWithCurrencyAsTouple = getMoneyWithCurrencyAsTouple;
function postMetric(actionType, timeMs, payload) {
    $.post("?a=Core&ps=Metric", { ActionType: actionType, TimeMs: timeMs, Payload: payload });
}
exports.postMetric = postMetric;
function iconExcel() {
    return '<i class="fa file-excel-o"></i>';
}
exports.iconExcel = iconExcel;
function iconFolderFull() {
    return '<i class="fa fa-folder icon-folderfull"></i>';
}
exports.iconFolderFull = iconFolderFull;
function addStandardListeners($root) {
    initSelectChild($root);
    initSelect2($root);
    initTabs($root);
    initTooltip($root);
    fallbackInputTypeDate($root);
}
exports.addStandardListeners = addStandardListeners;


/***/ }),
/* 1 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var Light_1 = __webpack_require__(0);
var Form = /** @class */ (function () {
    function Form($form) {
        this.$form = $form;
        this.name = $form.attr("name") || "";
        this.fields = Light_1.buildMapFromObj(Light_1.parseJson($form.attr("data-fields"), {}));
        //Сохраняем созданный объект внутрь самой ноды с помощью jQuery
        $form.data("formObj", this);
    }
    Form.prototype.getFormNode = function () {
        return this.$form;
    };
    Form.prototype.getName = function () {
        return this.name;
    };
    Form.prototype.getFields = function () {
        return this.fields;
    };
    Form.prototype.hasField = function (fieldKey) {
        return this.fields.has(fieldKey);
    };
    Form.prototype.getField = function (fieldKey) {
        return this.fields.get(fieldKey);
    };
    Form.prototype.getFieldType = function (fieldKey) {
        return this.fields.has(fieldKey) ? this.fields.get(fieldKey).type : undefined;
    };
    Form.prototype.getFieldName = function (fieldKey) {
        return this.fields.has(fieldKey) ? this.fields.get(fieldKey).name : undefined;
    };
    Form.prototype.setFieldDefaultValue = function (fieldKey, defaultValue) {
        if (this.fields.has(fieldKey)) {
            this.fields.get(fieldKey).def = defaultValue;
        }
    };
    Form.prototype.getFieldNode = function (fieldKey) {
        return Light_1.getFieldNode(this.$form, fieldKey, this.getFieldType(fieldKey));
    };
    Form.prototype.getFieldValue = function (fieldKey) {
        return Light_1.getFieldValue(this.$form, fieldKey, this.getFieldType(fieldKey));
    };
    Form.prototype.getFieldsValues = function () {
        var _this = this;
        var result = new Map();
        this.fields.forEach(function (_, fieldKey) {
            var value = _this.getFieldValue(fieldKey);
            if (value !== null) {
                result.set(fieldKey, value);
            }
        });
        return result;
    };
    Form.prototype.setFieldValue = function (fieldKey, newValue) {
        var type = this.getFieldType(fieldKey);
        if ((type === "Int" && Light_1.myParseInt(newValue) === 0) || (type === "Float" && Light_1.myParseFloat(newValue) === 0)) {
            newValue = "";
        }
        Light_1.setFieldValue(this.$form, fieldKey, newValue, type);
    };
    Form.prototype.onFieldChange = function (fieldKey, handler) {
        //Сначала првоерим, что это поле есть среди списка полей
        var $node = this.getFieldNode(fieldKey);
        var type = this.getFieldType(fieldKey);
        Light_1.onNodeChange($node, handler, type);
    };
    Form.prototype.removeErrors = function () {
        this.$form.find(".input-error").removeClass("input-error");
    };
    Form.prototype.highlightErrorField = function (fieldKey, hint) {
        var $node = this.getFieldNode(fieldKey).addClass("input-error");
        if (hint) {
            $node.prop("title", hint);
        }
    };
    return Form;
}());
exports.Form = Form;
var formsStore = {};
function init($root) {
    $root.find("form").each(function () {
        var $form = $(this);
        var formObj = new Form($form);
        var name = formObj.getName();
        formsStore[name] = formObj;
    });
}
exports.init = init;
function get(formName) {
    if (!formsStore[formName]) {
        throw Error("Unknown form: " + formName);
    }
    return formsStore[formName];
}
exports.get = get;
//Инициализируем все формы на странице
init($(document.body));


/***/ }),
/* 2 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var Light_1 = __webpack_require__(0);
var Filters_1 = __webpack_require__(13);
var Table = /** @class */ (function () {
    function Table($div) {
        var _this = this;
        this.selectRowHandlers = [];
        this.selectAllHandlers = [];
        this.reloadHandlers = [];
        this.$div = $div;
        this.$table = $div.find("table.table");
        this.$tbody = this.$table.children("tbody");
        this.name = $div.attr("data-tablename") || "";
        this.reloadUrl = $div.attr("data-reloadurl") || "";
        this.linkModule = $div.attr("data-linkmodule") || "";
        $div.on("click", "span.table-excel a", function (e) {
            var ids = _this.getSelectedValues();
            if (ids.length !== 0) {
                e.preventDefault();
                document.location.href = e.currentTarget.href + "&excelIds=" + ids.join(",");
            }
        });
        //Попробуем найти текущую страницу из вёрстки
        //Необходимо хранить текущую страницу для метода reload() (удобно при смене table layout settings и при удалении в ModuleAbstract)
        var $strong = $div.find(".table-pages:first").find("strong");
        this.currentPage = $strong.length > 0 ? Light_1.myParseInt($strong.text()) : 1;
        var that = this;
        // Проверим, есть ли форма фильтров поиском по глобальному идентификатору?
        var filtersFormEl = document.getElementById(this.name + "-filters-form");
        if (filtersFormEl) {
            this.filters = new Filters_1.Filters(this);
        }
        $div.on("click", ".table-pages a", function (e) {
            e.preventDefault();
            var tableTop = $div.offset().top;
            //Проверим, виден ли верх таблицы при пажинации, если нет, нужно прокрутить
            var $body = $(document.body);
            if ($body.scrollTop() > tableTop) {
                //window.scrollTo($body.scrollLeft(), tableTop - 10);
                $("html,body").animate({ scrollTop: tableTop - 10 }, 500);
            }
            var newPage = Light_1.myParseInt(e.currentTarget.hash.substring(1));
            _this.reload({ page: newPage });
        });
        $div.on("click", "a.table-showall-link", function (e) {
            e.preventDefault();
            var showall = e.currentTarget.hash.substring(1);
            _this.reload({ showall: showall, page: 1 });
        });
        $div.on("click", "a.table-sort-link", function (e) {
            e.preventDefault();
            var params = e.currentTarget.hash.substring(1).split("|");
            _this.reload({ sort: params[0], order: params[1], page: 1 });
        });
        // Переход по ссылке зашитой внутри строки таблицы при двойном клике
        $div.on("dblclick", "table.table > tbody > tr", function (e) {
            var $tr = $(e.currentTarget);
            var href = $tr.attr("data-href");
            if (!href) {
                // Если внутри самой строки найти href не удалось, попробуем сформировать ссылку из парамтера href у всей таблицы
                if (_this.linkModule) {
                    // У всего контейнера определён параметр linkmodule, попробуем найти id у самой строки
                    var linkId = $tr.attr("data-linkid");
                    if (linkId) {
                        href = "?a=" + that.linkModule + "&id=" + linkId;
                        // Попробуем проверить, есть ли дополнительные параметры для url
                        var tableLinkUrlParams = _this.$table.attr("data-linkurlparams");
                        if (tableLinkUrlParams) {
                            href += "&" + tableLinkUrlParams;
                        }
                        var rowLinkUrlParams = $tr.attr("data-linkurlparams");
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
        $div.on("click", "table.table > tbody > tr", function (e) {
            //Делаем выделение строки, только если клик был совершен
            // не по ссылке
            // не по кнопке color
            // и не по какому-либо элементу формы (input, textarea, select, button)
            var $target = $(e.target);
            if ($target.closest("a").length > 0 ||
                $target.hasClass("colorpalette") ||
                ($target.is("input") && !$target.hasClass("cb") && !$target.hasClass("rb")) ||
                ($target.hasClass("select2") || $target.closest(".select2").length > 0) ||
                $target.is("textarea") ||
                $target.is("select") ||
                $target.is("button") ||
                ($target.hasClass("no-select-row") || $target.closest(".no-select-row").length > 0)) {
                return;
            }
            var $tr = $(e.currentTarget);
            //Проверим, есть ли внутри этого tr чекбокс с классом cb?
            var $cb = $tr.find("input[type=checkbox].cb");
            if ($cb.length > 0) {
                var currentSelected = $cb.prop("checked");
                //Если клик был совершен непосредсвенно на чекбоксе, то $cb.prop("checked") возвращает статус после нажатия
                if ($target.is($cb)) {
                    currentSelected = !currentSelected;
                }
                //В качестве вторго параметра (selected) передаём инвертированное значение текущего статуса
                //т.е. если сейчас строка выбрана, то нужно снять выделение (передать вторым параметром selected = false)
                _this.selectRow($tr, !currentSelected);
            }
            else {
                //Возможно, в этой сторке есть радио кнопка?
                var $rb = $tr.find("input[type=radio].rb");
                if ($rb.length > 0) {
                    //Делаем выделение этой строки, но перед этим снимим выделение со всех других строк
                    $tr
                        .closest("table.table")
                        .find("tr")
                        .removeClass("selected");
                    //Выделяем строку с помощью специального метода в классе Table, чтобы запустить обработчики события
                    _this.selectRow($tr, true);
                }
            }
        });
        //Выделение всех строк или снятие выделения при клике на чекбокс в заголовке таблицы
        $div.on("click", "table.table > thead .cb-all", function (e) {
            //Найдём все строки в таблице и пройдём их в цикле
            _this.selectAll(e.currentTarget.checked);
        });
        $("a.check-all-link[data-tablename=\"" + this.name + "\"]").on("click", function (e) {
            e.preventDefault();
            _this.selectAll(true);
        });
        $("a.uncheck-all-link[data-tablename=\"" + this.name + "\"]").on("click", function (e) {
            e.preventDefault();
            _this.selectAll(false);
        });
    }
    Table.prototype.getName = function () {
        return this.name;
    };
    Table.prototype.getDivNode = function () {
        return this.$div;
    };
    Table.prototype.getTableNode = function () {
        return this.$table;
    };
    Table.prototype.getTBodyNode = function () {
        return this.$tbody;
    };
    Table.prototype.onFiltersReset = function (handler) {
        if (this.filters !== undefined) {
            this.filters.resetHandlers.push(handler);
        }
        return this;
    };
    Table.prototype.onFilterChange = function (fieldKey, handler) {
        if (this.filters !== undefined) {
            this.filters.form.onFieldChange(fieldKey, handler);
        }
        return this;
    };
    Table.prototype.onSelectAll = function (handler) {
        this.selectAllHandlers.push(handler);
        return this;
    };
    Table.prototype.onSelectRow = function (handler) {
        this.selectRowHandlers.push(handler);
        return this;
    };
    Table.prototype.onReload = function (handler) {
        this.reloadHandlers.push(handler);
        return this;
    };
    Table.prototype.getFilterValue = function (fieldKey) {
        if (this.filters !== undefined) {
            return this.filters.getValue(fieldKey);
        }
        return "";
    };
    Table.prototype.setFilterValue = function (fieldKey, newValue) {
        if (this.filters !== undefined) {
            this.filters.setValue(fieldKey, newValue);
        }
        return this;
    };
    Table.prototype.selectRow = function ($tr, selected) {
        //Инвертируем выделение строки специальным классом
        $tr.toggleClass("selected", selected);
        //Отмечаем чекбокс или радио-кнопку
        $tr.find("input[type=checkbox].cb, input[type=radio].rb").prop("checked", selected);
        //После выделения текущей строки, проверим, есть ли чекбокс в заголовке таблицы, нужно ли его переключить?
        var $headCb = this._getHeadCb();
        if ($headCb.length > 0) {
            //Если есть хотя бы один неотмеченный чекбокс среди строк таблицы - снимаем выделение с headCb
            var allSelected = this.$tbody.find("input[type=checkbox].cb:not(:checked)").length === 0;
            $headCb.prop("checked", allSelected);
        }
        this.updateExcelSelectedCountSpan();
        this.selectRowHandlers.forEach(function (handler) {
            handler(selected, $tr);
        });
    };
    Table.prototype._getHeadCb = function () {
        return this.$table.children("thead").find(".cb-all");
    };
    /**
             * Вспомагательная функция для обработчиков в таблицах - выделяет все строки в таблице или снимает выделение
             * у которых есть чекбокс с классом cb
             */
    Table.prototype.selectAll = function (allSelected) {
        this.$tbody.find("tr").each(function () {
            var $tr = $(this);
            var $cb = $tr.find("input[type=checkbox].cb");
            if ($cb.length > 0) {
                //Выделим строку специальным классом
                $tr.toggleClass("selected", allSelected);
                //Выделим чекбокс
                $cb.prop("checked", allSelected);
            }
        });
        this._getHeadCb().prop("checked", allSelected);
        this.selectAllHandlers.forEach(function (handler) {
            handler(allSelected);
        });
    };
    Table.prototype.reload = function (data, callback) {
        var _this = this;
        if (data === void 0) { data = {}; }
        var newPage = Light_1.myParseInt(data.page);
        if (newPage > 0) {
            //При данном вызове метода realod меняется страница - сохраним в currentPage
            this.currentPage = newPage;
        }
        else {
            //Если же страница не была передана, то нужно взять из currentPage
            data.page = this.currentPage;
        }
        // Выполним ajax запрос к серверу, чтобы обновить таблицу
        // Если уже есть какой-то запрос на обновление этой таблицы, то его нужно прервать
        if (this.currentReloadXhr) {
            // Если уже есть какой-то ajax запрос в процессе, то его нужно убить
            this.currentReloadXhr.abort();
        }
        // Запоминаем ссылку на текущий запрос обновления этой таблицы
        this.currentReloadXhr = $.post(this.reloadUrl, data);
        this.currentReloadXhr
            .done(function (newTableHtml) {
            _this.$div.html(newTableHtml);
            //Обновим керующие переменные
            _this.$table = _this.$div.find("table.table");
            _this.$tbody = _this.$table.find("tbody");
            //Заново наложим sticky заголовки на таблицу (если только у неё нет специального класса .nosticky)
            if (!_this.$table.hasClass("nosticky")) {
                var offset = _this.$table.attr("data-stickyoffset") || 0;
                _this.$table.stickyTableHeaders({ fixedOffset: offset });
            }
        })
            .always(function () {
            _this.currentReloadXhr = null;
            if (callback) {
                callback();
            }
            _this.reloadHandlers.forEach(function (handler) {
                handler();
            });
        });
        // Возвращаем JQueryXHR
        return this.currentReloadXhr;
    };
    /**
             * Возвращает массив из значений заключённых внутри чекбоксов в таблице table-tableName-table
             */
    Table.prototype.getSelectedValues = function () {
        return this.$tbody
            .find("input[type=checkbox].cb:checked")
            .toArray()
            .map(function (el) { return el.value; });
    };
    Table.prototype.getSelectedCount = function () {
        return this.$tbody.find("input[type=checkbox].cb:checked").length;
    };
    /**
     * Возвращает массив из значений заключённых внутри чекбоксов в таблице table-tableName-table
     */
    Table.prototype.getSelectedRows = function () {
        return this.$tbody.find("input[type=checkbox].cb:checked").closest("tr");
    };
    /**
     * Возвращает значение первой отмеченной радио-точки
     */
    Table.prototype.getSelectedRadio = function () {
        return (this.$tbody
            .find("input[type=radio].rb:checked")
            .first()
            .val() || "");
    };
    Table.prototype.updateExcelSelectedCountSpan = function () {
        var selectedCount = this.getSelectedCount();
        var $a = this.$div.find("span.table-excel a");
        var $span = $a.find(".table-excel-selected-count-span");
        if (selectedCount === 0) {
            // Если не выделено ничего, удаляем специальный span в котором отображено число выделенных строк
            // (возможно, этого span и не было в вёрстке, в этом случае jQuery ничего не сделает)
            $span.remove();
        }
        else {
            // Если же что-то было выделено, нужно вывести кол-во выделенных строк
            if (!$span.length) {
                // Специальный span пока не существует в вёрстке, быстренько создадим его
                $span = $('<span class="table-excel-selected-count-span"></span>').appendTo($a);
            }
            $span.html("&nbsp;(" + selectedCount + ")");
        }
    };
    return Table;
}());
exports.Table = Table;
var tablesStore = {};
function init($root) {
    $root.find("div.table-div").each(function () {
        var $div = $(this);
        var tableObj = new Table($div);
        var name = tableObj.getName();
        tablesStore[name] = tableObj;
    });
}
exports.init = init;
function get(name) {
    if (tablesStore[name] === undefined) {
        throw Error("Unknown table: " + name);
    }
    return tablesStore[name];
}
exports.get = get;
function has(name) {
    return tablesStore[name] !== undefined;
}
exports.has = has;
//Инициализируем таблицы
init($(document.body));


/***/ }),
/* 3 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var Light_1 = __webpack_require__(0);
var form = __webpack_require__(1);
var table = __webpack_require__(2);
var subtable = __webpack_require__(6);
var openedDialogsStack = [];
var Dialog = /** @class */ (function () {
    function Dialog(content, options, destroyAfterClose) {
        if (options === void 0) { options = {}; }
        if (destroyAfterClose === void 0) { destroyAfterClose = true; }
        var _this = this;
        var $div = $('<div class="lightdialog display-none">');
        if (options.width) {
            $div.css("width", options.width);
        }
        if (options.height) {
            $div.css("height", options.height);
        }
        if (options.top) {
            this.top = options.top;
            $div.css("top", options.top);
        }
        //Добавляем сожержимое
        var $divContent = $('<div class="lightdialog__content">');
        if (content) {
            $divContent.append(content);
        }
        $divContent.appendTo($div);
        //Добавляем footer в котором будут располагаться кнопки
        var $divFooter = $('<div class="lightdialog__footer">').appendTo($div);
        //Сгенерируем кнопки
        //Для начала вставим кнопку cancel (если только она не была принудительно отключена значением  null)
        if (options.buttonCancelText !== null) {
            $('<a href="#cancel" class="lightdialog__button waves-effect grey lighten-5 btn-flat"></a>')
                .html(options.buttonCancelText || (options.buttonOkText ? "Cancel" : "Close"))
                .on("click", function (e) {
                e.preventDefault();
                // обрабатываем клик, только если кнопка уже не заблокирована
                if (!$(e.currentTarget).hasClass("disabled")) {
                    _this.close();
                }
            })
                .appendTo($divFooter);
        }
        //Далее вставим кнопку Ok, если она была определена
        if (options.buttonOkText) {
            $('<a href="#ok" class="lightdialog__button waves-effect waves-light btn bgcolor-btn-primary"></a>')
                .html(options.buttonOkText)
                .on("click", function (e) {
                e.preventDefault();
                // обрабатываем клик, только если кнопка уже не заблокирована
                if (!$(e.currentTarget).hasClass("disabled")) {
                    _this.blockButtons();
                    _this.defaultSubmitHandler(_this, "ok");
                }
            })
                .appendTo($divFooter);
            if (options.buttonOkHandler) {
                this.defaultSubmitHandler = options.buttonOkHandler;
            }
        }
        //Если пользователь указал массив произвольных кнопок, то добавим и их
        if (Array.isArray(options.buttons)) {
            options.buttons.forEach(function (button, index) {
                var btnId = button.id || "btn" + index;
                var $btn = $("<a href=\"#" + btnId + "\" class=\"lightdialog__button waves-effect\"></a>")
                    .html(button.text)
                    .on("click", function (e) {
                    e.preventDefault();
                    _this.blockButtons();
                    button.handler(_this, btnId);
                })
                    .appendTo($divFooter);
                if (button.className) {
                    //Добавим пользовательские классы
                    $btn.addClass(button.className);
                }
                else {
                    //Пользовательских классов не было, добавим стандартные классы
                    if (button.isPrimary) {
                        //Если это Primary кнопка, то делаем её синим цветом с тенью
                        $btn.addClass("waves-light btn bgcolor-btn-primary");
                    }
                    else {
                        //Для прочих кнопок (не Primary) делаем плоский стиль
                        $btn.addClass("btn-flat waves-green");
                    }
                }
                if (button.isPrimary) {
                    //Если эта кнопка отмечена как основная, то именно её handler запишем в defaultSubmitHandler
                    //что пригодится для автоматического запуска формы (если такая есть)
                    _this.defaultSubmitHandler = function () {
                        button.handler(_this, btnId);
                    };
                }
            });
        }
        $div.appendTo(document.body);
        Light_1.addStandardListeners($divContent);
        form.init($divContent);
        table.init($divContent);
        subtable.init($divContent);
        //Проверим, содержал ли content какую либо форму?
        var $form = $div.find("form:not(.filters-form)");
        if ($form.length > 0) {
            if (!this.defaultSubmitHandler) {
                //Если есть форма и к этому моменту не сформировался какой-то defaultSubmitHandler, то сделаем его
                //в виде отпарвки формы
                this.defaultSubmitHandler = function () { return $form.submit(); };
                // Проверим, нужно ли отправлять эту форму через ajax?
                var action = $form.attr("action");
                if (action) {
                    if (action.indexOf("#ajax") !== -1) {
                        // Поскольку в action встретился якорь #ajax - это значит будем отправлять через ajax
                        $form.on("submit", function (e) {
                            e.preventDefault();
                            _this.blockButtons();
                            var url = $form.attr("action");
                            var postData = $form.serialize();
                            //Отправляем POST запрос на сервер. В случае ошибки - нужно разблокировать кнопки
                            //Если всё успешно - тут мы ничего не делаем. Сервер сам должен решить что делать, например вернуть x-light-redirect
                            $.post(url, postData)
                                .done(function () {
                                _this.unblockButtons();
                                _this.close();
                            })
                                .fail(function () {
                                _this.unblockButtons();
                            });
                        });
                    }
                    else {
                        // Форму нужно отправить простым POST запросом, но при этом не забудем заблокировать кнопки диалога
                        $form.on("submit", function () { return _this.blockButtons(); });
                    }
                }
            }
            else {
                //Если же defaultSubmitHandler был определён выше (в кропке Ok или в какой-либо другой кнопке isPrimary
                //то вместо стандартного сабмита формы запустим эту специально определённую функцию
                $form.on("submit", function (e) {
                    e.preventDefault();
                    _this.blockButtons();
                    _this.defaultSubmitHandler(_this, "form");
                });
            }
        }
        //Сохраняем ссылки на
        this.$div = $div;
        this.$divContent = $divContent;
        this.destroyAfterClose = destroyAfterClose;
        if (options.afterClose) {
            this.afterClose = options.afterClose;
        }
        this.positiionCenter = this.positiionCenter.bind(this);
    }
    Dialog.prototype.open = function () {
        var dialogNum = openedDialogsStack.length;
        // Отключаем прокрутку основного окна
        document.body.style.overflow = "hidden";
        // На всякий случай разблокируем все кнопки, если они были заблокированы до этого
        this.unblockButtons();
        //Создаём затемненеие основного окна
        this.$overlay = $('<div class="lightdialogoverlay">')
            .css({ "z-index": 1000 + dialogNum * 2 })
            .appendTo(document.body);
        var $div = this.$div;
        if ($div) {
            // Убираем класс 'display-none' в самом начале, чтобы можно было посчитать outerWidth и outerHeight
            $div.removeClass("display-none");
            $div.css({ "z-index": 1000 + dialogNum * 2 + 1 });
        }
        // Передвигаем диалог в центр экрана
        this.positiionCenter();
        $(window).on("resize", this.positiionCenter);
        if ($div) {
            // Поставим фокус на первый элемент формы, только если это не виджет выбора даты (он некрасиво раскрывается)
            var $firstInput = $div.find("input[type=text]:first, select[data-type=Select2Multiple]:first, textarea:first");
            if ($firstInput.length > 0 && !$firstInput.hasClass("input-date")) {
                $firstInput.get(0).focus();
            }
        }
        //Добавим открытый диалог в стек диалогов
        openedDialogsStack.push(this);
    };
    Dialog.prototype.positiionCenter = function () {
        if (this.$div) {
            var windowWidth = $(window).width();
            var windowHeight = $(window).height();
            var divWidth = this.$div.outerWidth();
            var divHeight = this.$div.outerHeight();
            var css = {
                display: "block",
                left: (windowWidth - divWidth) / 2
            };
            if (css.left < 0) {
                css.left = 0;
                css.width = windowWidth;
            }
            if (this.top === undefined) {
                //Если пользователь жестко не задал настройку top, то центрирует диалог по вертикали автоматически
                css.top = (windowHeight - divHeight) / 2;
                if (css.top < 0) {
                    css.top = 0;
                    css.height = windowHeight;
                }
            }
            this.$div.css(css);
        }
    };
    Dialog.prototype.close = function () {
        var _this = this;
        // Включаем прокрутку основного окна
        document.body.style.overflow = "";
        $(window).off("resize", this.positiionCenter);
        // Убираем затемнение
        if (this.$overlay) {
            // Иногда на сервере возникала ошибка $overlay is null - возможно, где-то в коде вызывается метод close
            // для уже закрытого диалогового окна, поэтому лучше проверим, что $overlay существует
            this.$overlay.remove();
            this.$overlay = null;
        }
        if (this.$div) {
            if (this.destroyAfterClose) {
                // убирает все обработчики событий и уничтожает div
                this.$div.remove();
                // убираем ссылки на элементы, чтобы не было утечек
                this.$div = null;
                this.$divContent = null;
            }
            else {
                // Если не выставлен флаг this.destroyAfterClose, то просто скроем div содержащий этот диалог
                this.$div.addClass("display-none");
            }
        }
        // Если был какой-то пользовательский обработчик - запустим его
        if (this.afterClose) {
            this.afterClose();
        }
        // Убираем ссылку на текущий объект из стека открытых диалогов
        // Можно было бы убрать последний элемент стека, но не факт, что диалог на котором запустился метод close
        // в данный момент находится на самом верху, поэтому перебираем весь стек, находим текущий диалог и удаляем
        // его оттуда (возможно, даже из середины стека)
        openedDialogsStack = openedDialogsStack.filter(function (openedDialog) { return openedDialog !== _this; });
    };
    Dialog.prototype.blockButtons = function () {
        if (this.$div) {
            this.$div
                .find(".lightdialog__footer")
                .find("a")
                .addClass("disabled");
        }
    };
    Dialog.prototype.unblockButtons = function () {
        if (this.$div) {
            this.$div
                .find(".lightdialog__footer")
                .find("a")
                .removeClass("disabled");
        }
    };
    return Dialog;
}());
exports.Dialog = Dialog;
$(document).on("keyup", function (e) {
    if (e.keyCode === 27) {
        // ESC key
        if (openedDialogsStack.length > 0) {
            //Если есть хотя бы один открытый диалог, убираем закрываем верхний из стека
            openedDialogsStack[openedDialogsStack.length - 1].close();
        }
    }
});
$(document).ajaxComplete(function (_, XMLHttpRequest) {
    if (XMLHttpRequest.getResponseHeader && XMLHttpRequest.getResponseHeader("x-light-showdialog")) {
        var responseText = XMLHttpRequest.responseText;
        if (responseText) {
            var params = Light_1.parseJson(XMLHttpRequest.responseText, false);
            if (params) {
                var options = params.dialogOptions || {};
                if (options.buttonOkHandler) {
                    options.buttonOkHandler = eval(options.buttonOkHandler);
                }
                if (options.afterClose) {
                    options.afterClose = eval(options.afterClose);
                }
                if (options.buttons) {
                    options.buttons = options.buttons.map(function (button) {
                        if (button.handler) {
                            button.handler = eval(button.handler);
                        }
                        return button;
                    });
                }
                new Dialog(params.html, options).open();
            }
            else {
                new Dialog(responseText).open();
            }
        }
    }
});


/***/ }),
/* 4 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var Light_1 = __webpack_require__(0);
// Вспомагательная переменная - ссылка на текущую кнопку рядом с которой открыта палитра
var $currentColorBtn;
var $colorpalette;
var callback = null;
var allCssClasses = ""; // Заготовим текстовую переменную, которая пригодится для removeClass (очистка всех цветов)
for (var i = 1; i <= 12; i++) {
    allCssClasses += "color" + i + " ";
}
function showPalette($targetBtn, onChooseCallback) {
    // Запоминаем кнопку по которой нажал пользователь для открытия палитры - пригодится в будущем
    $currentColorBtn = $targetBtn;
    // Если палитра ещё ни разу не отображалась на экране, её нет в вёрстке. Создаём динамически.
    if (!$colorpalette) {
        $colorpalette = $('<div id="colorpalette-div" class="floatingwidget"><table><tr><td>0</td><td class="color1">1</td><td class="color2">2</td></tr><tr><td class="color3">3</td><td class="color4">4</td><td class="color5">5</td></tr><tr><td class="color6">6</td><td class="color7">7</td><td class="color8">8</td></tr><tr><td class="color9">9</td><td class="color10">10</td><td class="color11">11</td></tr></table></div>');
        // Добавим всплывающие подсказки
        var colorHints = Light_1.getPageParam("ColorHints");
        if (colorHints) {
            for (var i = 1; i <= 11; i++) {
                $colorpalette.find(".color" + i).prop("title", colorHints["ColorHint" + i]);
            }
        }
        // Вставляем в вёрстку
        $colorpalette.appendTo("body");
    }
    else {
        // Палитра уже была создана в вёрствке при прошлом использовании. Возможно, она была скрыта - значит нужно
        // убрать класс скрывающий палитру
        $colorpalette.removeClass("display-none");
    }
    // Перемещаем палитру непосретсвенно рядом с нажатой кнопкой
    var offset = $targetBtn.offset();
    if (offset !== undefined) {
        offset.top += 17;
        $colorpalette.offset(offset);
    }
    callback = onChooseCallback || null;
}
exports.showPalette = showPalette;
function hidePalette() {
    // Обнуляем указатель на "текущую" кнопку
    $currentColorBtn = null;
    // Скрываем виджет
    $colorpalette.addClass("display-none");
    // Очищаем ссылку на функцию callback при закрытии палитры
    callback = null;
}
/**
     * Раскрашивает некий элемент на странице, который символизирует выбранный цвет.
     * Это может быть строка в таблице или div в фильтрах или ячейка в режиме просмотра отдельной записи view/edit
     * @param $container - тот самый элемент, который нужно раскрасить
     * @param newColor - цвет
     */
function updateColorContainer($container, newColor) {
    // Перед тем как добавлять новый класс, удалим все возможные старые значения "color1 color2 color3 ... color11"
    $container.removeClass(allCssClasses);
    if (newColor !== "0") {
        $container.addClass("color" + newColor);
    }
}
// В момент on dom ready инициализируем основные обработчики клика на body
$(function () {
    var $body = $("body");
    $body.on("click", function (e) {
        var $target = $(e.target);
        // Проверим, куда был сделан клик?
        if ($target.hasClass("colorpalette")) {
            // Клик был сделан непосредственно по кнопке палитры.
            // Если палитра была в открытом состоянии и пользователь ещё раз кликнул по текущей кнопке - нужно прсто её закрыть
            if ($currentColorBtn && e.target === $currentColorBtn.get(0)) {
                hidePalette();
            }
            else {
                // Либо палира вообще не была открыта, либо была открыта какая-то другая палитра - не важно,
                // переоткрываем палитру вновь по только что кликнутой кнопке
                showPalette($target);
            }
        }
        else {
            // Клик был сделан куда-то вне кнопки открывающей палитру.
            // Здесь нас интересует только тот случай, если палитра уже была открыта
            if ($currentColorBtn) {
                if (!$.contains($colorpalette.get(0), e.target)) {
                    // Если клик был сделан куда-то вне уже открытой палитры, то её нужно закрыть
                    hidePalette();
                }
                else if (e.target.tagName.toLowerCase() === "td") {
                    // Клик был совершен где-то внутри палитры и попал на одну из td ячеек - применим новый цвет!
                    var newColor = $target.text();
                    // Окрасим элемент-конетйнер внутри которого была расположена текущая кнопка, открывшая палитру
                    var $container = $currentColorBtn.closest(".color-container");
                    if ($container.length === 0) {
                        $container = $currentColorBtn.closest("table.form td, table.table tr");
                    }
                    if ($container.length > 0) {
                        updateColorContainer($container, newColor);
                    }
                    // Если рядом с кнопкой находилось скрытое поле input с классом color-input - запишем туда новое значение цвета
                    $container.find("input.color-input").val(newColor);
                    // Отправим ajax запрос на сервер, если у кнопки есть параметр source
                    var url = $currentColorBtn.attr("data-source");
                    if (url) {
                        url += "&fieldKey=Color&newValue=" + newColor;
                        $.post(url);
                    }
                    if (callback) {
                        callback(newColor);
                    }
                    // Сразу после этого закроем палитру
                    hidePalette();
                }
            }
        }
    });
    // Найдём все скрытые input поля на странице и при любом внешнем изменении (например, если на фильтрах кто-то
    // нажал reset), окрасим и внешний контейнер
    $("input.color-input").on("change", function () {
        var $container = $(this).closest(".color-container");
        if ($container.length > 0) {
            updateColorContainer($container, this.value);
        }
    });
});


/***/ }),
/* 5 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var Light_1 = __webpack_require__(0);
var form = __webpack_require__(1);
var table = __webpack_require__(2);
var Dialog_1 = __webpack_require__(3);
var state = Light_1.getPageParam("state");
// Если сейчас режим Edit или AddNew - проинициализируется jQuery объект формы редактирования
// он понадобится во многих методах дальше
var editForm;
var objectId = Light_1.myParseInt(Light_1.getPageParam("id"));
var validationHandler;
/**
 * Обновляет div в котором находятся выпадающий список с massaction
 * Обновление происходит во время прокрутки таблицы или при выделении строк таблицы
 */
function updateMassactionDiv() {
    var listTable = table.get(Light_1.getModule());
    var $massactionDiv = $(".listmassaction");
    if ($massactionDiv.length === 0) {
        // В этом модуле вообще нет div отвечающего за massaction
        return;
    }
    // Сразу удаляем все total-span, которые могли быть в этом div -
    // в них отображались старые знаения total по полям
    // (ниже будет пересчёт этих total и повторный вывод)
    $massactionDiv.find(".listmassaction__total").remove();
    var $countSpan = $massactionDiv.find('label[for="massaction-select"] > span');
    var selectedCount = listTable.getSelectedCount();
    if (selectedCount === 0) {
        // Если ничего не выделено, больше ничего и не делаем
        $massactionDiv.removeClass("sticky");
        $countSpan.html('');
        return;
    }
    // Если что-то выделено галочками, обязательно добавляем класс sticky, который выставит position: sticky; bottom: 0;
    $massactionDiv.addClass("sticky");
    $countSpan.html(" (" + selectedCount + ")");
    var tableTotalRowCalcFieldsKeys = Light_1.getPageParam("tableTotalRowCalcFieldsKeys");
    if (tableTotalRowCalcFieldsKeys && tableTotalRowCalcFieldsKeys.length) {
        // Для этой таблицы заданы какие-то колонки по которым считается total
        // в случае выделения строк галочками нужно пересчитать total и вывести его в massaction div
        var $thead_1 = listTable.getTableNode().find("thead");
        tableTotalRowCalcFieldsKeys.forEach(function (fieldKey) {
            var $th = $thead_1.find("th[data-fieldkey=\"" + fieldKey + "\"]");
            if ($th.length) {
                // Узнаем порядковый номер колонки, чтобы далее прочитать все значения
                // в выделенных строках
                var colIndex_1 = $thead_1.find("th").index($th);
                // Обычно для подсчёта используются колонки типа суммы
                // Иногда это сумма с валютой
                // Сразу заготовим Map с разбивкой по валютам. Если в данной колонке нет валют
                // то в итоге получим Map из одного элемента (ключём будет пустая валюта - пустая строка)
                var totalPerCurrency_1 = new Map();
                // Пройдём по всем выделенным строкам
                listTable.getSelectedRows().each(function (_, tr) {
                    var tdHtml = $(tr)
                        .find("td")
                        .eq(colIndex_1)
                        .html();
                    // HTML код внутри ячейки может содержать <br>
                    // это значит несколько сумм в режиме groupconcat
                    // разобьём на элементарные строки
                    tdHtml.split("<br>").forEach(function (oneLineHtml) {
                        // Вырежем все теги (которые обычно оформляют суммы)
                        var text = $(oneLineHtml).text();
                        // Попробуем распарсить текст, возможно там какие суммы с валютами?
                        // Например "123 456.78 USD" или "1 234.00 $"
                        // По умолчанию примим валюту пустой
                        var currencyIso = "";
                        var parts = text.split(" ");
                        // Прововерим последнюю часть массива после разбивки по пробелам - там может быть реальная валюта
                        if (!$.isNumeric(parts[parts.length - 1])) {
                            // Нашли валюту в конце числа, вынимаем валюту из массива частей
                            currencyIso = parts.pop();
                        }
                        // Оставшиеся части после изъятия валюты склеиваем обратно
                        var amount = Light_1.myParseFloat(parts.join(""));
                        if (!totalPerCurrency_1.has(currencyIso)) {
                            // Нашли какую-то новую валюту, которая не встречалась ранее
                            totalPerCurrency_1.set(currencyIso, amount);
                        }
                        else {
                            // Эта валюта уже есть в Map totalPerCurrency, прибавляем текующую сумму amount
                            totalPerCurrency_1.set(currencyIso, totalPerCurrency_1.get(currencyIso) + amount);
                        }
                    });
                });
                // После того как прошлись по всем строкам и собрали результат в totalPerCurrency
                // выведем этот результат в massaction div
                var perCurrencyBeautyAmounts_1 = [];
                totalPerCurrency_1.forEach(function (total, currencyIso) {
                    if (total != 0) {
                        perCurrencyBeautyAmounts_1.push(Light_1.getBeautyMoney(total, true, currencyIso));
                    }
                });
                if (perCurrencyBeautyAmounts_1.length > 0) {
                    // Имя поля возьмём из th, чтобы вывести внизу в massaction div
                    var fieldName = $th
                        .html()
                        .replace(/<br>/g, " ")
                        .replace(/<\/?[^>]+>/gi, "")
                        .replace('▼', "")
                        .replace('▲', "")
                        .trim();
                    var $totalSpan = $("<div class=\"listmassaction__total\"></div>").append("<div class=\"listmassaction__totalfieldname\"><strong>" + fieldName + ":&nbsp;</strong></div>");
                    // Склеиваем все суммы по валютам через запятую и доавляем в текущий $totalSpan
                    var amountsHtml = perCurrencyBeautyAmounts_1.join("<br>");
                    $totalSpan.append("<div class=\"listmassaction__totalamount\">" + amountsHtml + "</div>");
                    // Итоговый результат вставляем в $massactionDiv
                    $massactionDiv.append($totalSpan);
                }
            }
        });
    }
}
function initListListeners() {
    try {
        var listTable = table.get(Light_1.getModule());
        listTable.onSelectRow(updateMassactionDiv);
        listTable.onSelectAll(updateMassactionDiv);
    }
    catch (e) {
        console.log(e);
    }
}
function blockSaveButtons(block) {
    editForm
        .getFormNode()
        .find(".btn-save")
        .prop("disabled", block);
}
function showErrorDialog(responseText) {
    // Уберём красную окраску у старых проблемных полей
    $(".input-error").removeClass("input-error");
    // Окрасим новые проблемные поля в красный и добавим title, заодно соберём все сообщения для диалога
    // в отдельный массив
    var dialogBodyHtml;
    var hasCriticalError = false;
    var errors = Light_1.parseJson(responseText, false);
    if (errors === false || errors === null) {
        // Не удалось распарсить json, выведем весь текст ошибки как есть!
        dialogBodyHtml = responseText;
        hasCriticalError = true;
    }
    else {
        // Подсветим все поля с ошибками
        errors.forEach(function (err) {
            if (err.fieldKey) {
                editForm.highlightErrorField(err.fieldKey);
            }
        });
        // Сформируем тело для диалога из списка ошибок
        if (errors.length > 1) {
            dialogBodyHtml = "<ol class=\"save-errors\">" + errors.map(function (_a) {
                var msg = _a.msg;
                return "<li>" + msg + "</li>";
            }).join("") + "</ol>";
        }
        else {
            dialogBodyHtml = errors[0].msg;
        }
        // Проверим, были ли среди ошибок критчные? (хотя бы одна)
        hasCriticalError = errors.some(function (_a) {
            var critical = _a.critical;
            return critical !== undefined && critical;
        });
    }
    var options = {
        buttonCancelText: hasCriticalError ? "Close" : "Cancel",
        afterClose: function () {
            $(".input-error:first").focus();
        }
    };
    if (!hasCriticalError) {
        // Если не было критичных ошибок, то добавим кнопку 'Confirm Save'
        options.buttonOkText = "Confirm Save";
        options.buttonOkHandler = function (dialog) {
            dialog.close();
            // Вызываем метод сохранения с параметром true - чтобы в ajax запрос к серверу передать GET параметр
            // saveanyway=1 - в этом случае на серверной стороне будут проигнорированы все некритиные ошибки
            save(true);
        };
    }
    new Dialog_1.Dialog(dialogBodyHtml, options).open();
}
/**
 * Эта функция может вызываться из validationHandler. Например, VInvoice диалоговое окно
 */
function save(saveanyway, additionalData) {
    blockSaveButtons(true);
    var $editForm = editForm.getFormNode();
    var postData = $editForm.serialize();
    var url = $editForm.attr("action");
    if (saveanyway) {
        url += "&saveanyway=1";
    }
    if (additionalData) {
        //Если в этот метод были принудительно переданы какие-то данные, то добавим их в POST запрос
        //Это могут быть данные из диалога подтверждающего сохранение (если такой был при налаичии не критичных ошибок)
        postData += "&" + $.param(additionalData);
    }
    $.post(url, postData)
        .done(function (_, __, jqXHR) {
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
        .fail(function (jqXHR) {
        blockSaveButtons(false);
        //Если в пришедшем ajax ответе от срвера есть Header заголовок указывающий на то, что ответ содержит в себе
        //какое-то диалоговое окно, то в этом методе ничего делать не будем. Диалог отрисуется средствами js
        if (!jqXHR.getResponseHeader("x-light-showdialog")) {
            showErrorDialog(jqXHR.responseText);
        }
    });
}
exports.save = save;
function getState() {
    return state;
}
exports.getState = getState;
function getObjectId() {
    return objectId;
}
exports.getObjectId = getObjectId;
function getModuleSessionParams() {
    return Light_1.getPageParam("moduleSessionParams");
}
exports.getModuleSessionParams = getModuleSessionParams;
function getFieldNode(fieldKey) {
    return editForm.getFieldNode(fieldKey);
}
exports.getFieldNode = getFieldNode;
function getFieldValue(fieldKey) {
    return editForm.getFieldValue(fieldKey);
}
exports.getFieldValue = getFieldValue;
function setFieldValue(fieldKey, newValue) {
    editForm.setFieldValue(fieldKey, newValue);
}
exports.setFieldValue = setFieldValue;
function onFieldChange(fieldKey, handler) {
    editForm.onFieldChange(fieldKey, handler);
}
exports.onFieldChange = onFieldChange;
function highlightErrorField(fieldKey) {
    getFieldNode(fieldKey).addClass("input-error");
}
exports.highlightErrorField = highlightErrorField;
function ajaxChangeField(id, fieldKey, newValue) {
    return $.post("?a=" + Light_1.getModule() + "&id=" + id + "&func=ChangeField", {
        fieldKey: fieldKey,
        value: newValue
    });
}
exports.ajaxChangeField = ajaxChangeField;
function setValidationHandler(handler) {
    validationHandler = handler;
}
exports.setValidationHandler = setValidationHandler;
//Обработчик сохранения
var $body = $("body");
$body.on("click", "#edit-form .btn-save", function (e) {
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
$body.on("click", ".listmassaction__selectall", function (e) {
    e.preventDefault();
    table.get(Light_1.getModule()).selectAll(true);
});
$body.on("click", ".listmassaction__selectnone", function (e) {
    e.preventDefault();
    table.get(Light_1.getModule()).selectAll(false);
});
$body.on("change", "#massaction-select", function (e) {
    e.preventDefault();
    // Узнаем значение в выпадающем списке - именно он будет именем того massaction, который нужно выполнить
    var select = this;
    var action = select.value;
    var ids;
    //Соберём значения чекбоксов в таблице и отправим их ajax POST запросом
    try {
        ids = table.get(Light_1.getModule()).getSelectedValues();
    }
    catch (e) {
        // Почему-то на экране на нашлась таблица с именем _lightModule - ничего не делаем
        return;
    }
    if (ids.length === 0) {
        Light_1.toastError("Select rows with checkboxes");
        $(this).val("");
        return;
    }
    //Проверим, если была попытка отправить запрос на массовое удаление, нужно переспросить пользователя
    if (action === "delete" && !confirm("Delete selected records?")) {
        select.value = "";
        return;
    }
    var url = "?a=" + Light_1.getModule() + "&p=massaction&action=" + action;
    // Узнаем, следует ли открывать новое окно при сабмите?
    if ($(select)
        .find(":selected")
        .hasClass("massaction-target-blank")) {
        // Нужно открывать в новом окне, т.к. в выбранном option есть специальный css class
        // Чтобы открыть в новом окне и при этом сделать POST запрос - создадим временную форму и засабмитем её!
        $("<form></form>")
            .attr({ action: url, method: "post", target: "_blank" })
            .append("<input type=\"text\" name=\"CheckedIds\" value=\"" + ids.join(",") + "\">")
            .append("<input type=\"hidden\" name=\"csrf_token\" value=\"" + window._lightCsrfToken + "\">")
            .appendTo("body")
            .submit()
            .remove();
        // Сбрасываем выбор в выпадающем списке
        this.value = "";
    }
    else {
        // У этого option не было пометки откывать в новом окне - просто делаем AJAX POST запрос
        // Заблокируем выпадающий список
        select.disabled = true;
        $.post(url, { CheckedIds: ids.join(",") }).always(function () {
            select.disabled = false;
            // Сбрасываем выбор в выпадающем списке
            select.value = "";
        });
    }
});
$body.on("click", "a.mushowhide", function (e) {
    e.preventDefault();
    var $link = $(this);
    // Текущее состояние хранится справа от ссылки в скрытом поле
    var $state = $link.next("input[type=hidden]");
    var newState;
    newState = $state.val() === "hidden" ? "showed" : "hidden";
    $state.val(newState);
    // Теперь найдём само поле в соседнем td и отобразим или скроем его
    $link
        .parent()
        .next("td")
        .find(".mufield")
        .toggleClass("display-none", newState === "hidden");
});
Light_1.listener.simple_combo("ctrl e", function () {
    if (state === "view") {
        document.location.href = "?a=" + Light_1.getModule() + "&editid=" + objectId + "&" + getModuleSessionParams();
    }
});
Light_1.listener.simple_combo("ctrl s", function () {
    if (state === "edit" || state === "addnew") {
        $("#edit-form")
            .find(".btn-save")
            .first()
            .trigger("click");
    }
});
if (state === "edit" || state === "addnew") {
    editForm = form.get("edit-form");
    // Передаём фокус на элемент Name
    $("input[name=Name]").focus();
}
else if (state === "list") {
    initListListeners();
}


/***/ }),
/* 6 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var Light_1 = __webpack_require__(0);
var Module_1 = __webpack_require__(5);
var Dialog_1 = __webpack_require__(3);
var Subtable = /** @class */ (function () {
    function Subtable($div) {
        var _this = this;
        //+Для различный операций над ячейками неплохо было бы закешировать индексы полей
        this.fieldKeys = [];
        this.hiddenColumns = [];
        this.copyOnAddFieldKeys = [];
        //Хеш-массивы fieldKey => [...changeHandlers...]
        this.fieldCalcTotalHandlers = {};
        this.onAddHandlers = [];
        this.onDelHandlers = [];
        this.onCalcTotalHandlers = [];
        //Кеширующий объект, в качестве клюа выступает связка rowNum + fieldKey
        this.fieldNodesCache = {};
        //Грязный хак с глобальным флагом: когда вызывается метод addRows он добавляет несколько строк за один раз
        //Этот метод может быть вызван из клиентского скрипта (например, копирование Cost-sheet) или в результате paste диалога
        //При каждом добавлении строки в неё записываются какие-то данные и срабатывают обработчики onChange на отдельных полях
        //если какое-то поле подразумевает под собой подсчёт total в столбце, то автоматически запустится calcTotal по этому столбцу
        //и так на каждую вставленную строку!
        //Решение: введём глобальный флаг, если он установлен в true, метод calcTotal ничего не будет делать!
        this.calcTotalEnabled = true;
        this.$div = $div;
        this.fields = Light_1.parseJson($div.attr("data-fields"));
        this.name = $div.attr("data-subtablename");
        this.$table = $div.find("table.table").first();
        this.$thead = this.$table.children("thead").first();
        this.$tbody = this.$table.children("tbody").first();
        this.$countSpan = $("." + this.name + "-subtable-count-span");
        this.tbodyEl = this.$tbody.get(0);
        this.totalRowEl = this.$tbody.find("#" + this.name + "-subtable-tr-total").get(0);
        this.idsEl = $div.find("#" + this.name + "-subtable-ids").get(0);
        this.delRowsEl = $div.find("#" + this.name + "-subtable-delrows").get(0);
        //Сформируем массив colIndex => fieldKey
        for (var fieldKey in this.fields) {
            this.fields[fieldKey].colIndex = this.fieldKeys.length;
            this.fieldKeys.push(fieldKey);
            this.hiddenColumns.push(this.$thead.find("th[data-fieldkey=\"" + fieldKey + "\"]").hasClass("display-none"));
            //Сформируем массив из ключей полей, которые нужно копировать при добавлении новой строки
            if (this.fields[fieldKey].copyOnAdd) {
                this.copyOnAddFieldKeys.push(fieldKey);
            }
        }
        var that = this;
        //Добавляем обработчик на ссылку добавления новой строки
        $div.find(".subtable-add-link").on("click", function (e) {
            e.preventDefault();
            var data = {};
            if (_this.copyOnAddFieldKeys.length > 0) {
                var $lastTr = that.$tbody
                    .find("tr")
                    .not(".total")
                    .last();
                if ($lastTr.length > 0) {
                    var lastRowNum_1 = getRowNum($lastTr);
                    //Нашли последнюю строку, вычленим данные по полям из массива copyOnAddFieldKeys
                    _this.copyOnAddFieldKeys.forEach(function (fieldKey) {
                        data[fieldKey] = _this.getFieldValue(lastRowNum_1, fieldKey).toString();
                        if (_this.fields[fieldKey].type === "MoneyWithCurrency") {
                            //Если это поле MoneyWithCurrency, то будем копировать не всё значение, а только валюту
                            data[fieldKey] = data[fieldKey].substring(0, 3) + " 0";
                        }
                    });
                }
            }
            //Вызываем публичный метод addRow - он пересчитывает total
            that.addRow(data);
        });
        $div.find(".change-all").on("click", function (e) {
            e.preventDefault();
            //Hide changeAll button
            var $link = $(e.currentTarget);
            $link.addClass("display-none");
            //Отобразим выпадающий список
            var $node = $link.next(".display-none");
            $node.removeClass("display-none");
            var nodeTag = $node.prop("tagName");
            var inputType = $node.attr("type");
            var fieldKey = $link.attr("data-fieldkey");
            //Повесим на него обработчик изменения, который обновит все строки
            $node.on("change keyup", function (e) {
                var input = e.currentTarget;
                var val;
                if (nodeTag === "INPUT" && inputType === "checkbox") {
                    val = input.checked ? 1 : 0;
                }
                else {
                    val = input.value;
                }
                _this.eachRow(function (rowNum) {
                    _this.setFieldValue(rowNum, fieldKey, val);
                });
            });
        });
        $div.find(".change-all-string").on("click", function (e) {
            e.preventDefault();
            var $link = $(e.currentTarget);
            var fieldKey = $link.attr("data-fieldkey");
            // отобразим диалог с выбором полной замены текста или только часть (с поиском)
            var that = _this;
            var dialog = new Dialog_1.Dialog("\n                        <div>\n                            <div><label><input type=\"radio\" name=\"type-update-string\" value=\"update\" checked=\"checked\"> \u041E\u0431\u043D\u043E\u0432\u0438\u0442\u044C \u043F\u043E\u043B\u043D\u043E\u0441\u0442\u044C\u044E</label></div>\n                            <div class=\"area-update\" style=\"padding: 1em;\">\n                                <input type=\"text\" class=\"input-default\" name=\"text-update\" placeholder=\"\u041E\u0431\u043D\u043E\u0432\u0438\u0442\u044C \u043D\u0430\">\n                            </div>\n                            <div style=\"margin-top: 0.5em;\"><label><input type=\"radio\" name=\"type-update-string\" value=\"replace\"> \u0417\u0430\u043C\u0435\u043D\u0438\u0442\u044C \u0447\u0430\u0441\u0442\u044C</label></div>\n                            <div class=\"area-replace display-none\" style=\"padding: 1em;\">\n                                <div><input type=\"text\" class=\"input-default\" name=\"text-find\" placeholder=\"\u0418\u0441\u043A\u043E\u043C\u044B\u0439 \u0442\u0435\u043A\u0441\u0442\"></div>\n                                <div><input type=\"text\" class=\"input-default\" name=\"text-replace\" placeholder=\"\u0417\u0430\u043C\u0435\u043D\u0438\u0442\u044C \u043D\u0430\"></div>\n                            </div>\n                      </div>\n                    ", {
                width: "360px",
                buttonOkText: "Заменить",
                buttonOkHandler: function (dialog) {
                    var type = dialog.$divContent.find("input[type='radio']:checked").val();
                    if (type == "update") {
                        var text_1 = dialog.$divContent.find("input[name='text-update']").val();
                        _this.eachRow(function (rowNum) {
                            _this.setFieldValue(rowNum, fieldKey, text_1);
                        });
                    }
                    else if (type == "replace") {
                        var find_1 = dialog.$divContent.find("input[name='text-find']").val();
                        var replace_1 = dialog.$divContent.find("input[name='text-replace']").val();
                        that.eachRow(function (rowNum) {
                            var text = that.getFieldValue(rowNum, fieldKey);
                            text = text.toString().replace(find_1, replace_1);
                            _this.setFieldValue(rowNum, fieldKey, text);
                        });
                    }
                    dialog.close();
                }
            });
            dialog.open();
            dialog.$divContent.find("input[type='radio']").on("change", function (e) {
                var type = $(e.target).val();
                dialog.$divContent.find("div[class^='area']").addClass("display-none");
                dialog.$divContent.find("div.area-" + type).removeClass("display-none");
            });
        });
        //Добавляем обработчик на ссылку вставки нескольких строк
        $div.find(".subtable-paste-link").on("click", function (e) {
            e.preventDefault();
            _this.showPasteDialog();
        });
        //Добавим обработчик на ссылки удаления строк
        $div.on("click", ".subtable-del-link", function (e) {
            e.preventDefault();
            //Определим номер удаляемой строки
            var deleteRowNum = getRowNum($(e.currentTarget));
            _this.delRow(deleteRowNum);
        });
        $div.on("click", "a.table-sort-link", function (e) {
            e.preventDefault();
            var currentTarget = e.currentTarget;
            var params = currentTarget.hash.substring(1).split("|");
            var sortFieldKey = params[0], sortOrder = params[1];
            currentTarget.hash = "#" + sortFieldKey + "|" + (sortOrder === "ASC" ? "DESC" : "ASC");
            var rows = [];
            _this.eachRow(function (rowNum) {
                var $row = _this.getRowNode(rowNum);
                rows.push({
                    row: $row,
                    sortValue: _this.getFieldValue(rowNum, sortFieldKey)
                });
                $row.find("input.input-date").datepicker("destroy");
                $row.remove();
            });
            rows = rows.sort(function (a, b) {
                if (a.sortValue > b.sortValue) {
                    return sortOrder === "ASC" ? 1 : -1;
                }
                else if (a.sortValue < b.sortValue) {
                    return sortOrder === "ASC" ? -1 : 1;
                }
                return 0;
            });
            _this.$tbody.append(rows.map(function (r) { return r.row; }));
            Light_1.addStandardListeners(_this.$tbody);
            _this.$thead.find("th span.tr-sort-icon").remove();
            var icon = sortOrder === "DESC" ? "▲" : "▼";
            _this.$thead
                .find("th[data-fieldkey=\"" + sortFieldKey + "\"]")
                .append('<span class="tr-sort-icon">' + icon + "</span>");
            $.post("index.php?a=Core&ps=SetTableUserParams", {
                table: Light_1.getModule() + _this.name,
                sortFieldKey: sortFieldKey,
                sortOrder: sortOrder
            });
        });
        // Добавим обработчики на пересчёт Total при изменнии полей у которых стоит флаг calcTotal
        this.fieldKeys.filter(function (fieldKey) { return _this.fields[fieldKey].calcTotal; }).forEach(function (fieldKey) {
            _this.onFieldChange(fieldKey, function () {
                _this.calcTotal(fieldKey);
                // Запустим общие обрабочики события calcTotal
                _this.onCalcTotalHandlers.forEach(function (handler) {
                    handler.call(_this);
                });
            });
        });
        // Пересчитаем все Total поля
        this.calcTotalAll();
        this.updateCountSpan();
    }
    Subtable.prototype.disableCalcTotal = function () {
        this.calcTotalEnabled = false;
    };
    Subtable.prototype.enableCaclTotal = function () {
        this.calcTotalEnabled = true;
        this.calcTotalAll();
        this.updateCountSpan();
    };
    Subtable.prototype.showPasteDialog = function () {
        var that = this;
        var dialog = new Dialog_1.Dialog('<textarea class="paste-textarea" rows="24"></textarea>', {
            width: "560px",
            buttonOkText: Light_1.isLangRu ? "Вставить" : "Paste",
            buttonOkHandler: function (dialog) {
                var startMs = performance.now();
                //Переберём все строки из вставленного текста
                var text = dialog.$div.find("textarea").val();
                var lines = text.split("\n");
                //Удаляем пустые строки
                lines = lines.filter(function (line) { return line != ""; });
                //Сформируем массив из строк для вставки (строки готовые для передачи в метод addRow()
                var rowsData = lines.map(function (line) {
                    var lineData = line.split("\t");
                    var rowData = {};
                    //Перебираем поля и вынимаем из массива lineData ячейки одна за одной
                    for (var fieldKey in that.fields) {
                        //Если для данного поля вставка из буфера не запрещена, используем текущее значение из lineData
                        if (!that.fields[fieldKey].disablePaste && !that.isColHidden(fieldKey)) {
                            rowData[fieldKey] = lineData.shift() || "";
                        }
                    }
                    return rowData;
                });
                //Теперь можно вставить все сформированные строки (метод addRows() сам пересчитает total и обновит count span)
                that.addRows(rowsData);
                var workTimeMs = performance.now() - startMs;
                if (workTimeMs > 1000) {
                    // Если вставка на стороне JavaScript заняла более секунды, то зарегистрируем это событие на сервере
                    Light_1.postMetric("subtablepaste", workTimeMs, {
                        lines: rowsData.length,
                        module: Light_1.getModule(),
                        id: Module_1.getObjectId(),
                        subtable: that.getName(),
                        text: text
                    });
                }
                dialog.close();
            }
        });
        dialog.open();
        var $textarea = dialog.$div.find("textarea");
        $textarea.focus();
        $textarea.on("keydown", function (e) {
            var key = e.keyCode, st = $textarea.prop("scrollTop"), selStart = $textarea.prop("selectionStart"), selEnd = $textarea.prop("selectionEnd");
            var value = $textarea.val();
            if (key == 9) {
                e.preventDefault();
                //Only insert a tab when nothing is selected.
                if (selStart === selEnd) {
                    value = value.substring(0, selStart) + String.fromCharCode(9) + value.substring(selStart);
                    $textarea.val(value);
                }
                $textarea.prop("scrollTop", st);
            }
        });
    };
    /**
     * Пользователь может указать в вёрстке специальный тег (или несколько) с классом <subtableName>-count-span и тогда
     * при каждом добавлении или удалении строки в этот span будет записываться текущее число строк
     */
    Subtable.prototype.updateCountSpan = function () {
        this.$countSpan.html(this.getDataCount().toString());
    };
    Subtable.prototype.calcTotalAll = function () {
        var _this = this;
        if (!this.calcTotalEnabled) {
            return;
        }
        //Перебираем все поля в таблице и при нахождении полей с флагом calcTotal - вызываем пересчёт поля
        for (var fieldKey in this.fields) {
            if (this.fields[fieldKey].calcTotal) {
                this.calcTotal(fieldKey);
            }
        }
        // Запустим общие обрабочики события calcTotal
        this.onCalcTotalHandlers.forEach(function (handler) {
            handler.call(_this);
        });
    };
    Subtable.prototype.getTotalValue = function (fieldKey) {
        var fieldValues = this.getFieldValues(fieldKey);
        if (fieldValues.length === 0) {
            return 0;
        }
        var len = fieldValues.length;
        var total = 0;
        // Если это тип MoneyWithCurrency, то считать нужно особым образом
        var isMoneyWithCurrency = this.fields[fieldKey].type === "MoneyWithCurrency";
        if (isMoneyWithCurrency) {
            var firstCurrency = fieldValues[0].substring(0, 3);
            for (var i = 0; i < len; i++) {
                var _a = Light_1.getMoneyWithCurrencyAsTouple(fieldValues[i]), currencyIso = _a[0], amount = _a[1];
                if (currencyIso !== firstCurrency) {
                    // Нашли валюту отличную от первой - значит набор валют содержит более одного уникального
                    // значения и мы не можем посчитать общую сумму
                    return false;
                }
                total += amount;
            }
            // Вернуть надо вместе с валютой в виде строки
            return firstCurrency + " " + total;
        }
        // Обычное числовое поле (не MoneyWithCurrency)
        for (var i = 0; i < len; i++) {
            total += Light_1.myParseFloat(fieldValues[i]);
        }
        return total;
    };
    Subtable.prototype.calcTotal = function (fieldKey) {
        if (!this.calcTotalEnabled) {
            return;
        }
        //Посчитаем total в столбик по всем значениям
        var total = this.getTotalValue(fieldKey);
        //Запишем total в специально подготовленную ячейку
        var $totalTd = this.getTotalTdNode(fieldKey);
        if ($totalTd.length > 0) {
            var totalHtml = void 0;
            if (total === false) {
                totalHtml = "";
            }
            else {
                //Если это тип Money или MoneyWithCurrency - нужно привести к красивому виду перед записью в таблицу
                var type = this.fields[fieldKey].type;
                if (type === "Money" || type === "MoneyWithCurrency") {
                    totalHtml = Light_1.getBeautyValue(total, type);
                }
                else {
                    //Это не деньги, значит простой числовой тип (вес или объём)
                    //Округлим до того количества знаков после запятой, которое указано в параметре precision
                    var precision = this.fields[fieldKey].precision;
                    if (precision === undefined) {
                        precision = 2;
                    }
                    totalHtml = Light_1.round(total, precision).toString();
                }
            }
            $totalTd.html(totalHtml);
        }
        //Запустим все обработчики события onCalcTotalField приязанные к этому конрктеному полю
        if (this.fieldCalcTotalHandlers[fieldKey] !== undefined) {
            var handlers = this.fieldCalcTotalHandlers[fieldKey];
            for (var i = 0; i < handlers.length; ++i) {
                handlers[i].call(this, total);
            }
        }
    };
    Subtable.prototype.getMaxRowNum = function () {
        var idsValue = this.idsEl.value;
        if (idsValue === "") {
            //Таблица была пуста, нет номера максимальной строки
            return -1;
        }
        return idsValue.split(",").length - 1;
    };
    /**
     * Возвращает число строк в подтаблице
     * @return {int}
     */
    Subtable.prototype.getDataCount = function () {
        // Посчитаем число id строк записанных в скротом элементе ids и вычтем число строк записанных в delrows
        var idsValue = this.idsEl.value;
        if (idsValue === "") {
            //Таблица пуста
            return 0;
        }
        var rowsCount = idsValue.split(",").length;
        if (this.delRowsEl) {
            var delrowsValue = this.delRowsEl.value;
            if (delrowsValue !== "") {
                rowsCount -= delrowsValue.split(",").length;
            }
        }
        return rowsCount;
    };
    /**
     * Возвращает массив из объектов с данными
     */
    Subtable.prototype.getData = function () {
        var _this = this;
        var data = [];
        this.eachRow(function (rowNum) {
            data.push(_this.getRowData(rowNum));
        });
        return data;
    };
    /**
     * Возвращает объект с данными из указанной строки
     */
    Subtable.prototype.getRowData = function (rowNum) {
        var rowData = {};
        //Перебираем поля и вынимаем из массива ячейки одна за одной
        for (var fieldKey in this.fields) {
            if (fieldKey !== "Del") {
                rowData[fieldKey] = this.getFieldValue(rowNum, fieldKey);
            }
        }
        return rowData;
    };
    Subtable.prototype.eachRow = function (callback) {
        // Перебираем все строки до максимального номера
        // При этом если порядковый номер строки есть среди delrows - значит она удалена и её использовать не нужно
        var maxRowNum = this.getMaxRowNum();
        var delrows = this.delRowsEl ? this.delRowsEl.value.split(",") : [];
        for (var rowNum = 0; rowNum <= maxRowNum; rowNum++) {
            if (delrows.indexOf(rowNum.toString()) !== -1) {
                // Текущая строка есть среди удалённых
                continue;
            }
            var result = callback.call(this, rowNum);
            // Если пользовательская функция вернула false - прерываем цикл
            if (result === false) {
                break;
            }
        }
    };
    Subtable.prototype.addRow = function (data) {
        //Номер следующей строки определим исходя из содержимого скрытого элемента, в котором пепречислены id всех строк
        var newRowNum = 0;
        var idsValue = this.idsEl.value;
        if (idsValue) {
            //Таблица была не пуста
            var ids = idsValue.split(",");
            newRowNum = ids.length;
            //Обязательно добавляем в конец скрытого поля ноль = ID элемента в базе (ведь элемент новый и его ещё в базе не существует)
            this.idsEl.value += ",0";
        }
        else {
            //Таблица была пуста
            //Обязательно добавляем в конец скрытого поля ноль = ID элемента в базе (ведь элемент новый и его ещё в базе не существует)
            this.idsEl.value = "0";
        }
        //Создадим новую строку
        var tr = document.createElement("tr");
        tr.id = this.name + "-subtable-tr-" + newRowNum;
        var tdsHtml = "";
        var fieldParams;
        for (var fieldKey in this.fields) {
            tdsHtml += '<td class="';
            fieldParams = this.fields[fieldKey];
            if (fieldParams.tableAlign) {
                tdsHtml += "ta" + fieldParams.tableAlign[0];
            }
            if (this.isColHidden(fieldKey)) {
                tdsHtml += " display-none";
            }
            tdsHtml += '">' + fieldParams.html + "</td>";
        }
        tdsHtml = tdsHtml.replace(/\{NewRowNum\}/g, newRowNum.toString());
        tr.innerHTML = tdsHtml;
        //Добавим созданную строку к таблице перед строкой total
        //Используем нативные элементы и нативный метод insertBefore для скорости (особенно актуально при использовании
        //paste большого числа строк)
        if (this.totalRowEl) {
            //Если есть total строка, то вставляем в tbody перед этой total строкой
            //https://developer.mozilla.org/ru/docs/Web/API/Node/insertBefore
            this.tbodyEl.insertBefore(tr, this.totalRowEl);
        }
        else {
            //Если total строки не было, то просто вставляем в конец tbody
            this.tbodyEl.appendChild(tr);
        }
        //Если передан объект data, нужно заполнить поля ввода переданными данными
        //Внутри этого метода будет произведён пересчёт total (если обновление total влючено флагом this.calcTotalEnabled)
        if (data) {
            this.setRowData(newRowNum, data);
        }
        //Сформируем jQuery объект из вставленной строки
        var $newRow = $(tr);
        Light_1.addStandardListeners($newRow);
        //Если не было заблокировано пересчёт total, то нужно заодно и обновить так называемсый count-span - элемент
        //в котором отображается общее число строк.
        //(если же пресчёт total был заблокирован? скорее всего мы находимся где-то внутри
        //цикла вставки нескольких строк, поэтому нет смысла обновлять count-span после каждой вставки строки. Этот метод
        //будет вызыван внешним кодом после окончания цикла)
        if (this.calcTotalEnabled) {
            this.calcTotalAll();
            this.updateCountSpan();
        }
        //Вызовем все обработчики подписанные на событие добавление строки
        var onAddHandlersLength = this.onAddHandlers.length;
        for (var i = 0; i < onAddHandlersLength; ++i) {
            this.onAddHandlers[i].call(this, newRowNum, $newRow);
        }
        return { newRowNum: newRowNum, newRowNode: $newRow };
    };
    Subtable.prototype.setRowData = function (rowNum, data) {
        var $fieldNode, newValue, field;
        for (var fieldKey in this.fields) {
            if (data[fieldKey] === undefined) {
                continue;
            }
            //Проверим, что для данного поля существует DOM элемент. Заодно $newFieldNode пригодится для проверки это select или нет
            $fieldNode = this.getFieldNode(rowNum, fieldKey);
            if ($fieldNode.length === 0) {
                continue;
            }
            field = this.fields[fieldKey];
            newValue = data[fieldKey];
            if (field.type === "MoneyWithCurrency") {
                //Для поля MoneyWithCurrency нужно дополнительно обработать newValue
                //Например, при вставке из диалогового окна paste пользователь может указать чисто сумму без валюты
                if (newValue.indexOf(" ") === -1) {
                    //Нет пробела, значит это чисто сумма - добавим вперёд валюту по умолчанию
                    newValue = "USD " + newValue;
                }
            }
            else {
                //Сделаем исключение для поля типа Select
                // нужно сделать поиск по всем элементам option на предмет совпадения текса
                if ($fieldNode.prop("nodeName").toLowerCase() === "select") {
                    //Проверим наличие option с таким же текстом, как value
                    if (field._optionsHash === undefined) {
                        field._optionsHash = {};
                        $fieldNode.find("option").each(function () {
                            field._optionsHash[this.innerHTML] = this.value;
                        });
                    }
                    if (field._optionsHash[newValue] !== undefined) {
                        newValue = field._optionsHash[newValue];
                    }
                }
            }
            this.setFieldValue(rowNum, fieldKey, newValue);
        }
    };
    Subtable.prototype.addRows = function (rowsData) {
        this.disableCalcTotal();
        for (var i = 0; i < rowsData.length; i++) {
            this.addRow(rowsData[i]);
        }
        this.enableCaclTotal();
    };
    Subtable.prototype.delRow = function (rowNum) {
        //Найдём строку по номеру
        var $trNode = this.getRowNode(rowNum);
        if ($trNode.length === 0) {
            return false;
        }
        //Физически удалим строку из таблицы (из DOM дерева)
        $trNode.remove();
        //Добавим номер удалённой строки в скрытое поле, чтобы потом обработать на стороне php
        if (this.delRowsEl.value === "") {
            this.delRowsEl.value = rowNum.toString();
        }
        else {
            this.delRowsEl.value += "," + rowNum;
        }
        //Вызовем все обработчики подписанные на событие удаление строки
        for (var i = 0; i < this.onDelHandlers.length; ++i) {
            this.onDelHandlers[i].call(this, rowNum);
        }
        if (this.calcTotalEnabled) {
            this.calcTotalAll();
            this.updateCountSpan();
        }
        return true;
    };
    Subtable.prototype.empty = function () {
        var _this = this;
        this.disableCalcTotal();
        this.eachRow(function (rowNum) {
            _this.delRow(rowNum);
        });
        this.enableCaclTotal();
    };
    Subtable.prototype.getRowNode = function (rowNum) {
        return this.$tbody.find("#" + this.name + "-subtable-tr-" + rowNum);
    };
    /**
     * Если это поле в режиме view, то метод возвразает td ячейку
     * Если же поле в режиме редактирования, то возвращается input/select элемент
     */
    Subtable.prototype.getFieldNode = function (rowNum, fieldKey) {
        if (!this.fields[fieldKey]) {
            throw new Error("Subtabe " + this.name + ": uknown field " + fieldKey);
        }
        var cacheKey = rowNum + "-" + fieldKey;
        if (this.fieldNodesCache[cacheKey]) {
            return this.fieldNodesCache[cacheKey];
        }
        var $tdNode = this.getTdNode(rowNum, fieldKey);
        if (this.fields[fieldKey].mode === "view") {
            //Для поля находящегося в режиме view возвращаем td ячейку
            this.fieldNodesCache[cacheKey] = $tdNode;
            return $tdNode;
        }
        var type = this.fields[fieldKey].type;
        if (type === "MoneyWithCurrency") {
            var $result = Light_1.getFieldNode($tdNode, this.name + "Subtable[" + rowNum + "][" + fieldKey + "]", "MoneyWithCurrency");
            this.fieldNodesCache[cacheKey] = $result;
            return $result;
        }
        var $fieldNode = $tdNode.find("input");
        if ($fieldNode.length > 0) {
            this.fieldNodesCache[cacheKey] = $fieldNode;
            return $fieldNode;
        }
        $fieldNode = $tdNode.find("select");
        if ($fieldNode.length > 0) {
            this.fieldNodesCache[cacheKey] = $fieldNode;
            return $fieldNode;
        }
        $fieldNode = $tdNode.find("textarea");
        if ($fieldNode.length > 0) {
            this.fieldNodesCache[cacheKey] = $fieldNode;
            return $fieldNode;
        }
        throw new Error("Subtabe " + this.name + ": uknown field " + fieldKey);
    };
    Subtable.prototype.getFieldValue = function (rowNum, fieldKey) {
        if (!this.fields[fieldKey]) {
            throw new Error("Subtabe " + this.name + ": uknown field " + fieldKey);
        }
        if (this.fields[fieldKey].mode === "view") {
            //Для поля находящегося в режиме View парсим из текста внутри td
            return Light_1.parseTextValue(this.getTdNode(rowNum, fieldKey).text(), this.fields[fieldKey].type);
        }
        //Для режим Edit парсим из поля ввода
        return Light_1.getNodeValue(this.getFieldNode(rowNum, fieldKey), this.fields[fieldKey].type);
    };
    /**
     * Возвращает массив со значениями определённого поля (колонку)
     */
    Subtable.prototype.getFieldValues = function (fieldKey) {
        var _this = this;
        var out = [];
        this.eachRow(function (rowNum) {
            var value = _this.getFieldValue(rowNum, fieldKey);
            out.push(value);
        });
        return out;
    };
    Subtable.prototype.setFieldValue = function (rowNum, fieldKey, newValue, triggerChange) {
        if (triggerChange === void 0) { triggerChange = true; }
        if (this.fields[fieldKey] === undefined) {
            return;
        }
        var field = this.fields[fieldKey];
        var $node = this.getFieldNode(rowNum, fieldKey);
        if (field.mode === "view") {
            // Для поля находящегося в режиме view просто запишем значение в html код ячейки
            // При этом значение нужно обренуть в вызов функции getBeautyValue(), чтобы было красиво!
            $node.html(Light_1.getBeautyValue(newValue, field.type));
        }
        else {
            // Для поля находящегося в режиме edit, записываем значение в поле ввода
            // но только если это поле не находится в состоянии disabled
            if (!$node.prop('disabled')) {
                Light_1.setNodeValue($node, newValue, field.type, triggerChange && this.calcTotalEnabled);
            }
        }
        // Обновим Total по этому полю, если у него есть флаг calcTotal и при вызове этого метода не было запрещено обновлять total
        if (this.calcTotalEnabled && field.calcTotal) {
            this.calcTotal(fieldKey);
        }
    };
    Subtable.prototype.getTdNode = function (rowNum, fieldKey) {
        var colIndex = this.fields[fieldKey].colIndex;
        return this.getRowNode(rowNum)
            .find("td")
            .eq(colIndex);
    };
    Subtable.prototype.getTotalTdNode = function (fieldKey) {
        return this.getTdNode("total", fieldKey);
    };
    Subtable.prototype.getDivNode = function () {
        return this.$div;
    };
    Subtable.prototype.showHideCol = function (fieldKey, show) {
        //Все ячейки td и заголовчная ячейка th имеют атрибут data-fieldkey
        var colIndex = this.fields[fieldKey].colIndex;
        this.$table
            .find("tr")
            .find("td:eq(" + colIndex + "), th:eq(" + colIndex + ")")
            .toggleClass("display-none", !show);
        this.hiddenColumns[colIndex] = !show;
    };
    Subtable.prototype.hideCol = function (fieldKey) {
        this.showHideCol(fieldKey, false);
    };
    Subtable.prototype.showCol = function (fieldKey) {
        this.showHideCol(fieldKey, true);
    };
    Subtable.prototype.isColHidden = function (fieldKey) {
        var colIndex = this.fields[fieldKey].colIndex;
        return this.hiddenColumns[colIndex] === true;
    };
    Subtable.prototype.getName = function () {
        return this.name;
    };
    Subtable.prototype.onFieldCalcTotal = function (fieldKey, handler) {
        this.fieldCalcTotalHandlers[fieldKey] = this.fieldCalcTotalHandlers[fieldKey] || [];
        this.fieldCalcTotalHandlers[fieldKey].push(handler);
    };
    Subtable.prototype.onCalcTotal = function (handler) {
        this.onCalcTotalHandlers.push(handler);
    };
    Subtable.prototype.onAdd = function (handler) {
        this.onAddHandlers.push(handler);
    };
    Subtable.prototype.onDel = function (handler) {
        this.onDelHandlers.push(handler);
    };
    Subtable.prototype.onFieldChange = function (fieldKey, handler) {
        var _this = this;
        // Сначала проверим, что это поле есть среди списка полей
        if (this.fields[fieldKey] === undefined) {
            throw new Error("Unknown field " + fieldKey + " in subtable " + this.name);
        }
        var changeHandler = function (e) {
            var rowNum = getRowNum($(e.currentTarget));
            var newValue = _this.getFieldValue(rowNum, fieldKey);
            var $node = _this.getFieldNode(rowNum, fieldKey);
            handler.call(_this, rowNum, newValue, $node);
        };
        // Просто добавим делегат на всю таблицу
        this.$tbody.on("change keyup", "input[name$=\"[" + fieldKey + "]\"]", changeHandler);
        this.$tbody.on("change keyup", "textarea[name$=\"[" + fieldKey + "]\"]", changeHandler);
        this.$tbody.on("change", "select[name$=\"[" + fieldKey + "]\"]", changeHandler);
    };
    Subtable.prototype.hasField = function (fieldKey) {
        return this.fields[fieldKey] !== undefined;
    };
    return Subtable;
}());
exports.Subtable = Subtable;
var subtablesStore = {};
function init($root) {
    $root.find("div.subtable-div").each(function () {
        // Проверим, есть ли в этом div атрибут data-fields с описанием полей? Если есть, создадим объект Subtable
        var $div = $(this);
        // Проверим, что эта таблица находится в режиме редактирования по атрибуту data-fields
        if ($div.attr("data-fields")) {
            var subtableObj = new Subtable($div);
            var name_1 = subtableObj.getName();
            subtablesStore[name_1] = subtableObj;
        }
    });
}
exports.init = init;
function getByNode($node) {
    var subtableName = $node.closest("div.subtable-div").attr("data-subtablename");
    if (!subtableName) {
        throw new Error("Subtable not found by node: " + $node);
    }
    return get(subtableName);
}
exports.getByNode = getByNode;
function get(name) {
    if (subtablesStore[name] === undefined) {
        throw new Error("Unknown subtable: " + name);
    }
    return subtablesStore[name];
}
exports.get = get;
function getRowNum($node) {
    var id = $node.closest("tr").attr("id");
    if (!id) {
        throw new Error("No row num at Subtable row node " + $node);
    }
    var parts = id.split("-");
    if (typeof parts[3] === "undefined") {
        throw new Error("Wrong row num at Subtable row node " + $node);
    }
    return Light_1.myParseInt(parts[3]);
}
exports.getRowNum = getRowNum;
function has(name) {
    return subtablesStore[name] !== undefined;
}
exports.has = has;
// Инициализируем подтаблицы
init($(document.body));


/***/ }),
/* 7 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

function __export(m) {
    for (var p in m) if (!exports.hasOwnProperty(p)) exports[p] = m[p];
}
Object.defineProperty(exports, "__esModule", { value: true });
var Light_1 = __webpack_require__(0);
__webpack_require__(9);
__webpack_require__(4);
__webpack_require__(10);
var colopalette = __webpack_require__(4);
exports.colopalette = colopalette;
__webpack_require__(11);
var currency = __webpack_require__(12);
exports.currency = currency;
var forms = __webpack_require__(1);
exports.forms = forms;
var table = __webpack_require__(2);
exports.table = table;
exports.tables = table;
var module = __webpack_require__(5);
exports.module = module;
var subtable = __webpack_require__(6);
exports.subtable = subtable;
exports.subtables = subtable;
__export(__webpack_require__(0));
__export(__webpack_require__(3));
__export(__webpack_require__(14));
var Light_2 = __webpack_require__(0);
exports.parseInt = Light_2.myParseInt;
exports.parseFloat = Light_2.myParseFloat;
//На событие dom ready навешиваем эти стандратные обработчики
$(function () {
    "use strict";
    var $body = $(document.body);
    Light_1.addStandardListeners($body);
    // Добавим плавающие шапки таблиц в момент загрузки страницы
    if (!Light_1.getPageParam("disableTableSticky")) {
        $body.find("table.table:not(.nosticky)").each(function () {
            var $table = $(this);
            var offset = Light_1.myParseInt($table.attr("data-stickyoffset"));
            $table.stickyTableHeaders({ fixedOffset: offset });
        });
    }
});


/***/ }),
/* 8 */
/***/ (function(module, exports) {

(function() { module.exports = window["_"]; }());

/***/ }),
/* 9 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var Light_1 = __webpack_require__(0);
var $body = $("body");
var textFail = Light_1.isLangRu ? "Не удалось изменить" : "Update failed";
var textSave = Light_1.isLangRu ? "Сохранить" : "Save";
var textCancel = Light_1.isLangRu ? "Отмена" : "Cancel";
$body.on("click", "a.ajax-checkbox", function (e) {
    e.preventDefault();
    var $link = $(this);
    // Найдём ближайший тег контейнер в котором описан url на который делать запрос
    var $container = $link.closest("*[data-ajaxchangefieldurl]");
    if ($container.length === 0) {
        throw new Error("Element container *[data-ajaxchangefieldurl] not found for ajax field " + this.toString());
    }
    // Ставим на время load-картинку
    var $img = $('<img src="lightpublic/i/circle.gif">').insertAfter($link);
    // Оригинальную ссылку удаляем из Dom (но сохраняем в переменной $link на случай ajax fail
    $link.remove();
    var fieldKey = $link.attr("data-fieldkey");
    var id = $link.attr("data-id");
    var oldValue = $link.attr("data-value");
    var newValue = oldValue === "1" ? "0" : "1";
    var url = $container.attr("data-ajaxchangefieldurl");
    url += id + "&fieldKey=" + encodeURIComponent(fieldKey) + "&newValue=" + newValue;
    //Отправим сам запрос!
    $.post(url)
        .done(function (newHtml) {
        // Замещаем изображение загрузки на новую ссылку, пришедшую от сервера
        $(newHtml).insertAfter($img);
        $img.remove();
    })
        .fail(function () {
        // В случае ошибки ajax запроса возвращаем старое значение
        $link.insertAfter($img);
        $img.remove();
        Light_1.toastError(textFail);
    });
});
$body.on("click", "a.ajax-input", function (e) {
    e.preventDefault();
    var $link = $(this);
    if ($link.hasClass("disabled")) {
        return;
    }
    // Найдём ближайший тег контейнер в котором описан url на который делать запрос
    var $container = $link.closest("*[data-ajaxchangefieldurl]");
    if ($container.length === 0) {
        throw new Error("Element container *[data-ajaxchangefieldurl] not found for ajax field " + this.toString());
    }
    // Блокируем ссылку от повторного нажатия
    $link.addClass("disabled");
    var fieldKey = $link.attr("data-fieldkey");
    var id = $link.attr("data-id");
    var url = $container.attr("data-ajaxchangefieldurl");
    url += id + "&fieldKey=" + encodeURIComponent(fieldKey);
    $.ajax(url)
        .done(function (inputHtml) {
        // Полученный html код поля для ввода вставляем после ссылки на редактирование
        var $input = $(inputHtml);
        $input.insertAfter($link);
        // Саму ссылку удаляем из DOM (при этом она сохраняется в переменной $link на случай cancel)
        $link.remove();
        // Если пришеднее input поле - это поле для ввода даты, то добавим datepicker виджет
        if ($input.hasClass("input-date") && $input.attr("type") === "text") {
            // Добавим специальный класс, чтобы поле выглядело поменьше и не дёргался экран в таблице
            $input.addClass("input-date-ajax");
            // Добавим к нему виджет выбора даты
            $input.datepicker({
                onClose: function () {
                    handleAjaxInputSave($input, url);
                }
            });
        }
        else {
            // Если же это не поле input-date, то добавим пару ссылок сохранить и отмена
            $("<a href=\"#\" style=\"padding-left: 15px\">" + textCancel + "</a>")
                .on("click", function (e) {
                e.preventDefault();
                handleAjaxInputCancel($input, $link);
            })
                .insertAfter($input);
            $("<a href=\"#\">" + textSave + "</a>")
                .on("click", function (e) {
                e.preventDefault();
                handleAjaxInputSave($input, url);
            })
                .insertAfter($input);
        }
        // Навесим обработчики кнопок на поле ввода
        $input.on("keydown", function (e) {
            if (e.keyCode === 13) {
                console.log($input.get(0).tagName);
                if ($input.get(0).tagName.toLowerCase() === "textarea" && !e.ctrlKey && !e.metaKey) {
                    // В случае textarea реагируем на enter только при зажатем control
                    return;
                }
                e.preventDefault();
                handleAjaxInputSave($input, url);
            }
            else if (e.keyCode === 27) {
                e.preventDefault();
                handleAjaxInputCancel($input, $link);
            }
        });
        $input.focus();
    })
        .fail(function (jqXHR) {
        // Ошибка получения HTML кода поля для редактирования
        // Возвращаем старую ссылку к живому виду
        $link.removeClass("disabled");
        // Выведем пользователю сообщение об ошибке пришедшее от сервера
        // (скорее всего там будет что-то вроде "нет доступа")
        Light_1.toastError(jqXHR.responseText);
    });
});
function handleAjaxInputSave($input, url) {
    if ($input.prop("disabled")) {
        // поле ввода уже заблокировано, значит предыдущий ajax запрос на сохранение ещё в процессе
        return;
    }
    // Блокируем поле для ввода на время путешетсвия ajax запроса
    $input.prop("disabled", true);
    // сохранение
    var postData = { newValue: $input.val() };
    $.post(url, postData)
        .done(function (newHtml) {
        // удаляем те две кнопки (сохранить  и отмена)
        removeSaveCancelButtons($input);
        // вставляем новый html код поля в режиме view после поля для ввода
        $(newHtml).insertAfter($input);
        // удаляем само поле для ввода
        $input.remove();
    })
        .fail(function () {
        // В случае ошибки ajax запроса возвращаем поле ввода в режим редактирования
        $input.prop("disabled", false);
        Light_1.toastError(textFail);
    });
}
function handleAjaxInputCancel($input, $originalLink) {
    // удаляем те две кнопки (сохранить  и отмена)
    removeSaveCancelButtons($input);
    // возвращаем на место старую ссылку
    $originalLink.insertAfter($input);
    $originalLink.removeClass("disabled");
    // удаляем само поле для ввода
    $input.remove();
}
function removeSaveCancelButtons($input) {
    // удаляем те две кнопки (сохранить  и отмена), если только это не было поле даты (у даты нет этих кнопок)
    if (!($input.hasClass("input-date") && $input.attr("type") === "text")) {
        $input.next("a").remove();
        $input.next("a").remove();
    }
}


/***/ }),
/* 10 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var Light_1 = __webpack_require__(0);
$(function () {
    //Навесим обработчики на пункты меню
    var $mainmenu = $("#mainmenu");
    //$("ul[data-status=closed]").hide();
    $mainmenu.on("click", ".mainmenu__sectionhead", function () {
        var $clikedHeader = $(this);
        var sectionText = $clikedHeader.attr("data-name");
        var $ul = $clikedHeader.next('ul');
        //Узнаем текущее состояние этой секции
        var newState = $ul.attr("data-status") === 'closed' ? 'open' : 'closed';
        $ul.attr("data-status", newState);
        if (newState === 'closed') {
            $ul.slideUp();
        }
        else {
            $ul.slideDown();
        }
        Light_1.setUserParam("Mainmenu/" + sectionText, newState);
    });
    var $body = $("body");
    var $layout = $("#global-layout");
    var $menu = $(".global-menu");
    var $menuBut = $(".hideshow-menu-button");
    var $pinBut = $(".pin-menu-button");
    var showMenu = function () {
        $menu.addClass("global-menu-show");
        $menuBut.addClass("display-none");
    };
    var hideMenu = function () {
        $menu.removeClass("global-menu-show");
        $menuBut.removeClass("display-none");
    };
    $menuBut.on("click", showMenu);
    $body.on("click", function (e) {
        if ($menu.hasClass("global-menu-show")
            && !$(e.target).closest('.hideshow-menu-button').length
            && !$(e.target).closest('.global-menu').length) {
            hideMenu();
        }
    });
    $pinBut.on("click", function () {
        if ($layout.hasClass("global-menu-unfix")) {
            $layout.removeClass("global-menu-unfix");
            Light_1.setUserParam("global-menu-unfix", "0");
        }
        else {
            $layout.addClass("global-menu-unfix");
            Light_1.setUserParam("global-menu-unfix", "1");
        }
    });
    var fileuploadInited = false;
    $("#mainmenu__avatarimg").on('click', function () {
        if (!fileuploadInited) {
            var script = document.createElement('script');
            script.src = 'lightpublic/js/fileupload.min.js';
            script.type = 'text/javascript';
            script.async = true;
            script.onload = function () {
                $("#mainmenu__avatarinputfile").fileupload({
                    url: 'index.php?a=Core&ps=UploadUserAvatar',
                    dataType: 'json',
                    sequentialUploads: true,
                    disableImageResize: /Android(?!.*Chrome)|Opera/
                        .test(window.navigator && navigator.userAgent),
                    imageMaxWidth: 800,
                    imageMaxHeight: 800,
                    imageCrop: true,
                    done: function (_, data) {
                        if (data.result.error) {
                            Light_1.toastError(data.result.text);
                        }
                        else {
                            $("#mainmenu__avatarimg").attr('src', data.result.imgUrl);
                        }
                    }
                });
                fileuploadInited = true;
                document.getElementById('mainmenu__avatarinputfile').click();
            };
            document.getElementsByTagName('head')[0].appendChild(script);
        }
        else {
            document.getElementById('mainmenu__avatarinputfile').click();
        }
    });
});


/***/ }),
/* 11 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var Light_1 = __webpack_require__(0);
var ru = {
    All: "Весь",
    Jan: "Янв",
    Feb: "Фев",
    Mar: "Мар",
    Apr: "Апр",
    May: "Май",
    Jun: "Июн",
    Jul: "Июл",
    Aug: "Авг",
    Sep: "Сен",
    Oct: "Окт",
    Nov: "Ноя",
    Dec: "Дек"
};
function _t(text) {
    if (Light_1.isLangRu && ru[text]) {
        return ru[text];
    }
    return text;
}
// Вспомагательная переменная - ссылка на текущую кнопку рядом с которой открыта палитра
var $currentInput;
var $widget;
var $yearSelect;
var $allyearTr;
function parseInputText(inputText) {
    if (inputText.length === 4) {
        // В поле ввода указан только год - возвращаем только его
        return {
            year: Light_1.myParseInt(inputText),
            month: 0
        };
    }
    else if (inputText.indexOf("/") !== -1) {
        var _a = inputText.split("/"), month = _a[0], year = _a[1];
        return {
            year: Light_1.myParseInt(year),
            month: Light_1.myParseInt(month)
        };
    }
    return null;
}
function parseAndhighlightCurrentValue() {
    if ($currentInput) {
        // Распарсим текущее текстовое значение в поле для ввода и сделаем соответсвующую подсветку в виджете
        var result = parseInputText($currentInput.val());
        var year = void 0, month = void 0;
        if (result === null) {
            // В поле ввода не была указана какая либо дата
            // Устанавилваем год в текущий год
            year = new Date().getFullYear();
            // А месяц вообще никак не будем определять
            month = null;
        }
        else {
            year = result.year;
            month = result.month;
        }
        setYear(year > 2000 ? year : new Date().getFullYear());
        // Нужно ли отобразить в специальной строке allyear выбранный год?
        $allyearTr.find("td").text(_t("All") + " " + year);
        // Разберёмся с месяцем. Для начала снимем старую подсветку, если такая была
        $widget.find(".selected").removeClass("selected");
        if (month !== null) {
            // Добавим подстветку выбранного месяца к конкретной ячейке месяца
            // При этом если номер месяца будет 0 - то подсветится нулевая ячейка - это опция "весь год"
            $widget.find("td:eq(" + month + ")").addClass("selected");
        }
    }
}
function setYear(year) {
    // Проверим, есть ли среди option значение года, которое указано в тексте? Если нет, динамически добавляем в выпадающий список
    if ($yearSelect.find("option[value=\"" + year + "\"]").length === 0) {
        // Такого значения нет - добавляем динамически
        $yearSelect.append("<option value=\"" + year + "\">" + year + "</option>");
    }
    // Теперь делаем нужный год выбранным в выпадающем писке
    $yearSelect.val(year);
    $allyearTr.find("td").text(_t("All") + " " + year);
}
function show($targetInput) {
    // Запоминаем элемент ввода месяца на который пользователь только что передал фокус
    $currentInput = $targetInput;
    // Если виджет ещё ни разу не отображался на экране, создаём динамически
    if (!$widget) {
        var html = "<div class=\"floatingwidget\">\n                <div style=\"display: flex;align-items: center;\">\n                    <a href=\"#\" style=\"flex: 0; margin-left: 5px;\" id=\"monthwidget__prevlink\"><</a>\n                    <div style=\"flex: 1\"><select id=\"monthwidget__yearselect\"></select></div>\n                    <a href=\"#\" style=\"flex: 0; margin-right: 5px;\" id=\"monthwidget__nextlink\">></a>\n                </div>\n                <table id=\"monthwidget__monthstable\">\n                <tr id=\"monthwidget__allyeartr\"><td colspan=\"3\"></td></tr>\n                <tr><td>" + _t("Jan") + "</td><td>" + _t("Feb") + "</td><td>" + _t("Mar") + "</td></tr>\n                <tr><td>" + _t("Apr") + "</td><td>" + _t("May") + "</td><td>" + _t("Jun") + "</td></tr>\n                <tr><td>" + _t("Jul") + "</td><td>" + _t("Aug") + "</td><td>" + _t("Sep") + "</td></tr>\n                <tr><td>" + _t("Oct") + "</td><td>" + _t("Nov") + "</td><td>" + _t("Dec") + "</td></tr>\n                </table>\n                </div>";
        $widget = $(html);
        // Вставляем в вёрстку
        $widget.appendTo("body");
        $yearSelect = $("#monthwidget__yearselect");
        $allyearTr = $("#monthwidget__allyeartr");
        // При смене года нужно также обновлять строку "весь год"
        $yearSelect.on("change", function () {
            setYear($yearSelect.val());
        });
        $("#monthwidget__prevlink").on("click", function (e) {
            e.preventDefault();
            setYear(Light_1.myParseInt($yearSelect.val()) - 1);
        });
        $("#monthwidget__nextlink").on("click", function (e) {
            e.preventDefault();
            setYear(Light_1.myParseInt($yearSelect.val()) + 1);
        });
    }
    else {
        // Виджет уже был создан в вёрстке при прошлом использовании
        $widget.removeClass("display-none");
    }
    // Перемещаем палитру непосретсвенно рядом с полем ввода
    var offset = $targetInput.offset();
    offset.top += 24;
    $widget.offset(offset);
    // Уберём или вернём строку с возможностью выбрать весь год
    var allYear = $targetInput.attr("data-allyear") === "1";
    $allyearTr.toggleClass("display-none", !allYear);
    // Установим значение года в выпадающем списке в соответсвие с параметрами поля ввода
    $yearSelect.empty();
    var yearFrom = Light_1.myParseInt($targetInput.attr("data-yearfrom"));
    var yearUntil = Light_1.myParseInt($targetInput.attr("data-yearuntil"));
    for (var tmpYear = yearFrom; tmpYear <= yearUntil; tmpYear++) {
        $yearSelect.append("<option value=\"" + tmpYear + "\">" + tmpYear + "</option>");
    }
    // Установим правильное значение в выпадающем списке и подсветим месяц в зависимости от текста указанного в поле ввода
    parseAndhighlightCurrentValue();
}
function hide() {
    $currentInput = null;
    $widget.addClass("display-none");
}
// В момент on dom ready инициализируем основные обработчики клика на body
$(function () {
    $("body")
        .on("focus", "input[data-type=Month]", function (e) {
        // Пользователь передал фокус на какое-то поле ввода месяца - нужно отобразить виджет
        show($(e.target));
    })
        .on("keyup", "input[data-type=Month]", function (e) {
        if ($currentInput && $currentInput.get(0) === e.target) {
            // Если виджет уже отображается и элемент на котором происходит keyup соответсвует текущему элементу виджета
            // то нужно заново распарсить значение месяца и обновить подсветку внутри виджета
            parseAndhighlightCurrentValue();
        }
    })
        .on("click", function (e) {
        // Пользователь куда-то кликнул - нужно либо подтвердить выбор месяца, либо скрыть виджет, если клик куда-то во вне
        if ($currentInput) {
            var target = e.target;
            if (!$.contains($widget.get(0), target)) {
                // Если клик был сделан куда-то вне уже открытой палитры, то её нужно закрыть
                if (target.getAttribute("data-type") !== "Month") {
                    hide();
                }
            }
            else {
                if (e.target.tagName.toLowerCase() === "td") {
                    // Клик был совершен где-то внутри виджета и попал на одну из td ячеек - применим выбранный месяц
                    var month = $widget.find("td").index(target);
                    var year = $yearSelect.val();
                    var resultText = month !== 0 ? (month < 10 ? "0" : "") + month.toString() + "/" + year : year;
                    $currentInput.val(resultText);
                    $currentInput.trigger("change");
                    // Сразу после этого закроем виджет
                    hide();
                }
            }
        }
    })
        .on("keydown", function (e) {
        if ($currentInput) {
            if (e.which === 27) {
                hide();
            }
            else if (e.which === 13) {
                // При тупом нажатии на enter устанавливаем текущую дату, если текущее значение пусто
                if ($currentInput.val() === "") {
                    var month = new Date().getMonth().toString();
                    if (month.length < 2) {
                        month = "0" + month;
                    }
                    $currentInput.val(month + "/" + new Date().getFullYear());
                }
                hide();
            }
        }
    });
});


/***/ }),
/* 12 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var Light_1 = __webpack_require__(0);
// Объект с курссами валют по отношению к RUB
// {EUR: {RateToRUB: 75.1234, ValidDate: '2016-04-12'}, USD: {RateToRUB: 65.7890, ValidDate: '2016-04-12'}}
var rates = Light_1.getPageParam("CurrencyRates") || {};
function getRate(from, to) {
    if (to === void 0) { to = "USD"; }
    if (from === to) {
        return 1;
    }
    //Если хотя бы одна из валют рубли, то используем и RateToRUB
    if (from === "RUB") {
        //Пользователь хочет перевести рубли в какую-то другую валюту
        //Например, хочет перевести рубли в доллары. В этом случае в toCurrencyIso.RateToRUB будет что-то типа 35.1234
        //А отношение RUB / USD будет соответсвенно 1 / 35.1234
        return 1 / (rates[to] ? rates[to].RateToRUB : 1);
    }
    if (to === "RUB") {
        //Пользователь хочет перевести какую-то валюту в рубли
        //Например, хочет перевести доллары в рубли. В этом случае в fromCurrencyIso.RateToRUB будет что-то типа 35.1234
        //Его и возвращаем
        return rates[from] ? rates[from]["RateToRUB"] : 1;
    }
    // Если перевод никак не связан с рублями
    // Исходную валюту переводим в рубли, затем результат переводим в валюту назначения. Например, мы хотим перевести
    // фунты в евро. 1 фунт это примерно 1.5 евро - т.е. результат getRate('GBP', 'EUR') === 1.5
    // Сначала делаем getRate('GBP', 'RUB') === 100
    // Затем делаем getRate('RUB', 'EUR') === 1/70
    // Перемножаем 100 * (1/70) ~= 1.5!
    return getRate(from, "RUB") * getRate("RUB", to);
}
exports.getRate = getRate;
function convertValue(value, from, to) {
    if (to === void 0) { to = "USD"; }
    return Light_1.myParseFloat(value) * getRate(from, to);
}
exports.convertValue = convertValue;


/***/ }),
/* 13 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var Light_1 = __webpack_require__(0);
var forms = __webpack_require__(1);
var Filters = /** @class */ (function () {
    function Filters(table) {
        var _this = this;
        this.resetHandlers = [];
        this.onSubmit = function (e) {
            e.preventDefault();
            _this.submit();
        };
        this.onTextareaKeydown = function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                _this.submit();
            }
        };
        this.onResetClick = function () {
            _this.reset();
            _this.resetHandlers.forEach(function (handler) { return handler(); });
        };
        this.onWidgetLikeResetClick = function (e) {
            e.preventDefault();
            var currentValues = _this.getValues();
            // Устанавливаем новое default значение для текущей формы
            currentValues.forEach(function (currentValue, fieldKey) {
                _this.form.setFieldDefaultValue(fieldKey, currentValue);
            });
            // Отправляем запрос на сервер
            $.post("?a=Core&ps=FiltersSaveLikeReset&TableName=" + encodeURIComponent(_this.name), {
                Defaults: Light_1.buildObjFromMap(currentValues)
            });
            _this.hideWidget();
        };
        this.onWidgetSelectFiltersClick = function (e) {
            e.preventDefault();
            var $a = $(e.currentTarget);
            // Сначала сбрасываем все значения в значения по умолчанию
            _this.form.getFields().forEach(function (field, fieldKey) {
                _this.form.setFieldValue(fieldKey, field.def || "");
            });
            // Затем выставляем в соответсвие с сохранёнными
            var savedValues = Light_1.buildMapFromObj(Light_1.parseJson($a.attr("data-filtersjson"), {}));
            savedValues.forEach(function (value, fieldKey) {
                _this.form.setFieldValue(fieldKey, value);
            });
            _this.resetHandlers.forEach(function (handler) { return handler(); });
            _this.submit();
            _this.hideWidget();
        };
        this.onWidgetSaveCurrentClick = function (e) {
            e.preventDefault();
            var buttonEl = e.currentTarget;
            var newName = $(buttonEl)
                .closest(".save-filter")
                .find("input")
                .val();
            newName = newName.trim();
            if (newName == "") {
                Light_1.toastError("Введите название для фильтра");
                return;
            }
            buttonEl.disabled = true;
            $.post("?a=Core&ps=FiltersSave&TableName=" + encodeURIComponent(_this.name), {
                Name: newName,
                Filters: Light_1.buildObjFromMap(_this.getValues())
            }).done(function (savedHtml) {
                _this.renderHtmlIntoWidget(savedHtml);
                _this.$widget.find("input.input-default").val(newName);
            });
        };
        this.onWidgetDeleteSavedClick = function (e) {
            e.preventDefault();
            var toBeDeletedName = $(e.currentTarget)
                .prev("a")
                .text();
            $.post("?a=Core&ps=FiltersDelete&TableName=" + encodeURIComponent(_this.name), {
                Name: toBeDeletedName
            }).done(function (savedHtml) {
                // Если успешно удалили то от сервера приходит обновлённый список всех сохранённых настроек
                _this.renderHtmlIntoWidget(savedHtml);
            });
        };
        this.onHistoryClick = function (e) {
            e.preventDefault();
            _this.showWidget(e.currentTarget);
            $.ajax("?a=Core&ps=FiltersGetHistoryHtml&TableName=" + encodeURIComponent(_this.name)).done(function (responseHtml) {
                _this.renderHtmlIntoWidget(responseHtml);
            });
        };
        this.onSaveClick = function (e) {
            e.preventDefault();
            _this.showWidget(e.currentTarget);
            $.ajax("?a=Core&ps=FiltersGetSavedHtml&TableName=" + encodeURIComponent(_this.name)).done(function (savedHtml) {
                _this.renderHtmlIntoWidget(savedHtml);
            });
        };
        this.onShowLayoutClick = function (e) {
            e.preventDefault();
            _this.showLayout();
            var $form = _this.$form;
            // Запоминаем текущую сортировку и видимость для возврата при случае "Отмены"
            _this.layoutForCancel = _this.getLayoutFromMarkup();
            _this.invisibleForCancel = _this.getInvisibleFromMakrup();
            // Расставляем "глазки" в каждый th
            _this.showEyeIcons();
            // Отображаем все скрытые фильтры
            $form.find(".filter-invisible").removeClass("filter-invisible");
            $form.find("table.filters").sortable({
                connectWith: "table.filters",
                items: "> tbody > tr",
                cursor: "move",
                handle: "th:not(:empty)"
            });
        };
        this.onLayoutCancelClick = function (e) {
            e.preventDefault();
            var $form = _this.$form;
            // Отключаем виджет сортировки
            $form.find("table.filters").sortable("destroy");
            // Убираем все иконки-глазки
            $form.find(".filter-hide-icon").remove();
            // Возвращаем положение всех строк в начальное (которое было предварительно сохранено в this.layoutForCancel)
            _this.applyLayout(_this.layoutForCancel);
            // Возвращаем классы filter-invisible на те строки, которые были сохранены в this.invisibleForCancel
            _this.addInvisibleClasses(_this.invisibleForCancel);
            // Убираем панель с кнопками для режима редактирования settings
            _this.hideLayout();
        };
        this.onLayoutSaveClick = function (e) {
            e.preventDefault();
            var $form = _this.$form;
            var newLayout = _this.getLayoutFromMarkup();
            var newInvisible = new Set();
            // Соберём набор полей которые пользователь отметил иконкой "скрыть" (перечёркнутый глаз)
            $form.find(".fa-eye-slash").each(function () {
                var $tr = $(this).closest('tr[data-templatekey]');
                if ($tr.length === 1) {
                    newInvisible.add($tr.attr('data-templatekey'));
                }
            });
            // Отправляем новые настройки на сервер
            $.post("?a=Core&ps=FiltersSaveLayout&TableName=" + encodeURIComponent(_this.name), {
                Layout: newLayout,
                Invisible: Array.from(newInvisible)
            });
            // Отключаем jQuery плагин sortable
            $form.find("table.filters").sortable("destroy");
            // Физически скрываем все отмеченные перечёкнутым глазом фильтроы
            _this.addInvisibleClasses(newInvisible);
            // Убираем все иконки с глазами
            $form.find(".filter-hide-icon").remove();
            // Скрываем форму настроек
            _this.hideLayout();
        };
        this.onLayoutResetClick = function (e) {
            e.preventDefault();
            // Когда пользователь нажал кнопку "сбросить layout по умолчанию", на лету парсим атрибут формы data-defaultlayout
            var defaultLayout = Light_1.parseJson(_this.$form.attr("data-defaultlayout"), []);
            // И применяем layout занный в атрибует формы как layout по умолчанию
            _this.applyLayout(defaultLayout);
        };
        this.onHideIconClick = function (e) {
            e.preventDefault();
            var $icon = $(e.currentTarget);
            var isCurrentInvisible = $icon.hasClass("fa-eye-slash");
            var newInvisible = !isCurrentInvisible;
            $icon
                .toggleClass("fa-eye-slash", newInvisible)
                .toggleClass("fa-eye", !newInvisible);
        };
        this.onAllVisibleClick = function (e) {
            e.preventDefault();
            // Убираем все перечёркнутые глазки, добавляем обычные глазки
            _this.$form.find(".filter-hide-icon").removeClass("fa-eye-slash").addClass("fa-eye");
        };
        this.onAllInvisibleClick = function (e) {
            e.preventDefault();
            // Убираем все обычные глазки, добавляем перечёркнутые глазки
            _this.$form.find(".filter-hide-icon").removeClass("fa-eye").addClass("fa-eye-slash");
        };
        this.onBodyClickTryHideWidget = function (e) {
            if (_this.$widget
                && !$.contains(_this.$widget.get(0), e.target)) {
                _this.hideWidget();
            }
        };
        this.name = table.getName();
        this.table = table;
        this.form = forms.get(this.name);
        this.$form = this.form.getFormNode();
        var $form = this.$form;
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
            $(document).keydown(function (e) {
                if (_this.$layer && e.keyCode === 27 && _this.$layer.is(":visible")) {
                    $form.find(".filters-layout-cancel").trigger("click");
                }
            });
        }
    }
    Filters.prototype.getValue = function (fieldKey) {
        return this.form.getFieldValue(fieldKey) || "";
    };
    Filters.prototype.getValues = function () {
        return this.form.getFieldsValues();
    };
    Filters.prototype.setValue = function (fieldKey, newValue) {
        this.form.setFieldValue(fieldKey, newValue);
    };
    Filters.prototype.renderHtmlIntoWidget = function (html) {
        if (this.$widget) {
            this.$widget.html(html);
        }
    };
    Filters.prototype.highlightActive = function () {
        var _this = this;
        //Сначала уберём подсветку со всех полей
        this.$form.find(".filter-active").removeClass("filter-active");
        this.form.getFields().forEach(function (_, fieldKey) {
            if (!_this.isDefaultValue(fieldKey)) {
                var $field = _this.form.getFieldNode(fieldKey);
                var $label = $field.prev("label");
                if ($label.length > 0) {
                    $label.addClass("filter-active");
                }
                else {
                    var $td = $field.closest("td");
                    if ($td.length > 0) {
                        var $th = $td.prev("th");
                        if ($th.length > 0) {
                            //Значение отличается от значения по умолчанию - подсвечиваем поле
                            $th.addClass("filter-active");
                        }
                    }
                }
            }
        });
    };
    Filters.prototype.reset = function () {
        var _this = this;
        this.form.getFields().forEach(function (field, fieldKey) {
            if (!field.donotreset) {
                _this.form.setFieldValue(fieldKey, field.def || "");
            }
        });
    };
    Filters.prototype.submit = function () {
        var _this = this;
        var $form = this.$form;
        $form.find(".btn-filter").prop("disabled", true);
        $form.addClass("loading-opacity");
        var reloadParams = {
            filterValues: JSON.stringify(Light_1.buildObjFromMap(this.form.getFieldsValues())),
            page: 1
        };
        this.table.reload(reloadParams, function () {
            _this.$form.removeClass("loading-opacity");
            $form.find(".btn-filter").prop("disabled", false);
            var hiddenInput = _this.table.getDivNode().find("input[name=filterValues]");
            if (hiddenInput.length > 0) {
                var newValues = Light_1.parseJson(hiddenInput.val());
                for (var fieldKey in newValues) {
                    _this.setValue(fieldKey, newValues[fieldKey]);
                }
                //Подсветим активные фильтры жирным и семеним иконку у кнопки Filter, если нужно
                _this.highlightActive();
                // Запустим механизм скрытия скрытых фильтров заново
                // это нужно чтобы отобразить те фильтры, у которых значение отличается от значения по умолчанию
                _this.refreshInvisibleClasses();
            }
        });
    };
    Filters.prototype.showWidget = function (clickTarget) {
        var _this = this;
        var $widget = $("<div class='widget-block floatingwidget'>\n                                    <div class=\"cssload-container\">\n                                        <div class=\"cssload-speeding-wheel\"></div>\n                                    </div>\n                                 </div>");
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
        var offset = $(clickTarget).offset();
        offset.top += 40;
        $widget.offset(offset);
        // Сохраняем в своействе класса, чтобы потом можно было сделать hide (удалив элемет из dom)
        this.$widget = $widget;
        setTimeout(function () {
            $("body").on("click", _this.onBodyClickTryHideWidget);
        }, 0);
        return $widget;
    };
    Filters.prototype.hideWidget = function () {
        if (this.$widget) {
            this.$widget.remove();
            this.$widget = undefined;
            $("body").off("click", this.onBodyClickTryHideWidget);
        }
    };
    /**
     * Собирает текущий layout на основе вёрстки
     * Применяется в начальный момент времени, когда пользователь только нажал кнопку настроек, чтобы сохранить в this.layoutForCancel
     * и при нажатии кнопки "применить настройки", когда нужно отправить на сервер все настройки сделанные пользователем
     */
    Filters.prototype.getLayoutFromMarkup = function () {
        var layoutFromMarkup = [];
        this.$form.find("table.filters").each(function (tableIndex) {
            var $table = $(this);
            layoutFromMarkup[tableIndex] = [];
            $table.find("tr[data-templatekey]").each(function () {
                var $tr = $(this);
                var templateKey = $tr.attr('data-templatekey');
                layoutFromMarkup[tableIndex].push(templateKey);
            });
        });
        return layoutFromMarkup;
    };
    /**
     * Возвращает Set из ключей templatekey у котоырх в вёрстке стоит класс filter-invisible
     * @returns {string[]}
     */
    Filters.prototype.getInvisibleFromMakrup = function () {
        var invisibleFromMarkup = new Set();
        this.$form.find("tr[data-templatekey].filter-invisible").each(function () {
            invisibleFromMarkup.add($(this).attr('data-templatekey'));
        });
        return invisibleFromMarkup;
    };
    /**
     * Отображает иконки с глазками
     * Вызывается при начале работы в режиме настроек
     */
    Filters.prototype.showEyeIcons = function () {
        this.$form.find("tr[data-templatekey]").each(function () {
            var $tr = $(this);
            var invisible = $tr.hasClass('filter-invisible');
            var iconClass = invisible ? "fa-eye-slash" : "fa-eye";
            var icon = "<i class=\"filter-hide-icon fa " + iconClass + "\"></i>";
            $tr.find('th').prepend(icon);
        });
    };
    /**
     * Перемещает элементы в вёрстке в соответсве с переданным layout
     * Вызывается в двух случаях: когда пользователь что-то поменял в расположении фильтров и затем нажал Cancel
     * либо когда пользователь нажал "сбросить положение фильтров по умолчанию"
     * @param {string[][]} layout
     */
    Filters.prototype.applyLayout = function (layout) {
        var $form = this.$form;
        // Перебирем колонки, которые уже есть в вёрстке (table.filters)
        $form.find("table.filters").each(function (tableIndex) {
            var $table = $(this);
            // Внутри каждой колонки применяем полученный порядок полей пришедший в параметре layout
            if (layout[tableIndex] !== undefined) {
                layout[tableIndex].forEach(function (templateKey) {
                    // Вынимаем элемент вёрски где-бы он ни находился и просто доабвляем в конец текущей колонки
                    $table.append($form.find('table.filters tr[data-templatekey="' + templateKey + '"]'));
                });
            }
        });
        // Кнопки фильтров расположим в первой колонке в самом конце
        $form.find("table.filters").first().append($form.find('.filters-buttons').closest('tr'));
    };
    /**
     * Добавляет класс filter-invisible ко всем строкам которые скрыты и в которых значение пусто
     * @param {Set<string>} invisibleTemplateKeys
     */
    Filters.prototype.addInvisibleClasses = function (invisibleTemplateKeys) {
        var _this = this;
        if (this.noSettings) {
            // Если для данного модуля в целом отключена система настроек layout,
            // то и скрывать ничего не будем
            return;
        }
        var $form = this.$form;
        // Сначала удаляем все старые наложенные классы на всякий случай
        $form.remove(".filter-invisible");
        // Теперь накладываем классы заново
        invisibleTemplateKeys.forEach(function (templateKey) {
            if (_this.isDefaultValue(templateKey)) {
                // Скрываем только если поле имеет значение по-умолчанию
                // При проверке на default может быть не соответствие: templateKey не всегда совпадает с реальным fieldKey
                // В случае если среди фильтров отсутствует поле с ключём templateKey, метод isDefaultValue вернёт true
                // и значит мы скроем такую строку не проверив на самом деле что внутри
                $form.find("tr[data-templatekey=\"" + templateKey + "\"]").addClass('filter-invisible');
            }
        });
    };
    Filters.prototype.refreshInvisibleClasses = function () {
        if (!this.noSettings) {
            this.addInvisibleClasses(this.getInvisibleFromMakrup());
        }
    };
    Filters.prototype.showLayout = function () {
        window.scrollTo(0, 0);
        var $form = this.$form;
        $form.closest(".filters").css("z-index", 10);
        if (!this.$layer) {
            var $layer = $("body > .layer");
            if ($layer.length) {
                this.$layer = $layer;
            }
            else {
                this.$layer = $("<div class=\"layer\"></div>");
                this.$layer.appendTo("body");
            }
        }
        this.$layer.fadeIn("fast");
        $("body").css("overflow", "hidden");
        var $buttons = $form.find(".filters-buttons").addClass("display-none");
        var textResetSort = Light_1.isLangRu ? "Сбросить сортировку фильтров" : "Reset to default layout";
        var textShowAll = Light_1.isLangRu ? "Сделать все фильтры видимыми" : "Show all";
        var textHideAll = Light_1.isLangRu ? "Сделать все фильтры невидимыми" : "Hide all";
        var textCancel = Light_1.isLangRu ? "Отмена" : "Cancel";
        var $layoutButtons = $("<div class=\"filters-layout-buttons\">\n                                             <span class=\"btn waves-effect waves-light bgcolor-btn-green filters-layout-save\"><i class=\"fa fa-save\" aria-hidden=\"true\"></i></span>       \n                                             <span class=\"btn-flat waves-effect filters-layout-reset toggle-tooltip\" title=\"" + textResetSort + "\"><i class=\"fa fa-random\" aria-hidden=\"true\"></i></span>\n                                             <span class=\"btn-flat waves-effect filters-layout-allvisible toggle-tooltip\" title=\"" + textShowAll + "\"><i class=\"fa fa-eye\" aria-hidden=\"true\"></i></span>\n                                             <span class=\"btn-flat waves-effect filters-layout-allinvisible toggle-tooltip\" title=\"" + textHideAll + "\"><i class=\"fa fa-eye-slash\" aria-hidden=\"true\"></i></span>\n                                             <span class=\"btn-flat waves-effect filters-layout-cancel toggle-tooltip\" title=\"" + textCancel + "\"><i class=\"fa fa-ban\" aria-hidden=\"true\"></i></span>\n                                        </div>");
        Light_1.initTooltip($layoutButtons);
        $buttons.after($layoutButtons);
    };
    Filters.prototype.hideLayout = function () {
        var $form = this.$form;
        $form.closest(".filters").css("z-index", 0);
        this.$layer.fadeOut("fast");
        $("body").css("overflow", "auto");
        $form.find(".filters-buttons").removeClass("display-none");
        $form.find(".filters-layout-buttons").remove();
    };
    Filters.prototype.isDefaultValue = function (fieldKey) {
        var field = this.form.getField(fieldKey);
        if (!field) {
            // Такого поля не существует, вернём true как будто у него стоит значение по умолчанию
            return true;
        }
        if (field.def === null || field.def === undefined) {
            // У этого поля не определено значение по умолчанию, вернём true, как будто оно и стоит
            return true;
        }
        var value = this.form.getFieldValue(fieldKey);
        if (value === null || value === undefined) {
            // Текущее значение не удалось определить, вернём true
            return true;
        }
        // Если текущее значение определено и занчение по умолчанию опеределено, то сравним их!
        return value.toString() === field.def.toString();
    };
    return Filters;
}());
exports.Filters = Filters;


/***/ }),
/* 14 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var Dialog_1 = __webpack_require__(3);
var table = __webpack_require__(2);
var Light_1 = __webpack_require__(0);
var dialog, $shownUl, $hiddenUl, $layoutsDefault, $layoutsUser, $newLayoutInput, $filter, $btnSaveLayout;
var currentState = {
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
    $btnSaveLayout = $("#tablelayoutdialog__btnsave").on("click", function (e) {
        e.preventDefault();
        var layoutName = $newLayoutInput.val();
        if (layoutName == "") {
            //нельзя сохранять настройку с пустым именем
            return;
        }
        ajaxCore({
            layoutName: layoutName,
            fieldsShow: getShownFieldKeys().join("|")
        }).done(function () {
            var successText = Light_1.isLangRu ? 'Настройки сохранены' : 'Layout saved';
            Light_1.toastSuccess(successText + ": <strong>" + layoutName + "</strong>");
        });
    });
    $newLayoutInput = $("#tablelayoutdialog__newlayout"); //.on('key', userLayoutSaveByEnter, 'enter');
    $("#tablelayoutdialog__linkall").on("click", function (e) {
        e.preventDefault();
        updateSortables(currentState.allFields.map(function (f) { return f.key; }));
    });
    $("#tablelayoutdialog__linknone").on("click", function (e) {
        e.preventDefault();
        updateSortables([]);
    });
    $layoutsDefault = $("#tablelayoutdialog__layoutsdefault").on("click", "a", function (e) {
        e.preventDefault();
        var layoutIndex = Light_1.myParseInt(e.currentTarget.hash.substring(1));
        var fieldsShow = currentState.layouts[layoutIndex].fieldKeys;
        updateSortables(fieldsShow);
    });
    $layoutsUser = $("#tablelayoutdialog__layoutsuser")
        .on("click", "a", function (e) {
        e.preventDefault();
        var a = e.currentTarget;
        $newLayoutInput.val(a.innerText);
        updateSortables(a.hash.substring(1).split("|"));
    })
        .on("click", "i", function (e) {
        ajaxCore({ delid: e.currentTarget.getAttribute("data-delid") });
    });
    $shownUl = $("#tablelayoutdialog__showul").sortable({ connectWith: "#tablelayoutdialog__hideul" });
    $hiddenUl = $("#tablelayoutdialog__hideul").sortable({ connectWith: "#tablelayoutdialog__showul" });
    $filter = $("#tablelayoutdialog__filter").on("keyup change", function (e) {
        var filterValLower = e.currentTarget.value.toLowerCase();
        if (filterValLower === "") {
            $hiddenUl.find("li.display-none").removeClass("display-none");
        }
        else {
            $hiddenUl.find("li").each(function () {
                var $li = $(this);
                $li.toggleClass("display-none", $li
                    .text()
                    .toLowerCase()
                    .indexOf(filterValLower) === -1 &&
                    ($li.attr("title") || "").toLowerCase().indexOf(filterValLower) === -1);
            });
        }
    });
    var $content = $("#tablelayoutdialog");
    var options = {
        width: "770px",
        buttonOkText: Light_1.isLangRu ? 'Применить' : 'Apply new Layout',
        buttonCancelText: Light_1.isLangRu ? 'Отмена' : 'Cancel',
        buttonOkHandler: function () {
            dialog.close();
            currentState.dialogSubmitCallback(getShownFieldKeys());
        }
    };
    dialog = new Dialog_1.Dialog($content, options, false);
    $content.removeClass("display-none");
}
function updateSortables(showFieldKeys) {
    //Очистим оба списка в DOM
    $filter.val("");
    $shownUl.empty();
    $hiddenUl.empty();
    // Перебираем все поля и добавляем их либо в блок отображаемых, либо в блок скрытых
    // Подготовим накопительный массив полей, которые потом будут отрендрены в блок отображаемых в нужном порядке
    var toShow = [];
    currentState.allFields.forEach(function (field) {
        // Определим позицию в блоке Shown в которую вставить текущее поле
        var showIndex = showFieldKeys.indexOf(field.key);
        if (showIndex === -1) {
            // Этого поля нет в массиве отображаемых, сразу вставляем его в список скрытых полей
            appendLi($hiddenUl, field.key, field.name, field.title);
        }
        else {
            // Определили позицию под которой нужно вставить в блок отображаемых полей. Но сразу вставлять в DOM не будем, накопим
            // в подготовительном массиве
            toShow[showIndex] = field;
        }
    });
    // Отрендрим накопленный массив отображаемых полей
    toShow.forEach(function (field) { return appendLi($shownUl, field.key, field.name, field.title); });
    // После изменения DOM дерева внутри списков Sortable, нужно вызвать метод sync()
    $shownUl.sortable("refresh");
    $hiddenUl.sortable("refresh");
}
var appendLi = function ($ul, fieldKey, fieldName, fieldTitle) {
    //С помощью css класса подсветим поле фиолетовым или голубым
    var cssClass = currentState.initialShownFieldKeys.indexOf(fieldKey) === -1
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
function getShownFieldKeys() {
    var shownFieldKeys = [];
    $shownUl.find("li").each(function () {
        shownFieldKeys.push(this.getAttribute("data-fieldkey"));
    });
    return shownFieldKeys;
}
function ajaxCore(postdata) {
    var url = "?a=Core&ps=table&table=" + currentState.tableName;
    $btnSaveLayout.prop("disabled", true);
    return $.ajax(url, {
        cache: false,
        data: postdata,
        type: "post"
    })
        .done(function (userLayoutsHtml) {
        // В ответ всегда приходит html код для вставки в виде списка пользовательских layouts
        $layoutsUser.html(userLayoutsHtml);
    })
        .always(function () {
        $btnSaveLayout.prop("disabled", false);
    });
}
/**
     * Этот метод специально сделан экспортируемым, чтобы можно было отображать диалоговое окно выбора полей
     * не только по стандартной ссылке "Table layout settings", но и в SPA приложении
     */
function showLayoutDialog(tableName, allFields, initialShownFieldKeys, layouts, userLayoutsHtml, dialogSubmitCallback) {
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
    currentState.layouts.forEach(function (layout, index) {
        $layoutsDefault.append("<li><a href=\"#" + index + "\">" + layout.name + "</a></li>");
    });
    $layoutsUser.html(userLayoutsHtml);
    $newLayoutInput.val("");
    //Отобразим диалог
    dialog.open();
}
exports.showLayoutDialog = showLayoutDialog;
// На все кнопки Table Layout Settings вешаем обрабочик клика
$("body").on("click", "a.table-layoutsettings", function (e) {
    e.preventDefault();
    var tableName = $(e.currentTarget)
        .closest("div.table-div")
        .attr("data-tablename");
    var tableObj = table.get(tableName);
    $.ajax(tableObj.reloadUrl + "&loadTableLayoutSettings=1").done(function (settingsFromServer) {
        var allFields = settingsFromServer.allFields, shownFieldKeys = settingsFromServer.shownFieldKeys, layouts = settingsFromServer.layouts, userLayoutsHtml = settingsFromServer.userLayoutsHtml;
        showLayoutDialog(tableName, allFields, shownFieldKeys, layouts, userLayoutsHtml, function (shownFieldKeys) {
            tableObj.reload({ fieldsShow: shownFieldKeys.join("|") });
        });
    });
});


/***/ })
/******/ ]);
//# sourceMappingURL=lightbuild.js.map