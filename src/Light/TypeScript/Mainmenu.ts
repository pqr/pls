$(function () {
    //Навесим обработчики на пункты меню
    const $mainmenu = $("#mainmenu");
    //$("ul[data-status=closed]").hide();
    $mainmenu.on("click", ".mainmenu__sectionhead", function (this:HTMLElement) {
        const $clikedHeader = $(this);
        const sectionText = $clikedHeader.attr("data-name");
        const $ul = $clikedHeader.next('ul');
        //Узнаем текущее состояние этой секции
        const newState = $ul.attr("data-status") === 'closed' ? 'open' : 'closed';
        $ul.attr("data-status", newState);
        if (newState === 'closed') {
            $ul.slideUp();
        } else {
            $ul.slideDown();
        }
        Light.setUserParam("Mainmenu/" + sectionText, newState);
    });

    const $body = $("body");
    const $layout = $("#global-layout");
    const $menu = $(".global-menu");
    const $menuBut = $(".hideshow-menu-button");
    const $pinBut = $(".pin-menu-button");


    const showMenu = function () {
        $menu.addClass("global-menu-show");
        $menuBut.addClass("display-none");
    };

    const hideMenu = function () {
        $menu.removeClass("global-menu-show");
        $menuBut.removeClass("display-none");
    };

    $menuBut.on("click", showMenu);

    $body.on("click", function (event) {
        if ($menu.hasClass("global-menu-show")
            && !$(event.target).closest('.hideshow-menu-button').length
            && !$(event.target).closest('.global-menu').length) {
            hideMenu();
        }
    });

    $pinBut.on("click", function () {
        if ($layout.hasClass("global-menu-unfix")) {
            $layout.removeClass("global-menu-unfix");
            Light.setUserParam("global-menu-unfix", "0");
        } else {
            $layout.addClass("global-menu-unfix");
            Light.setUserParam("global-menu-unfix", "1");
        }
    });

    let fileuploadInited = false;
    $("#mainmenu__avatarimg").on('click', function(){

        if (!fileuploadInited) {

            let script = document.createElement('script');
            script.src = 'lightpublic/js/fileupload.min.js';
            script.type = 'text/javascript';
            script.async = true;
            script.onload = () => {

                $("#mainmenu__avatarinputfile").fileupload({
                    url: 'index.php?a=Core&ps=UploadUserAvatar',
                    dataType: 'json',
                    sequentialUploads: true,
                    disableImageResize: /Android(?!.*Chrome)|Opera/
                        .test(window.navigator && navigator.userAgent),
                    imageMaxWidth: 800,
                    imageMaxHeight: 800,
                    imageCrop: true,
                    done: function (_:any, data:any) {
                        if (data.result.error) {
                            Light.toastError(data.result.text);
                        } else {
                            $("#mainmenu__avatarimg").attr('src', data.result.imgUrl);
                        }
                    }
                });

                fileuploadInited = true;

                document.getElementById('mainmenu__avatarinputfile')!.click();
            };

            document.getElementsByTagName('head')[0].appendChild(script);

        } else {

            document.getElementById('mainmenu__avatarinputfile')!.click();

        }

    });

});