namespace Light {
    export namespace module {
        export type ModuleState = "list" | "addnew" | "view" | "edit";

        import Form = Light.forms.Form;
        const state: ModuleState = Light.getPageParam("state");

        // Если сейчас режим Edit или AddNew - проинициализируется jQuery объект формы редактирования
        // он понадобится во многих методах дальше
        let editForm: Form;
        const objectId: number = Light.parseInt(Light.getPageParam("id"));
        let validationHandler: () => boolean;

        /**
         * Обновляет div в котором находятся выпадающий список с massaction
         * Обновление происходит во время прокрутки таблицы или при выделении строк таблицы
         */
        function updateMassactionDiv() {
            const listTable = Light.tables.get(_lightModule);
            const $massactionDiv = $(".listmassaction");
            if ($massactionDiv.length === 0) {
                // В этом модуле вообще нет div отвечающего за massaction
                return;
            }

            // Сразу удаляем все total-span, которые могли быть в этом div -
            // в них отображались старые знаения total по полям
            // (ниже будет пересчёт этих total и повторный вывод)
            $massactionDiv.find(".total-span").remove();

            const selectedCount = listTable.getSelectedCount();
            if (selectedCount === 0) {
                // Если ничего не выделено, больше ничего и не делаем
                $massactionDiv.removeClass('sticky');
                return;
            }

            // Если что-то выделено галочками, обязательно добавляем класс sticky, который выставит position: sticky; bottom: 0;
            $massactionDiv.addClass('sticky');

            const tableTotalRowCalcFieldsKeys = Light.getPageParam("tableTotalRowCalcFieldsKeys");
            if (tableTotalRowCalcFieldsKeys && tableTotalRowCalcFieldsKeys.length) {
                // Для этой таблицы заданы какие-то колонки по которым считается total
                // в случае выделения строк галочками нужно пересчитать total и вывести его в massaction div
                const $thead = listTable.getTableNode().find("thead");
                tableTotalRowCalcFieldsKeys.forEach(fieldKey => {
                    const $th = $thead.find("th[data-fieldkey='" + fieldKey + "']");
                    if ($th.length) {
                        // Узнаем порядковый номер колонки, чтобы далее прочитать все значения
                        // в выделенных строках
                        const colIndex = $thead.find("th").index($th);

                        // Обычно для подсчёта используются колонки типа суммы
                        // Иногда это сумма с валютой
                        // Сразу заготовим Map с разбивкой по валютам. Если в данной колонке нет валют
                        // то в итоге получим Map из одного элемента (ключём будет пустая валюта - пустая строка)
                        const totalPerCurrency = new Map<string, number>();

                        // Пройдём по всем выделенным строкам
                        listTable.getSelectedRows().each((_, tr) => {
                            const tdHtml = $(tr)
                                .find("td")
                                .eq(colIndex)
                                .html();

                            // HTML код внутри ячейки может содержать <br>
                            // это значит несколько сумм в режиме groupconcat
                            // разобьём на элементарные строки
                            tdHtml.split("<br>").forEach((oneLineHtml: string) => {
                                // Вырежем все теги (которые обычно оформляют суммы)
                                const text = $(oneLineHtml).text();

                                // Попробуем распарсить текст, возможно там какие суммы с валютами?
                                // Например "123 456.78 USD" или "1 234.00 $"
                                // По умолчанию примим валюту пустой
                                let currencyIso = "";
                                const parts = text.split(" ");
                                // Прововерим последнюю часть массива после разбивки по пробелам - там может быть реальная валюта
                                if (!$.isNumeric(parts[parts.length - 1])) {
                                    // Нашли валюту в конце числа, вынимаем валюту из массива частей
                                    currencyIso = parts.pop();
                                }
                                // Оставшиеся части после изъятия валюты склеиваем обратно
                                const amount = Light.parseFloat(parts.join(""));
                                if (!totalPerCurrency.has(currencyIso)) {
                                    // Нашли какую-то новую валюту, которая не встречалась ранее
                                    totalPerCurrency.set(currencyIso, amount);
                                } else {
                                    // Эта валюта уже есть в Map totalPerCurrency, прибавляем текующую сумму amount
                                    totalPerCurrency.set(currencyIso, totalPerCurrency.get(currencyIso) + amount);
                                }
                            });
                        });

                        // После того как прошлись по всем строкам и собрали результат в totalPerCurrency
                        // выведем этот результат в massaction div

                        let perCurrencyBeautyAmounts = [];
                        totalPerCurrency.forEach((total: number, currencyIso: string) => {
                            if (total != 0) {
                                perCurrencyBeautyAmounts.push(Light.getBeautyMoney(total, true, currencyIso));
                            }
                        });
                        if (perCurrencyBeautyAmounts.length > 0) {
                            // Имя поля возьмём из th, чтобы вывести внизу в massaction div
                            const fieldName = $th
                                .html()
                                .replace(/<br>/g, " ")
                                .replace(/<\/?[^>]+>/gi, "");

                            const $totalSpan = $(`<span class="total-span nobr"></span>`)
                                .append(`<strong>${fieldName}:&nbsp;</strong>`);
                            // Склеиваем все суммы по валютам через запятую и доавляем в текущий $totalSpan
                            $totalSpan.append(perCurrencyBeautyAmounts.join(', '))

                            // Итоговый результат вставляем в $massactionDiv
                            $massactionDiv.append($totalSpan);
                        }
                    }
                });
            }
        }

