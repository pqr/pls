(function () {
    "use strict";

    var intros = Light.getPageParam('intros');

    // Пройдём по всем заявленным шагам и оставим только те, для которых найдены элементы на странице
    var hash, realIntros = [], selectorAndText, $el;
    for (hash in intros) {
        selectorAndText = intros[hash];
        $el = $(selectorAndText[0]);
        if ($el.length > 0) {
            realIntros.push({
                hash: hash,
                $el: $el,
                text: selectorAndText[1]
            });
        }
    }

    //Если после первичной проверки были найдены элементы подпадающие под подсказки - добавим подсказки!
    if (realIntros.length) {

        //Суть плагина такова, что он навешивается на некий ol список содержащий подсказки (инициализация зи markup)
        //поскольку в нашем оригинальном html коде страницы нет этого ol списка, создадим его динамически, добавив в body
        var $ol = $("<ol></ol>");
        var joyRideCounter = 0;
        //Перебираем шаги, добавляя элементы li в этот список
        realIntros.forEach(function (intro, index) {

            //Добавим элементу на который будет указывать подсказка уникальный класс
            var cssClass = "joyride" + joyRideCounter;
            intro.$el.addClass(cssClass);

            //Создаём li со ссылкой на уникальный класс
            var $li = $("<li></li>");
            $li.attr("data-class", cssClass);
            $li.attr("data-options", "tipLocation:top");
            $li.html('<p>' + intro.text + '</p>');

            if (index === realIntros.length - 1) {
                $li.attr("data-button", "Close");
            }

            $ol.append($li);

            joyRideCounter++;

        });

        $(window).on('load', function () {
            $ol.joyride({
                autoStart: true,
                postStepCallback: function (index) {
                    //Light.setUserParam('intro' + module + '_' + introName, 1);
                    Light.setUserParam('intro' + realIntros[index].hash, 1);
                }
            });
        });

    }

})();