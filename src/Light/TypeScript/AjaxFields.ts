import { isLangRu, toastError } from "./Light";

const $body: JQuery = $("body");

const textFail = isLangRu ? "Не удалось изменить" : "Update failed";
const textSave = isLangRu ? "Сохранить" : "Save";
const textCancel = isLangRu ? "Отмена" : "Cancel";

$body.on("click", "a.ajax-checkbox", function(this: HTMLAnchorElement, e: JQuery.Event) {
    e.preventDefault();
    const $link = $(this);

    // Найдём ближайший тег контейнер в котором описан url на который делать запрос
    const $container = $link.closest("*[data-ajaxchangefieldurl]");
    if ($container.length === 0) {
        throw new Error("Element container *[data-ajaxchangefieldurl] not found for ajax field " + this.toString());
    }

    // Ставим на время load-картинку
    const $img = $('<img src="lightpublic/i/circle.gif">').insertAfter($link);
    // Оригинальную ссылку удаляем из Dom (но сохраняем в переменной $link на случай ajax fail
    $link.remove();

    const fieldKey = $link.attr("data-fieldkey")!;
    const id = $link.attr("data-id");
    const oldValue = $link.attr("data-value");
    const newValue = oldValue === "1" ? "0" : "1";
    let url = $container.attr("data-ajaxchangefieldurl");
    url += `${id}&fieldKey=${encodeURIComponent(fieldKey)}&newValue=${newValue}`;

    //Отправим сам запрос!
    $.post(url)
        .done((newHtml: string) => {
            // Замещаем изображение загрузки на новую ссылку, пришедшую от сервера
            $(newHtml).insertAfter($img);
            $img.remove();
        })
        .fail(() => {
            // В случае ошибки ajax запроса возвращаем старое значение
            $link.insertAfter($img);
            $img.remove();
            toastError(textFail);
        });
});

$body.on("click", "a.ajax-input", function(this: HTMLAnchorElement, e: JQuery.Event) {
    e.preventDefault();
    const $link = $(this);
    if ($link.hasClass("disabled")) {
        return;
    }

    // Найдём ближайший тег контейнер в котором описан url на который делать запрос
    const $container = $link.closest("*[data-ajaxchangefieldurl]");
    if ($container.length === 0) {
        throw new Error("Element container *[data-ajaxchangefieldurl] not found for ajax field " + this.toString());
    }

    // Блокируем ссылку от повторного нажатия
    $link.addClass("disabled");

    const fieldKey: string = $link.attr("data-fieldkey")!;
    const id: string = $link.attr("data-id")!;
    let url: string = $container.attr("data-ajaxchangefieldurl")!;
    url += `${id}&fieldKey=${encodeURIComponent(fieldKey)}`;
    $.ajax(url)
        .done((editHtml: string) => {
            // Полученный html код поля для ввода вставляем после ссылки на редактирование
            const $editHtml: JQuery = $(editHtml);
            $editHtml.insertAfter($link);
            // Саму ссылку удаляем из DOM (при этом она сохраняется в переменной $link на случай cancel)
            $link.remove();

            // Найдём внутри пришедшего edit блока поле для ввода
            let $input: JQuery;
            if ($editHtml.is("input,textarea")) {
                // Пришедший блок html кода целиком является полем ввода
                $input = $editHtml;
            } else {
                // Найдём поле ввода где-то внутри
                $input = $editHtml.find("input,textarea");
            }
            // Если пришеднее input поле - это поле для ввода даты, то добавим datepicker виджет
            if ($input.hasClass("input-date") && $input.attr("type") === "text") {
                // Добавим специальный класс, чтобы поле выглядело поменьше и не дёргался экран в таблице
                $input.addClass("input-date-ajax");
                // Добавим к нему виджет выбора даты
                $input.datepicker({
                    onClose: () => {
                        handleAjaxInputSave($editHtml, $input, url);
                    }
                });
            } else {
                // Если же это не поле input-date, то добавим пару ссылок сохранить и отмена
                $(`<a href="#" style="padding-left: 15px">${textCancel}</a>`)
                    .on("click", function(e: any) {
                        e.preventDefault();
                        handleAjaxInputCancel($editHtml, $input, $link);
                    })
                    .insertAfter($input);

                $(`<a href="#">${textSave}</a>`)
                    .on("click", function(e: any) {
                        e.preventDefault();
                        handleAjaxInputSave($editHtml, $input, url);
                    })
                    .insertAfter($input);
            }

            // Навесим обработчики кнопок на поле ввода
            $input.on("keydown", function(this: HTMLInputElement, e: JQuery.Event) {
                if (e.keyCode === 13) {
                    if ($input.get(0).tagName.toLowerCase() === "textarea" && !e.ctrlKey && !e.metaKey) {
                        // В случае textarea реагируем на enter только при зажатем control
                        return;
                    }
                    e.preventDefault();
                    handleAjaxInputSave($editHtml, $input, url);
                } else if (e.keyCode === 27) {
                    e.preventDefault();
                    handleAjaxInputCancel($editHtml, $input, $link);
                }
            });

            $input.focus();
        })
        .fail((jqXHR: any) => {
            // Ошибка получения HTML кода поля для редактирования
            // Возвращаем старую ссылку к живому виду
            $link.removeClass("disabled");
            // Выведем пользователю сообщение об ошибке пришедшее от сервера
            // (скорее всего там будет что-то вроде "нет доступа")
            toastError(jqXHR.responseText);
        });
});

function handleAjaxInputSave($editHtml: JQuery, $input: JQuery, url: string) {
    if ($input.prop("disabled")) {
        // поле ввода уже заблокировано, значит предыдущий ajax запрос на сохранение ещё в процессе
        return;
    }

    // Блокируем поле для ввода на время путешетсвия ajax запроса
    $input.prop("disabled", true);

    // сохранение
    const postData = { newValue: $input.val() };
    $.post(url, postData)
        .done((newHtml: string) => {
            // удаляем те две кнопки (сохранить  и отмена)
            removeSaveCancelButtons($input);
            // вставляем новый html код поля в режиме view после поля блока для ввода
            $(newHtml).insertAfter($editHtml);
            // удаляем сам блок для ввода
            $editHtml.remove();
        })
        .fail(() => {
            // В случае ошибки ajax запроса возвращаем поле ввода в режим редактирования
            $input.prop("disabled", false);
            toastError(textFail);
        });
}

function handleAjaxInputCancel($editHtml: JQuery, $input: JQuery, $originalLink: JQuery) {
    // удаляем те две кнопки (сохранить  и отмена)
    removeSaveCancelButtons($input);
    // возвращаем на место старую ссылку
    $originalLink.insertAfter($editHtml);
    $originalLink.removeClass("disabled");
    // удаляем блок для редактирования
    $editHtml.remove();
}

function removeSaveCancelButtons($input: JQuery) {
    // удаляем те две кнопки (сохранить  и отмена), если только это не было поле даты (у даты нет этих кнопок)
    if (!($input.hasClass("input-date") && $input.attr("type") === "text")) {
        $input.next("a").remove();
        $input.next("a").remove();
    }
}
