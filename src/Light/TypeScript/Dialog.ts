module Light {

    let openedDialogsStack: Array<Dialog> = [];

    export interface Button {
        id?: string;
        text: string;
        isPrimary?: boolean;
        className?: string;
        handler: (dialog: Dialog, buttonId: string) => void;
    }

    export interface DialogOptions {
        buttonOkText?: string;
        buttonOkHandler?: (dialog: Dialog) => void;
        buttonCancelText?: string;
        buttons?: Array<Button>;
        width?: string;
        height?: string;
        top?: string;
        afterClose?: () => void;
    }

    export class Dialog {
        public $div: JQuery | null;
        public $divContent: JQuery | null;

        private top: string;
        private afterClose: () => void;
        private destroyAfterClose: boolean;
        private $overlay: JQuery | null;
        private defaultSubmitHandler: (dialog: Dialog, buttonId: string) => void;

        constructor(content: any, options: DialogOptions = {}, destroyAfterClose: boolean = true) {

            let $div = $('<div class="lightdialog display-none">');

            if (options.width) {
                $div.css('width', options.width);
            }
            if (options.height) {
                $div.css('height', options.height);
            }
            if (options.top) {
                this.top = options.top;
                $div.css('top', options.top);
            }

            //Добавляем сожержимое
            let $divContent = $('<div class="lightdialog__content">');
            if (content) {
                $divContent.append(content)
            }
            $divContent.appendTo($div);


            //Добавляем footer в котором будут располагаться кнопки
            let $divFooter = $('<div class="lightdialog__footer">').appendTo($div);

            //Сгенерируем кнопки

            //Для начала вставим кнопку cancel (если только она не была принудительно отключена значением  null)
            if (options.buttonCancelText !== null) {
                $('<a href="#cancel" class="lightdialog__button waves-effect grey lighten-5 btn-flat"></a>')
                    .html(options.buttonCancelText || (options.buttonOkText ? 'Cancel' : 'Close'))
                    .on("click", (e:any) => {
                        e.preventDefault();
                        // обрабатываем клик, только если кнопка уже не заблокирована
                        if (!$(e.currentTarget).hasClass('disabled')) {
                            this.close();
                        }
                    })
                    .appendTo($divFooter);
            }

            //Далее вставим кнопку Ok, если она была определена
            if (options.buttonOkText) {
                $('<a href="#ok" class="lightdialog__button waves-effect waves-light btn bgcolor-btn-primary"></a>')
                    .html(options.buttonOkText)
                    .on("click", (e) => {
                        e.preventDefault();
                        // обрабатываем клик, только если кнопка уже не заблокирована
                        if (!$(e.currentTarget).hasClass('disabled')) {
                            this.blockButtons();
                            this.defaultSubmitHandler(this, 'ok');
                        }
                    })
                    .appendTo($divFooter);

                if (options.buttonOkHandler) {
                    this.defaultSubmitHandler = options.buttonOkHandler;
                }
            }

            //Если пользователь указал массив произвольных кнопок, то добавим и их
            if (Array.isArray(options.buttons)) {
                options.buttons.forEach((button: Button, index) => {

                    const btnId = button.id || 'btn' + index;
                    const $btn = $(`<a href="#${btnId}" class="lightdialog__button waves-effect"></a>`)
                        .html(button.text)
                        .on("click", (e) => {
                            e.preventDefault();
                            this.blockButtons();
                            button.handler(this, btnId);
                        })
                        .appendTo($divFooter);

                    if (button.className) {
                        //Добавим пользовательские классы
                        $btn.addClass(button.className);
                    } else {
                        //Пользовательских классов не было, добавим стандартные классы
                        if (button.isPrimary) {
                            //Если это Primary кнопка, то делаем её синим цветом с тенью
                            $btn.addClass('waves-light btn bgcolor-btn-primary');
                        } else {
                            //Для прочих кнопок (не Primary) делаем плоский стиль
                            $btn.addClass('btn-flat waves-green');
                        }
                    }

                    if (button.isPrimary) {
                        //Если эта кнопка отмечена как основная, то именно её handler запишем в defaultSubmitHandler
                        //что пригодится для автоматического запуска формы (если такая есть)
                        this.defaultSubmitHandler = () => {
                            button.handler(this, btnId);
                        }
                    }

                });
            }

            $div.appendTo(document.body);
            Light.addStandardListeners($divContent);
            Light.forms.init($divContent);
            Light.tables.init($divContent);
            if (Light.subtables) {
                Light.subtables.init($divContent);
            }

            //Проверим, содержал ли content какую либо форму?
            const $form = $div.find('form:not(.filters-form)');
            if ($form.length > 0) {

                if (!this.defaultSubmitHandler) {
                    //Если есть форма и к этому моменту не сформировался какой-то defaultSubmitHandler, то сделаем его
                    //в виде отпарвки формы
                    this.defaultSubmitHandler = () => $form.submit();

                    // Проверим, нужно ли отправлять эту форму через ajax?
                    const action = $form.attr('action');
                    if (action) {
                        if (action.indexOf('#ajax') !== -1) {

                            // Поскольку в action встретился якорь #ajax - это значит будем отправлять через ajax
                            $form.on("submit", (e) => {
                                e.preventDefault();
                                this.blockButtons();
                                const url = $form.attr('action');
                                const postData = $form.serialize();
                                //Отправляем POST запрос на сервер. В случае ошибки - нужно разблокировать кнопки
                                //Если всё успешно - тут мы ничего не делаем. Сервер сам должен решить что делать, например вернуть x-light-redirect
                                $.post(url, postData)
                                    .done(() => {
                                        this.unblockButtons();
                                        this.close();
                                    })
                                    .fail(() => {
                                        this.unblockButtons();
                                    });
                            });

                        } else {

                            // Форму нужно отправить простым POST запросом, но при этом не забудем заблокировать кнопки диалога
                            $form.on("submit", () => this.blockButtons());

                        }
                    }

                } else {

                    //Если же defaultSubmitHandler был определён выше (в кропке Ok или в какой-либо другой кнопке isPrimary
                    //то вместо стандартного сабмита формы запустим эту специально определённую функцию
                    $form.on('submit', (e) => {
                        e.preventDefault();
                        this.blockButtons();
                        this.defaultSubmitHandler(this, 'form');
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

        open() {

            const dialogNum = openedDialogsStack.length;

            // Отключаем прокрутку основного окна
            document.body.style.overflow = 'hidden';

            // На всякий случай разблокируем все кнопки, если они были заблокированы до этого
            this.unblockButtons();

            //Создаём затемненеие основного окна
            this.$overlay = $('<div class="lightdialogoverlay">')
                .css({'z-index': 1000 + dialogNum * 2})
                .appendTo(document.body);

            const $div = this.$div;

            if ($div) {
                // Убираем класс 'display-none' в самом начале, чтобы можно было посчитать outerWidth и outerHeight
                $div.removeClass('display-none');
                $div.css({'z-index': 1000 + dialogNum * 2 + 1});
            }

            // Передвигаем диалог в центр экрана
            this.positiionCenter();

            $(window).on("resize", this.positiionCenter);

            if ($div) {
                // Поставим фокус на первый элемент формы, только если это не виджет выбора даты (он некрасиво раскрывается)
                const $firstInput = $div.find('input[type=text]:first, select[data-type=Select2Multiple]:first, textarea:first');
                if ($firstInput.length > 0 && !$firstInput.hasClass('input-date')) {
                    $firstInput.get(0).focus();
                }
            }

            //Добавим открытый диалог в стек диалогов
            openedDialogsStack.push(this);

        }

        positiionCenter() {

            if (this.$div) {
                let css: any = {
                    display: "block",
                    left: ($(window).width() - this.$div.outerWidth()) / 2
                };

                if (css.left < 0) {
                    css.left = 0;
                    css.width = $(window).width();
                }

                if (this.top === undefined) {
                    //Если пользователь жестко не задал настройку top, то центрирует диалог по вертикали автоматически
                    css.top = ($(window).height() - this.$div.outerHeight()) / 2;
                    if (css.top < 0) {
                        css.top = 0;
                        css.height = $(window).height();
                    }
                }

                this.$div.css(css);
            }
        }

        close() {

            // Включаем прокрутку основного окна
            document.body.style.overflow = '';

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
                } else {
                    // Если не выставлен флаг this.destroyAfterClose, то просто скроем div содержащий этот диалог
                    this.$div.addClass('display-none');
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
            openedDialogsStack = openedDialogsStack.filter(openedDialog => openedDialog !== this);
        }

        blockButtons() {
            if (this.$div) {
                this.$div.find('.lightdialog__footer').find('a').addClass('disabled');
            }
        }

        unblockButtons() {
            if (this.$div) {
                this.$div.find('.lightdialog__footer').find('a').removeClass('disabled');
            }
        }

    }

    $(document).on('keyup', function (e) {
        if (e.keyCode === 27) {   // ESC key
            if (openedDialogsStack.length > 0) {
                //Если есть хотя бы один открытый диалог, убираем закрываем верхний из стека
                openedDialogsStack[openedDialogsStack.length - 1].close();
            }
        }
    });

}