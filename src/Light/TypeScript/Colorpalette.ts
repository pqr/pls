module Light.colopalette {

    export type ChooseCallback = (newColor:string) => void;

    // Вспомагательная переменная - ссылка на текущую кнопку рядом с которой открыта палитра
    let $currentColorBtn:JQuery | null;
    let $colorpalette:JQuery;
    let callback:ChooseCallback | null = null;

    let allCssClasses = ''; // Заготовим текстовую переменную, которая пригодится для removeClass (очистка всех цветов)
    for(let i=1; i <= 12; i++) {
        allCssClasses += 'color' + i + ' ';
    }

    export function showPalette($targetBtn:JQuery, onChooseCallback?:ChooseCallback) {

        // Запоминаем кнопку по которой нажал пользователь для открытия палитры - пригодится в будущем
        $currentColorBtn = $targetBtn;

        // Если палитра ещё ни разу не отображалась на экране, её нет в вёрстке. Создаём динамически.
        if (!$colorpalette) {
            $colorpalette = $('<div id="colorpalette-div" class="floatingwidget"><table><tr><td>0</td><td class="color1">1</td><td class="color2">2</td></tr><tr><td class="color3">3</td><td class="color4">4</td><td class="color5">5</td></tr><tr><td class="color6">6</td><td class="color7">7</td><td class="color8">8</td></tr><tr><td class="color9">9</td><td class="color10">10</td><td class="color11">11</td></tr></table></div>');
            // Добавим всплывающие подсказки
            let colorHints = _lightPageParams['ColorHints'];
            if (colorHints) {
                for (let i = 1; i <= 11; i++) {
                    $colorpalette.find(".color" + i).prop("title", colorHints['ColorHint' + i]);
                }
            }
            // Вставляем в вёрстку
            $colorpalette.appendTo('body');
        } else {
            // Палитра уже была создана в вёрствке при прошлом использовании. Возможно, она была скрыта - значит нужно
            // убрать класс скрывающий палитру
            $colorpalette.removeClass('display-none');
        }

        // Перемещаем палитру непосретсвенно рядом с нажатой кнопкой
        let offset = $targetBtn.offset();
        offset.top += 17;
        $colorpalette.offset(offset);

        callback = onChooseCallback || null;
    }

    function hidePalette() {
        // Обнуляем указатель на "текущую" кнопку
        $currentColorBtn = null;
        // Скрываем виджет
        $colorpalette.addClass('display-none');
        // Очищаем ссылку на функцию callback при закрытии палитры
        callback = null;
    }

    /**
     * Раскрашивает некий элемент на странице, который символизирует выбранный цвет.
     * Это может быть строка в таблице или div в фильтрах или ячейка в режиме просмотра отдельной записи view/edit
     * @param $container - тот самый элемент, который нужно раскрасить
     * @param newColor - цвет
     */
    function updateColorContainer($container:JQuery, newColor:string) {
        // Перед тем как добавлять новый класс, удалим все возможные старые значения "color1 color2 color3 ... color11"
        $container.removeClass(allCssClasses);
        if (newColor !== '0') {
            $container.addClass('color' + newColor);
        }
    }

    // В момент on dom ready инициализируем основные обработчики клика на body
    $(function () {
        const $body = $('body');

        $body.on("click", function (e) {

            const $target = $(e.target);
            // Проверим, куда был сделан клик?
            if ($target.hasClass("colorpalette")) {
                // Клик был сделан непосредственно по кнопке палитры.
                // Если палитра была в открытом состоянии и пользователь ещё раз кликнул по текущей кнопке - нужно прсто её закрыть
                if ($currentColorBtn && e.target === $currentColorBtn.get(0)) {
                    hidePalette();
                } else {
                    // Либо палира вообще не была открыта, либо была открыта какая-то другая палитра - не важно,
                    // переоткрываем палитру вновь по только что кликнутой кнопке
                    showPalette($target);
                }
            } else {
                // Клик был сделан куда-то вне кнопки открывающей палитру.
                // Здесь нас интересует только тот случай, если палитра уже была открыта
                if ($currentColorBtn) {
                    if (!$.contains($colorpalette.get(0), e.target)) {

                        // Если клик был сделан куда-то вне уже открытой палитры, то её нужно закрыть
                        hidePalette();

                    } else if ((<Element>e.target).tagName.toLowerCase() === 'td') {

                        // Клик был совершен где-то внутри палитры и попал на одну из td ячеек - применим новый цвет!
                        const newColor = $target.text();

                        // Окрасим элемент-конетйнер внутри которого была расположена текущая кнопка, открывшая палитру
                        let $container = $currentColorBtn.closest(".color-container");
                        if ($container.length === 0) {
                            $container = $currentColorBtn.closest("table.form td, table.table tr");
                        }
                        if ($container.length > 0) {
                            updateColorContainer($container, newColor);
                        }

                        // Если рядом с кнопкой находилось скрытое поле input с классом color-input - запишем туда новое значение цвета
                        $container.find('input.color-input').val(newColor);

                        // Отправим ajax запрос на сервер, если у кнопки есть параметр source
                        let url = $currentColorBtn.attr('data-source');
                        if (url) {
                            url += '&fieldKey=Color&newValue=' + newColor;
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
        $('input.color-input').on("change", function(this:HTMLInputElement) {
            const $container = $(this).closest(".color-container");
            if ($container.length > 0) {
                updateColorContainer($container, this.value);
            }
        });
    });

}
