import { isLangRu, myParseInt } from "./Light";

const ru: { [key: string]: string } = {
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

function _t(text: string) {
    if (isLangRu && ru[text]) {
        return ru[text];
    }

    return text;
}

// Вспомагательная переменная - ссылка на текущую кнопку рядом с которой открыта палитра
let $currentInput: JQuery | null;
let $widget: JQuery;
let $yearSelect: JQuery;
let $allyearTr: JQuery;

function parseInputText(inputText: string) {
    if (inputText.length === 4) {
        // В поле ввода указан только год - возвращаем только его
        return {
            year: myParseInt(inputText),
            month: 0
        };
    } else if (inputText.indexOf("/") !== -1) {
        const { 0: month, 1: year } = inputText.split("/");
        return {
            year: myParseInt(year),
            month: myParseInt(month)
        };
    }

    return null;
}

function parseAndhighlightCurrentValue() {
    if ($currentInput) {
        // Распарсим текущее текстовое значение в поле для ввода и сделаем соответсвующую подсветку в виджете
        const result = parseInputText(<string>$currentInput.val());
        let year, month;
        if (result === null) {
            // В поле ввода не была указана какая либо дата
            // Устанавилваем год в текущий год
            year = new Date().getFullYear();
            // А месяц вообще никак не будем определять
            month = null;
        } else {
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
            $widget.find(`td:eq(${month})`).addClass("selected");
        }
    }
}

function setYear(year: string | number) {
    // Проверим, есть ли среди option значение года, которое указано в тексте? Если нет, динамически добавляем в выпадающий список
    if ($yearSelect.find(`option[value="${year}"]`).length === 0) {
        // Такого значения нет - добавляем динамически
        $yearSelect.append(`<option value="${year}">${year}</option>`);
    }
    // Теперь делаем нужный год выбранным в выпадающем писке
    $yearSelect.val(year);

    $allyearTr.find("td").text(_t("All") + " " + year);
}

function show($targetInput: JQuery) {
    // Запоминаем элемент ввода месяца на который пользователь только что передал фокус
    $currentInput = $targetInput;

    // Если виджет ещё ни разу не отображался на экране, создаём динамически
    if (!$widget) {
        const html = `<div class="floatingwidget">
                <div id="monthwidget__header">
                    <a href="#" style="flex: 0; margin-left: 5px;" id="monthwidget__prevlink"><</a>
                    <div style="flex: 1; text-align: center"><select id="monthwidget__yearselect"></select></div>
                    <a href="#" style="flex: 0; margin-right: 5px;" id="monthwidget__nextlink">></a>
                </div>
                <table id="monthwidget__monthstable">
                <tr id="monthwidget__allyeartr"><td colspan="3"></td></tr>
                <tr><td>${_t("Jan")}</td><td>${_t("Feb")}</td><td>${_t("Mar")}</td></tr>
                <tr><td>${_t("Apr")}</td><td>${_t("May")}</td><td>${_t("Jun")}</td></tr>
                <tr><td>${_t("Jul")}</td><td>${_t("Aug")}</td><td>${_t("Sep")}</td></tr>
                <tr><td>${_t("Oct")}</td><td>${_t("Nov")}</td><td>${_t("Dec")}</td></tr>
                </table>
                </div>`;

        $widget = $(html);
        // Вставляем в вёрстку
        $widget.appendTo("body");

        $yearSelect = $("#monthwidget__yearselect");
        $allyearTr = $("#monthwidget__allyeartr");

        // При смене года нужно также обновлять строку "весь год"
        $yearSelect.on("change", () => {
            setYear(<string>$yearSelect.val());
        });

        $("#monthwidget__prevlink").on("click", (e: any) => {
            e.preventDefault();
            setYear(myParseInt($yearSelect.val()) - 1);
        });

        $("#monthwidget__nextlink").on("click", (e: any) => {
            e.preventDefault();
            setYear(myParseInt($yearSelect.val()) + 1);
        });
    } else {
        // Виджет уже был создан в вёрстке при прошлом использовании
        $widget.removeClass("display-none");
    }

    // Перемещаем палитру непосретсвенно рядом с полем ввода
    let offset = <JQuery.Coordinates>$targetInput.offset();
    offset.top += 24;
    $widget.offset(offset);

    // Уберём или вернём строку с возможностью выбрать весь год
    const allYear = $targetInput.attr("data-allyear") === "1";
    $allyearTr.toggleClass("display-none", !allYear);

    // Установим значение года в выпадающем списке в соответсвие с параметрами поля ввода
    $yearSelect.empty();
    const yearFrom = myParseInt($targetInput.attr("data-yearfrom"));
    const yearUntil = myParseInt($targetInput.attr("data-yearuntil"));
    for (let tmpYear = yearFrom; tmpYear <= yearUntil; tmpYear++) {
        $yearSelect.append(`<option value="${tmpYear}">${tmpYear}</option>`);
    }

    // Установим правильное значение в выпадающем списке и подсветим месяц в зависимости от текста указанного в поле ввода
    parseAndhighlightCurrentValue();
}

function hide() {
    $currentInput = null;
    $widget.addClass("display-none");
}

// В момент on dom ready инициализируем основные обработчики клика на body
$(function() {
    $("body")
        .on("focus", "input[data-type=Month]", function(e: any) {
            // Пользователь передал фокус на какое-то поле ввода месяца - нужно отобразить виджет
            show($(e.target));
        })
        .on("keyup", "input[data-type=Month]", function(e: any) {
            if ($currentInput && $currentInput.get(0) === e.target) {
                // Если виджет уже отображается и элемент на котором происходит keyup соответсвует текущему элементу виджета
                // то нужно заново распарсить значение месяца и обновить подсветку внутри виджета
                parseAndhighlightCurrentValue();
            }
        })
        .on("click", function(e: any) {
            // Пользователь куда-то кликнул - нужно либо подтвердить выбор месяца, либо скрыть виджет, если клик куда-то во вне
            if ($currentInput) {
                const target = e.target as HTMLElement;
                if (!$.contains($widget.get(0), target)) {
                    // Если клик был сделан куда-то вне уже открытой палитры, то её нужно закрыть
                    if (target.getAttribute("data-type") !== "Month") {
                        hide();
                    }
                } else {
                    if ((<Element>e.target).tagName.toLowerCase() === "td") {
                        // Клик был совершен где-то внутри виджета и попал на одну из td ячеек - применим выбранный месяц
                        const month: number = $widget.find("td").index(target);
                        const year = <string>$yearSelect.val();
                        const resultText: string =
                            month !== 0 ? (month < 10 ? "0" : "") + month.toString() + "/" + year : year;
                        $currentInput.val(resultText);
                        $currentInput.trigger("change");

                        // Сразу после этого закроем виджет
                        hide();
                    }
                }
            }
        })
        .on("keydown", function(e: any) {
            if ($currentInput) {
                if (e.which === 27) {
                    hide();
                } else if (e.which === 13) {
                    // При тупом нажатии на enter устанавливаем текущую дату, если текущее значение пусто
                    if ($currentInput.val() === "") {
                        let month = new Date().getMonth().toString();
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