        function initListListeners() {
            try {
                const listTable = Light.tables.get(_lightModule);
                listTable.onSelectRow(updateMassactionDiv);
                listTable.onSelectAll(updateMassactionDiv);
            } catch (e) {
                console.log(e);
            }
        }

        function blockSaveButtons(block: boolean) {
            editForm
                .getFormNode()
                .find(".btn-save")
                .prop("disabled", block);
        }

        function showErrorDialog(responseText: string) {
            // Уберём красную окраску у старых проблемных полей
            $(".input-error").removeClass("input-error");

            // Окрасим новые проблемные поля в красный и добавим title, заодно соберём все сообщения для диалога
            // в отдельный массив
            let dialogBodyHtml;
            let hasCriticalError = false;

            interface ErrorItem {
                msg: string;
                fieldKey?: string;
                critical?: boolean;
            }

            const errors: ErrorItem[] | false | null = Light.parseJson(responseText, false);
            if (errors === false || errors === null) {
                // Не удалось распарсить json, выведем весь текст ошибки как есть!
                dialogBodyHtml = responseText;
                hasCriticalError = true;
            } else {
                // Подсветим все поля с ошибками
                errors.forEach(err => {
                    if (err.fieldKey) {
                        editForm.highlightErrorField(err.fieldKey);
                    }
                });

                // Сформируем тело для диалога из списка ошибок
                if (errors.length > 1) {
                    dialogBodyHtml = `<ol class="save-errors">${errors
                        .map(({msg}) => `<li>${msg}</li>`)
                        .join("")}</ol>`;
                } else {
                    dialogBodyHtml = errors[0].msg;
                }

                // Проверим, были ли среди ошибок критчные? (хотя бы одна)
                hasCriticalError = errors.some(({critical}) => critical !== undefined && critical);
            }

            const options: DialogOptions = {
                buttonCancelText: hasCriticalError ? "Close" : "Cancel",
                afterClose: () => {
                    $(".input-error:first").focus();
                }
            };

            if (!hasCriticalError) {
                // Если не было критичных ошибок, то добавим кнопку 'Confirm Save'
                options.buttonOkText = "Confirm Save";
                options.buttonOkHandler = function(dialog) {
                    dialog.close();
                    // Вызываем метод сохранения с параметром true - чтобы в ajax запрос к серверу передать GET параметр
                    // saveanyway=1 - в этом случае на серверной стороне будут проигнорированы все некритиные ошибки
                    save(true);
                };
            }

            new Dialog(dialogBodyHtml, options).open();
        }

        /**
         * Эта функция может вызываться из validationHandler. Например, VInvoice диалоговое окно
         */
        export function save(saveanyway: boolean, additionalData?: Object) {
            blockSaveButtons(true);
            const $editForm = editForm.getFormNode();

            Light.showProgress($editForm);

            let postData = $editForm.serialize();
            let url = <string>$editForm.attr("action")!;
            if (saveanyway) {
                url += "&saveanyway=1";
            }

            if (additionalData) {
                //Если в этот метод были принудительно переданы какие-то данные, то добавим их в POST запрос
                //Это могут быть данные из диалога подтверждающего сохранение (если такой был при налаичии не критичных ошибок)
                postData += "&" + $.param(additionalData);
            }

            $.post(url, postData)
                .done((_, __, jqXHR:any) => {
                    //В заголовках ответа должен прийти Location куда перенаправить пользователя после сохранения
                    if (!jqXHR.getResponseHeader("x-light-redirect")) {
                        //Если же нет этого заголовка для перенаправления, то разблокируем кнопки Save и выведем диалог с ошибками
                        Light.hideProgress($editForm);
                        blockSaveButtons(false);
                        //Если в пришедшем ajax ответе от сервера есть Header заголовок указывающий на то, что ответ содержит в себе
                        //какое-то диалоговое окно, то в этом методе ничего делать не будем. Диалог отрисуется средствами Light.js
                        if (!jqXHR.getResponseHeader("x-light-showdialog")) {
                            //Нет url для редиректа, значит какая-то ошибка в sql или ешё где - выведем текст ошибки
                            showErrorDialog(jqXHR.responseText);
                        }
                    }
                })
                .fail((jqXHR) => {
                    blockSaveButtons(false);
                    Light.hideProgress($editForm);

                    //Если в пришедшем ajax ответе от срвера есть Header заголовок указывающий на то, что ответ содержит в себе
                    //какое-то диалоговое окно, то в этом методе ничего делать не будем. Диалог отрисуется средствами Light.js
                    if (!jqXHR.getResponseHeader("x-light-showdialog")) {
                        showErrorDialog(jqXHR.responseText);
                    }
                });
        }

