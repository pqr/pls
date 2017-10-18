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

        function initListListeners() {
            try {
                const listTable = Light.tables.get(_lightModule);
                const $divNode = listTable.getDivNode();

                $divNode.on("click", "span.table-excel a", function(this: HTMLAnchorElement, e) {
                    const ids = listTable.getSelectedValues();
                    if (ids.length !== 0) {
                        e.preventDefault();
                        document.location.href = this.href + "&excelIds=" + ids.join(",");
                    }
                });

                function updateExcelSelectedCountSpan() {
                    const $a = $divNode.find("span.table-excel a");
                    const ids = listTable.getSelectedValues();
                    let $selectedCountSpan = $a.find(".table-excel-selected-count-span");
                    if (ids.length === 0 && $selectedCountSpan.length > 0) {
                        $selectedCountSpan.remove();
                    } else if (ids.length > 0) {
                        if ($selectedCountSpan.length === 0) {
                            $selectedCountSpan = $('<span class="table-excel-selected-count-span"></span>');
                            $selectedCountSpan = $selectedCountSpan.appendTo($a);
                        }
                        $selectedCountSpan.html("&nbsp;(" + ids.length + ")");
                    }
                }

                /**
                 * Фиксирует div в котором находятся выпадающий список с massaction во время прокрутки таблицы>
                 */
                function fixMassactionDiv() {
                    const $listMassActionIndicator = $(".listmassactionIndicator");
                    const $listMassAction = $(".listmassaction");
                    const $selectedCount = $listMassAction.find(".selected-count");
                    const $selectedCountColumns = $listMassAction.find(".selected-count-column");

                    const countSelected = listTable.getSelectedValues().length;
                    const nodeSelectedCountValue = $selectedCount.find("strong");
                    if (countSelected > 0) {
                        nodeSelectedCountValue.text(countSelected);
                        if (!$listMassActionIndicator.is(":appeared")) {
                            $listMassAction.addClass("listmassaction-fixed");
                        }
                        $selectedCount.removeClass("display-none");
                        if ($selectedCountColumns.length) {
                            const columns: { [key: number]: string } = {};
                            const columKeys = $selectedCountColumns.data("keys").split("|");
                            const $thead = listTable.getTableNode().find("thead");
                            columKeys.forEach(function(key: string) {
                                const $th = $thead.find("th[data-fieldkey='" + key + "']");
                                if ($th.length) {
                                    columns[$thead.find("th").index($th)] = $th
                                        .html()
                                        .replace(/<br>/g, " ")
                                        .replace(/<\/?[^>]+>/gi, "");
                                }
                            });
                            const $tr = listTable.getSelectedRows();
                            let sums: Array<number> = [];
                            $tr.each(function(_i, elem) {
                                const $tr = $(elem);
                                const $td = $tr.find("td");
                                let columnIndex: any;
                                for (columnIndex in columns) {
                                    let value: any = $td.eq(columnIndex).text();
                                    try {
                                        value = parseFloat(value);
                                        if (!sums[columnIndex]) {
                                            sums[columnIndex] = 0;
                                        }
                                        sums[columnIndex] += value;
                                    } catch (e) {}
                                }
                            });
                            let html: string = "";
                            for (let columnIndex in columns) {
                                html +=
                                    "<span class='selected-count-column-span'><strong>" +
                                    columns[columnIndex] +
                                    "</strong>: " +
                                    getBeautyMoney(sums[columnIndex]) +
                                    "</span>";
                            }
                            $selectedCountColumns.html(html);
                        }
                    } else {
                        $listMassAction.removeClass("listmassaction-fixed");
                        $selectedCount.addClass("display-none");
                        if ($selectedCountColumns.length) {
                            $selectedCountColumns.html("");
                        }
                    }
                }

                function initListMassactionAppear() {
                    const $listMassActionIndicator = $(".listmassactionIndicator");
                    const $listMassAction = $(".listmassaction");
                    // const $selectedCount = $listMassAction.find(".selected-count");
                    $listMassActionIndicator.appear();

                    $listMassActionIndicator.on("appear", () => {
                        // this element is now inside browser viewport
                        $listMassAction.removeClass("listmassaction-fixed");
                        // $selectedCount.addClass("display-none")
                    });

                    $listMassActionIndicator.on("disappear", () => {
                        // this element is now outside browser viewport
                        fixMassactionDiv();
                    });
                }

                listTable.onReload(initListMassactionAppear);

                listTable.onSelectRow(updateExcelSelectedCountSpan);
                listTable.onSelectAll(updateExcelSelectedCountSpan);

                listTable.onSelectRow(fixMassactionDiv);
                listTable.onSelectAll(fixMassactionDiv);

                initListMassactionAppear();
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
                    dialogBodyHtml = `<ol class="save-errors">${errors.map(({msg}) => `<li>${msg}</li>`).join("")}</ol>`;
                } else {
                    dialogBodyHtml = errors[0].msg;
                }

                // Проверим, были ли среди ошибок критчные? (хотя бы одна)
                hasCriticalError = errors.some(({ critical }) => critical !== undefined && critical);
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

            const $progress = $editForm.find(".progress");
            $progress.removeClass("visibility-hidden");

            let postData = $editForm.serialize();
            let url = $editForm.attr("action");
            if (saveanyway) {
                url += "&saveanyway=1";
            }

            if (additionalData) {
                //Если в этот метод были принудительно переданы какие-то данные, то добавим их в POST запрос
                //Это могут быть данные из диалога подтверждающего сохранение (если такой был при налаичии не критичных ошибок)
                postData += "&" + $.param(additionalData);
            }

            $.post(url, postData)
                .done((_, __, jqXHR) => {
                    //В заголовках ответа должен прийти Location куда перенаправить пользователя после сохранения
                    if (!jqXHR.getResponseHeader("x-light-redirect")) {
                        //Если же нет этого заголовка для перенаправления, то разблокируем кнопки Save и выведем диалог с ошибками
                        blockSaveButtons(false);
                        //Если в пришедшем ajax ответе от сервера есть Header заголовок указывающий на то, что ответ содержит в себе
                        //какое-то диалоговое окно, то в этом методе ничего делать не будем. Диалог отрисуется средствами Light.js
                        if (!jqXHR.getResponseHeader("x-light-showdialog")) {
                            //Нет url для редиректа, значит какая-то ошибка в sql или ешё где - выведем текст ошибки
                            showErrorDialog(jqXHR.responseText);
                        }
                    }
                })
                .fail(jqXHR => {
                    blockSaveButtons(false);
                    $progress.addClass("visibility-hidden");

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

            //Соберём значения чекбоксов в таблице и отправим их ajax POST запросом
            const ids = Light.tables.get(_lightModule).getSelectedValues();
            if (ids.length === 0) {
                Light.toastError("Select rows with checkboxes");
                $(this).val("");
                return;
            }

            //Проверим, если была попытка отправить запрос на массовое удаление, нужно переспросить пользователя
            if (action === "delete") {
                if (!confirm("Delete selected records?")) {
                    select.value = "";
                    return;
                }
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
                    .attr({ action: url, method: "post", target: "_blank" })
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
                $.post(url, { CheckedIds: ids.join(",") }).always(() => {
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
