import {setUserParam, toastError, getOppositeString} from "./Light";

$(function () {
    //Навесим обработчики на пункты меню
    const $mainmenu: JQuery = $("#mainmenu");

    const $sectionheads = $mainmenu.find(".mainmenu__sectionhead");
    const $uls = $mainmenu.find(".mainmenu__section");
    const $items = $mainmenu.find(".mainmenu__li");

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
    $mainmenu.on("click", ".mainmenu__sectionhead", function (this: HTMLElement) {
        const $clikedHeader: JQuery = $(this);
        const sectionText: string = $clikedHeader.attr("data-name")!;
        const $ul: JQuery = $clikedHeader.next("ul");
        // Узнаем текущее состояние этой секции
        const newStatus = getUlStatus($ul) === "closed" ? "open" : "closed";
        setUlStatus($ul, newStatus);
        setUserParam("Mainmenu/" + sectionText, newStatus);
    });

    const $body: JQuery = $("body");
    const $layout: JQuery = $("#global-layout");
    const $menu: JQuery = $(".global-menu");
    const $menuBut: JQuery = $(".hideshow-menu-button");
    const $pinBut: JQuery = $(".pin-menu-button");

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

    const showMenu = function () {
        $menu.addClass("global-menu-show");
        $menuBut.addClass("display-none");

        // Когда пользователь открывает меню, добавим фокус в Autolaunch
        $("#mainmenu__search").focus();
    };

    const hideMenu = function () {
        $menu.removeClass("global-menu-show");
        $menuBut.removeClass("display-none");
    };

    $menuBut.on("click", showMenu);

    $body.on("click", (e: JQuery.Event) => {
        if (
            $menu.hasClass("global-menu-show") &&
            !$(e.target).closest(".hideshow-menu-button").length &&
            !$(e.target).closest(".global-menu").length
        ) {
            hideMenu();
        }
    });

    $pinBut.on("click", () => {
        if ($layout.hasClass("global-menu-unfix")) {
            $layout.removeClass("global-menu-unfix");
            setUserParam("global-menu-unfix", "0");
        } else {
            $layout.addClass("global-menu-unfix");
            setUserParam("global-menu-unfix", "1");
        }
    });

    let fileuploadInited = false;
    $("#mainmenu__avatar").on("click", function () {
        if (!fileuploadInited) {
            let script = document.createElement("script");
            script.src = "lightpublic/js/fileupload.min.js";
            script.type = "text/javascript";
            script.async = true;
            script.onload = () => {
                $("#mainmenu__avatarinputfile").fileupload({
                    url: "index.php?a=Core&ps=UploadUserAvatar",
                    dataType: "json",
                    sequentialUploads: true,
                    disableImageResize: /Android(?!.*Chrome)|Opera/.test(window.navigator && navigator.userAgent),
                    imageMaxWidth: 800,
                    imageMaxHeight: 800,
                    imageCrop: true,
                    done: function (_: any, data: any) {
                        if (data.result.error) {
                            toastError(data.result.text);
                        } else {
                            const newSrc = `index.php?a=Core&ps=GetUserAvatar&time=${Date.now()}`;
                            $("#mainmenu__avatar")
                                .find("img")
                                .attr("src", newSrc);
                        }
                    }
                });

                fileuploadInited = true;

                document.getElementById("mainmenu__avatarinputfile")!.click();
            };

            document.getElementsByTagName("head")[0].appendChild(script);
        } else {
            document.getElementById("mainmenu__avatarinputfile")!.click();
        }
    });

    $("#mainmenu__search").on("keyup", function (this: HTMLInputElement) {
        const filterValue = this.value.toLowerCase().trim();
        if (filterValue === "") {
            // Отображаем всё как и раньше
            $items.removeClass("display-none");
            $uls.removeClass("display-none");
            $sectionheads.removeClass("display-none");
        } else {
            // Подготовим строку в друго раскладке
            const filterValueOpposite = getOppositeString(filterValue);

            // Скрываем строки в меню, в тексте которых не встречается искомая строка
            $items.each(function (this: HTMLLIElement) {
                const $item = $(this);

                const text = $item.text();
                const title = $item.attr("title");
                const matched =
                    text.toLowerCase().indexOf(filterValue) !== -1 ||
                    text.toLowerCase().indexOf(filterValueOpposite) !== -1 ||
                    (title &&
                        (title.toLowerCase().indexOf(filterValue) !== -1 ||
                            title.toLowerCase().indexOf(filterValueOpposite) !== -1));

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