        export function getState(): ModuleState {
            return state;
        }

        export function getObjectId(): number {
            return objectId;
        }

        export function getModuleSessionParams(): string {
            return Light.getPageParam("moduleSessionParams");
        }

        export function getFieldNode(fieldKey: string): JQuery {
            return editForm.getFieldNode(fieldKey);
        }

        export function getFieldValue(fieldKey: string): any {
            return editForm.getFieldValue(fieldKey);
        }

        export function setFieldValue(fieldKey: string, newValue: any) {
            editForm.setFieldValue(fieldKey, newValue);
        }

        export function onFieldChange(fieldKey: string, handler: (newValue: any, $input: JQuery) => void) {
            editForm.onFieldChange(fieldKey, handler);
        }

        export function highlightErrorField(fieldKey: string) {
            Light.module.getFieldNode(fieldKey).addClass("input-error");
        }

        export function ajaxChangeField(id: number, fieldKey: string, newValue: string): JQueryXHR {
            return $.post(`?a=${_lightModule}&id=${id}&func=ChangeField`, {
                fieldKey: fieldKey,
                value: newValue
            });
        }

        export function setValidationHandler(handler: () => boolean): void {
            validationHandler = handler;
        }

        //Обработчик сохранения
        const $body = $("body");
        $body.on("click", "#edit-form .btn-save", function(e) {
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

        $body.on("change", "#massaction-select", function(this: HTMLSelectElement, e: JQueryEventObject) {
            e.preventDefault();

            // Узнаем значение в выпадающем списке - именно он будет именем того massaction, который нужно выполнить
            const select = this;
            const action = select.value;

            let ids;

            //Соберём значения чекбоксов в таблице и отправим их ajax POST запросом
            try {
                ids = Light.tables.get(_lightModule).getSelectedValues();
            } catch (e) {
                // Почему-то на экране на нашлась таблица с именем _lightModule - ничего не делаем
                return;
            }
            if (ids.length === 0) {
                Light.toastError("Select rows with checkboxes");
                $(this).val("");
                return;
            }

            //Проверим, если была попытка отправить запрос на массовое удаление, нужно переспросить пользователя
            if (action === "delete" && !confirm("Delete selected records?")) {
                select.value = "";
                return;
            }

            const url = `?a=${_lightModule}&p=massaction&action=${action}`;

            // Узнаем, следует ли открывать новое окно при сабмите?
            if (
                $(select)
                    .find(":selected")
                    .hasClass("massaction-target-blank")
            ) {
                // Нужно открывать в новом окне, т.к. в выбранном option есть специальный css class
                // Чтобы открыть в новом окне и при этом сделать POST запрос - создадим временную форму и засабмитем её!
                $("<form></form>")
                    .attr({action: url, method: "post", target: "_blank"})
                    .append(`<input type="text" name="CheckedIds" value="${ids.join(",")}">`)
                    .append(`<input type="hidden" name="csrf_token" value="${_lightCsrfToken}">`)
                    .appendTo("body")
                    .submit()
                    .remove();

                // Сбрасываем выбор в выпадающем списке
                this.value = "";
            } else {
                // У этого option не было пометки откывать в новом окне - просто делаем AJAX POST запрос
                // Заблокируем выпадающий список
                select.disabled = true;
                $.post(url, {CheckedIds: ids.join(",")}).always(() => {
                    select.disabled = false;
                    // Сбрасываем выбор в выпадающем списке
                    select.value = "";
                });
            }
        });

        $body.on("click", "a.mushowhide", function(this: HTMLAnchorElement, e) {
            e.preventDefault();
            const $link = $(this);

            // Текущее состояние хранится справа от ссылки в скрытом поле
            const $state = $link.next("input[type=hidden]");

            let newState: "showed" | "hidden";
            newState = $state.val() === "hidden" ? "showed" : "hidden";

            $state.val(newState);

            // Теперь найдём само поле в соседнем td и отобразим или скроем его
            $link
                .parent()
                .next("td")
                .find(".mufield")
                .toggleClass("display-none", newState === "hidden");
        });

        Light.listener.simple_combo("ctrl e", function() {
            if (state === "view") {
                document.location.href = `?a=${Light.getModule()}&editid=${objectId}&${getModuleSessionParams()}`;
            }
        });

        Light.listener.simple_combo("ctrl s", function() {
            if (state === "edit" || state === "addnew") {
                $("#edit-form")
                    .find(".btn-save")
                    .first()
                    .trigger("click");
            }
        });

        if (state === "edit" || state === "addnew") {
            editForm = Light.forms.get("edit-form");
            // Передаём фокус на элемент Name
            $("input[name=Name]").focus();
        } else if (state === "list") {
            initListListeners();
        }
    }
}
