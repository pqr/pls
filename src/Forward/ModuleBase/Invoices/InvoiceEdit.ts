namespace Invoice {
    import Dialog = Light.Dialog;
    const linesSubtable = Light.subtables.get("Lines");

    $(
        '<a href="#" class="splitweight btn-flat waves-effect">Разбить сумму пропорционально платному весу из накладных</a>'
    )
        .appendTo("#Lines-subtable .subtable-add-p")
        .on("click", function(e) {
            e.preventDefault();
            if (linesSubtable.getFieldValues("FileNumber").some((fn: string) => fn !== "")) {
                alert(
                    "Введите одну или несколько услуг, которые нужно разбить по файлам, но не указывайте номера файлов и накладных.\nОставьте колонки MAWB и File Number пустыми."
                );
                return;
            }

            // Выведем диалог для вставки номеров файлов
            const dialogBody =
                'Введите номера файлов (File Number) в столбик по одному на строчку:<br><textarea style="width: 100%; height: 200px"></textarea>';
            new Light.Dialog(dialogBody, {
                buttonOkText: "Разбить по файлам",
                buttonOkHandler: function(dialog: Dialog) {
                    const $textarea = dialog.$div!.find("textarea");
                    $textarea.removeClass("input-error");
                    const fileNumbers = ($textarea.val() as string).trim();
                    if (fileNumbers === "") {
                        $textarea.addClass("input-error");
                        alert("Не указаны номера файлов в большом тексовом поле");
                        dialog.unblockButtons();
                        return;
                    }

                    // Если номера файлов были укзаны, передадим их на сервер вместе с суммами
                    const subtableData = linesSubtable.getData();
                    const url = "?a=" + Light.getModule() + "&p=SplitPerWeight";
                    const postData = {
                        fileNumbers: fileNumbers,
                        subtableData: subtableData
                    };
                    $.post(url, postData)
                        .done(newSubtableData => {
                            linesSubtable.empty();
                            linesSubtable.addRows(newSubtableData);

                            Light.toastSuccess("Суммы услуг распределены по накладным пропорционально платному весу");
                        })
                        .fail(() => {
                            Light.toastError("Не удалось получить информацию о весах");
                        })
                        .always(() => {
                            dialog.close();
                        });
                }
            }).open();
        });
}
