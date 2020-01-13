import {loadSortableJs, matchWithOpposite, setUserParam, toastError} from "./Light";

declare var Sortable: any;

const PARAM_PINNED = 'Mainmenu_pinned';

// Метод вызывается, когда сервере отправляет диалог с настройками меню на клиент
// и там же происходит вызов Response::sendHeaderJsEval('Light.mainmenu.initMenuSettingsDialog()');
// см. Mainmenu.php
export function initMenuSettingsDialog() {
    // Делаем setTimeout 0, чтобы диалог успел отрисоваться
    setTimeout(() => {
        loadSortableJs().then(() => {
            Sortable.create(document.querySelector(".MainmanueSettings-Favorites"), {
                group: "mnu",
                animation: 100
            });
            Sortable.create(document.querySelector(".MainmanueSettings-AllItems"), {
                group: "mnu",
                animation: 100
            });
        });

    }, 0)

}

export function settingsDialogSubmit(dialog: any) {
    const favItems = document.querySelectorAll(".MainmanueSettings-Favorites .MainmenuSettings-Item");
    const modulesComaSeparated = Array.from(favItems)
        .map((li: HTMLLIElement) => li.getAttribute('data-module'))
        .join(',');
    $.post('?a=Core&ps=MainmenuSettings', {favorites: modulesComaSeparated})
        .done((newFavoritesHtml: string) => {
            document.querySelector('.MainmenuFavorites')!.innerHTML = newFavoritesHtml;
            dialog.close();
        })
        .fail(() => {
            toastError('Не удалось сохранить настройки меню');
            dialog.unblockButtons();
        })
}

export function showMenu() {
    $(".MasterLayout-Mainmenu").addClass("MasterLayout-Mainmenu_shown");
    $(".MasterLayout-BurgerBtn").addClass("display-none");

    // Когда пользователь открывает меню, добавим фокус в Autolaunch
    $(".MainmenuSearch").focus();
}

export function hideMenu() {
    $(".MasterLayout-Mainmenu").removeClass("MasterLayout-Mainmenu_shown");
    $(".MasterLayout-BurgerBtn").removeClass("display-none");
}

export function pinMenu() {
    $(".MasterLayout").removeClass("MasterLayout_mainmenu_unpinned");
}

export function unpinMenu() {
    $(".MasterLayout").addClass("MasterLayout_mainmenu_unpinned");
}

export function isPinned(): boolean {
    return !$(".MasterLayout").hasClass("MasterLayout_mainmenu_unpinned");
}

export function isShown(): boolean {
    return $(".MasterLayout-Mainmenu").hasClass("MasterLayout-Mainmenu_shown")
}

$(function () {
    //Навесим обработчики на пункты меню
    const $sectionheads = $(".MainmenuSectionHead");
    const $uls = $(".MainmenuSection");

    function getUlStatus($ul: JQuery) {
        return $ul.attr("data-status");
    }

    function setUlStatus($ul: JQuery, newStatus: string) {
        $ul.attr("data-status", newStatus);
        if (newStatus === "closed") {
            $ul.slideUp();
        } else {
            $ul.slideDown();
        }
    }

    // Проверим статус всех секций, сохраним в map (пригодится позже при поиске по меню)
    const ulStatuses = new Map();
    $sectionheads.each((_: any, ul: HTMLUListElement) => {
        const $ul = $(ul);
        ulStatuses.set($ul, getUlStatus($ul));
    });

    //$("ul[data-status=closed]").hide();
    $sectionheads.on("click", function (this: HTMLElement) {
        const $clikedHeader: JQuery = $(this);
        const sectionText: string = $clikedHeader.attr("data-name")!;
        const $ul: JQuery = $clikedHeader.next("ul");
        // Узнаем текущее состояние этой секции
        const newStatus = getUlStatus($ul) === "closed" ? "open" : "closed";
        setUlStatus($ul, newStatus);
        setUserParam("Mainmenu/" + sectionText, newStatus);
    });

    const $body: JQuery = $("body");

    $body.on("click", ".march8", function () {
        $body.append(
            '<div class="march8-poster-fixed"><div class="march8-poster-modal"><div class="march8-poster-container"><img src="lightpublic/i/march8.png"></div></div></div>'
        );
    });

    $body.on("click", ".march8-poster-fixed", function () {
        $body.find(".march8-poster-fixed").remove();
    });

    $(document).on("keydown", function (e: any) {
        const $marchh8poster = $body.find(".march8-poster-fixed");
        if ($marchh8poster.length != 0 && e.keyCode === 27) {
            e.preventDefault();
            $marchh8poster.remove();
        }
    });

    $(".MasterLayout-BurgerBtn").on("click", showMenu);

    $body.on("click", (e: any) => {
        // Скрыть отображаемое меню при клике куда-то в body вне этого самого меню
        if (!isPinned() && isShown() &&
            !$(e.target).closest(".MasterLayout-BurgerBtn").length &&
            !$(e.target).closest(".MasterLayout-Mainmenu").length
        ) {
            hideMenu();
        }
    });

    $(".MainmenuPinBtn").on("click", () => {
        if (isPinned()) {
            unpinMenu();
            setUserParam(PARAM_PINNED, "0");
        } else {
            pinMenu();
            setUserParam(PARAM_PINNED, "1");

        }
    });

    $(".MainmenuSearch").on("keyup", function (this: HTMLInputElement) {
        const filterValue = this.value.trim();
        const $items = $(".MeinmenuItem");
        if (filterValue === "") {
            // Отображаем всё как и раньше
            $items.removeClass("display-none");
            $uls.removeClass("display-none");
            $sectionheads.removeClass("display-none");
        } else {
            // Скрываем строки в меню, в тексте которых не встречается искомая строка
            $items.each(function (this: HTMLLIElement) {
                const $item = $(this);

                const text = $item.text();
                const title = $item.attr("title");
                const href = $item.find('a').attr('href');
                const matched = matchWithOpposite(text, filterValue)
                || matchWithOpposite(title, filterValue)
                || matchWithOpposite(href, filterValue);

                // Если нет текстовых совпадений в тексте пункта меню или в его title, то скрываем этот пункт меню
                $item.toggleClass("display-none", !matched);
            });

            // Также нужно скрыть пустые секции
            // Сначала скроем все ul блоки, внутри которых нет отображаемых li
            $uls.each(function (this: HTMLUListElement) {
                const $ul = $(this);
                if ($ul.children("li:not(.display-none)").length === 0) {
                    $ul.addClass("display-none");
                } else {
                    $ul.removeClass("display-none");
                    // Если текущая секция свёрнута, её нужно развернуть
                    // при этом факт разворачивания не будет сохранён в настройки пользователя
                    // чтобы после перезагрузки страницы пользователь увидел свои привычные настройки меню
                    if (getUlStatus($ul) === "closed") {
                        setUlStatus($ul, "open");
                    }
                }
            });
            // Теперь скроем заголовки секций, после которых идут скрытые ul
            $sectionheads.each(function (this: HTMLDivElement) {
                const $div = $(this);
                $div.toggleClass("display-none", $div.next("ul").hasClass("display-none"));
            });
        }
    });
});
