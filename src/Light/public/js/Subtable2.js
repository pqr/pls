/*global Light */
/*global console */

(function () {
    "use strict";

    /**
     * @param {string} name
     * @param {jQuery} $div
     * @param {Object} fields хеш-объект fieldKey -> fieldParams
     * @param {string} blockTemplate строковое представление шаблона блока - нужно для добавления новых
     * @constructor
     * @class Subtable
     */
    var Subtable2 = function (name, $div, fields, blockTemplate) {
        this._$div = $div;
        this._name = name;
        this._fields = fields;
        this._blockTemplate = blockTemplate;

        //Обычные массивы
        this._onAddHandlers = [];
        this._onDelHandlers = [];
        this._fieldNodesCache = {};

        var that = this;

        //Добавляем обработчик на ссылку добавления новой строки
        $div.find('.subtable2-add-link').on('click', function (e) {
            e.preventDefault();

            //Некоторые поля могли иметь флаг copyOnAdd - в этом случае необходимо скопировать значение из предыдущей
            //строки
            if (that._copyOnAddFieldKeys === undefined) {
                //Вычислим из набора полей, какие нужно копировать при добавлении строки?
                that._copyOnAddFieldKeys = [];
                _.forEach(that._fields, function (fieldParams, fieldKey) {
                    if (fieldParams.copyOnAdd) {
                        that._copyOnAddFieldKeys.push(fieldKey);
                    }
                });
            }

            var data;
            if (that._copyOnAddFieldKeys.length > 0) {
                var $lastBlock = that._$div.children(".subtable2-block").last();
                if ($lastBlock.length > 0) {
                    var lastRowNum = that.getRowNum($lastBlock);
                    //Нашли последнюю строку, вычленим данные по полям из массива that._copyOnAddFieldKeys
                    data = {};
                    that._copyOnAddFieldKeys.forEach(function (fieldKey) {
                            data[fieldKey] = that.getFieldValue(lastRowNum, fieldKey);

                            if (that._fields[fieldKey].type === "MoneyWithCurrency") {
                                //Если это поле MoneyWithCurrency, то будем копировать не всё значение, а только валюту
                                data[fieldKey] = data[fieldKey].substring(0, 3) + ' 0';
                            }
                        }
                    );
                }
            }

            //Вызываем публичный метод addRow
            that.addRow(data);
        });

        //Добавим обработчик на ссылки удаления строк
        $div.on('click', '.subtable2-del-link', function (e) {
            e.preventDefault();
            //Определим номер удаляемой строки
            var deleteRowNum = that.getRowNum($(this));
            that.delRow(deleteRowNum);
        });

        this._updateCountSpan();
    };

    Subtable2.prototype._getIdsNode = function () {
        return $("#" + this._name + "-subtable2-ids");
    };

    Subtable2.prototype._getDelrowsNode = function () {
        return $("#" + this._name + "-subtable2-delrows");
    };


    /**
     * Пользователь может указать в вёрстке специальный тег (или несколько) с классом <subtableName>-count-span и тогда
     * при каждом добавлении или удалении строки в этот span будет записываться текущее число строк
     * @private
     */
    Subtable2.prototype._updateCountSpan = function () {
        var $countSpan = $("." + this._name + '-subtable-count-span');
        if ($countSpan.length > 0) {
            $countSpan.html(this.getDataCount());
        }
    };

    Subtable2.prototype.getMaxRowNum = function () {
        var idsValue = this._getIdsNode().val();
        if (idsValue === "") {
            //Таблица была пуста, нет номера максимальной строки
            return -1;
        }

        return idsValue.split(",").length - 1;
    };

    /**
     * Возвращает число строк в подтаблице
     * @return {int}
     */
    Subtable2.prototype.getDataCount = function () {
        //Посчитаем число id строк записанных в скротом элементе ids и вычтем число строк записанных в delrows
        var idsValue = this._getIdsNode().val();
        if (idsValue === "") {
            //Таблица пуста
            return 0;
        }

        var rowsCount = idsValue.split(",").length;

        var $delrowsNode = this._getDelrowsNode();
        if ($delrowsNode.length > 0) {
            var delrowsValue = $delrowsNode.val();
            if (delrowsValue !== "") {
                rowsCount -= delrowsValue.split(",").length
            }
        }

        return rowsCount;
    };

    /**
     * Возвращает массив из объектов с данными
     * @return {[]}
     */
    Subtable2.prototype.getData = function () {
        var data = [];
        var that = this;
        this.eachRow(function (rowNum) {
            var rowData = that.getRowData(rowNum);
            data.push(rowData);
        });

        return data;
    };

    /**
     * Возвращает объект с данными из указанной строки
     * @param rowNum
     * @returns {{}}
     */
    Subtable2.prototype.getRowData = function (rowNum) {
        var rowData = {};
        var that = this;
        //Перебираем поля и вынимаем из массива ячейки одна за одной
        $.each(this._fields, function (fieldKey) {
            rowData[fieldKey] = that.getFieldValue(rowNum, fieldKey);
        });

        return rowData;
    };

    Subtable2.prototype.eachRow = function (callback) {

        //Перебираем все строки до максимального номера
        //При этом если порядковый номер строки есть среди delrows - значит она удалена и её использовать не нужно
        var $row;
        var maxRowNum = this.getMaxRowNum();
        var $delrowsNode = this._getDelrowsNode();
        var delrows = $delrowsNode.length > 0 ? $delrowsNode.val().split(",") : [];
        for (var rowNum = 0; rowNum <= maxRowNum; rowNum++) {
            if (delrows.indexOf(rowNum.toString()) !== -1) {
                //Текущая строка есть среди удалённых
                continue;
            }

            $row = $("#" + this._name + "-subtable-block-" + rowNum);
            var result = callback.call(this, rowNum);
            //Если пользовательская функция вернула false - прерываем цикл
            if (result === false) {
                break;
            }
        }

    };

    Subtable2.prototype.addRow = function (data) {
        var that = this;
        //Номер следующей строки определим исходя из содержимого скрытого элемента, в котором пепречислены id всех строк
        var newRowNum = 0;
        var idsNode = this._getIdsNode();
        var idsValue = idsNode.val();
        if (idsValue) {
            //Таблица была не пуста
            var ids = idsValue.split(',');
            newRowNum = ids.length;

            //Обязательно добавляем в конец скрытого поля ноль = ID элемента в базе (ведь элемент новый и его ещё в базе не существует)
            idsNode.val(idsValue + ',0');
        } else {
            //Таблица была пуста
            //Обязательно добавляем в конец скрытого поля ноль = ID элемента в базе (ведь элемент новый и его ещё в базе не существует)
            idsNode.val('0');
        }

        //Создадим новую строку
        var newBlockHtml = this._blockTemplate.replace(/\{NewBlockNum\}/g, newRowNum);
        var $newBlock = $(newBlockHtml);
        $newBlock.css({display: "none"});
        $newBlock.insertBefore(idsNode.get(0));
        $newBlock.slideDown();

        //Если передан объект data, нужно заполнить поля ввода переданными данными
        if (data) {
            that.setRowData(newRowNum, data)
        }

        Light.addStandardListeners($newBlock);

        this._updateCountSpan();

        //Вызовем все обработчики подписанные на событие добавление строки
        for (var i = 0; i < this._onAddHandlers.length; ++i) {
            this._onAddHandlers[i].call(this, newRowNum, $newBlock);
        }

        return {newRowNum: newRowNum, newBlockNode: $newBlock};
    };

    Subtable2.prototype.setRowData = function (rowNum, data) {
        var that = this;
        _.forEach(this._fields, function (fieldParams, fieldKey) {

            if (data[fieldKey] !== undefined) {

                //Проверим, что для данного поля существует DOM элемент. Заодно $newFieldNode пригодится для проверки это select или нет
                var $newFieldNode = that.getFieldNode(rowNum, fieldKey);
                if ($newFieldNode.length > 0) {

                    var newValue = data[fieldKey];

                    if (that._fields[fieldKey].type === "MoneyWithCurrency") {

                        //Для поля MoneyWithCurrency нужно дополнительно обработать newValue
                        //Например, при вставке из диалогового окна paste пользователь может указать чисто сумму без валюты
                        if (newValue.indexOf(' ') === -1) {
                            //Нет пробела, значит это чисто сумма - добавим вперёд валюту по умолчанию
                            newValue = 'USD ' + newValue;
                        }

                    } else {

                        //Сделаем исключение для поля типа Select
                        // нужно сделать поиск по всем элементам option на предмет совпадения текса
                        if ($newFieldNode.prop('nodeName').toLowerCase() === 'select') {
                            //Прововерим наличие option с таким же текстом, как value
                            if (that._fields[fieldKey]._optionsHash === undefined) {
                                that._fields[fieldKey]._optionsHash = {};
                                $newFieldNode.find('option').each(function (index, optionEl) {
                                    that._fields[fieldKey]._optionsHash[optionEl.innerHTML] = optionEl.value;
                                });
                            }

                            if (that._fields[fieldKey]._optionsHash[newValue] !== undefined) {
                                newValue = that._fields[fieldKey]._optionsHash[newValue];
                            }
                        }
                    }

                    that.setFieldValue(rowNum, fieldKey, newValue);
                }
            }

        });
    };

    Subtable2.prototype.delRow = function (rowNum) {
        //Найдём строку по номеру
        var $blockNode = this.getBlockNode(rowNum);
        if ($blockNode.length > 0) {
            //Физически удалим строку из таблицы (из DOM дерева)
            $blockNode.slideUp(400, function() {
                $blockNode.remove();
            });

            //Добавим номер удалённой строки в скрытое поле, чтобы потом обработать на стороне php
            var delrowsInput = this._getDelrowsNode().get(0);
            if (delrowsInput.value === "") {
                delrowsInput.value = rowNum;
            } else {
                delrowsInput.value += "," + rowNum;
            }

            //Вызовем все обработчики подписанные на событие удаление строки
            for (var i = 0; i < this._onDelHandlers.length; ++i) {
                this._onDelHandlers[i].call(this, rowNum);
            }

            this._updateCountSpan();

            return true;
        }

        return false;
    };

    Subtable2.prototype.getBlockNode = function (rowNum) {
        return $("#" + this._name + "-subtable-block-" + rowNum);
    };

    Subtable2.prototype.getRowNum = function ($node) {
        return parseInt($node.closest(".subtable2-block").attr("rowNum"));
    };

    /**
     * Если это поле в режиме view, то метод возвразает td ячейку
     * Если же поле в режиме редактирования, то возвращается input/select элемент
     * @param {int} rowNum
     * @param {string} fieldKey
     * @return {jQuery|Object}
     */
    Subtable2.prototype.getFieldNode = function (rowNum, fieldKey) {

        var cacheKey = rowNum + '-' + fieldKey;
        if (!this._fieldNodesCache[cacheKey]) {
            var $blockNode = this.getBlockNode(rowNum);
            var fieldName = this._name + 'Subtable2[' + rowNum + '][' + fieldKey + ']';
            var fieldType = this._fields[fieldKey].type;
            this._fieldNodesCache[cacheKey] = Light.getFieldNode($blockNode, fieldName, fieldType);
        }
        return this._fieldNodesCache[cacheKey];
    };

    /**
     * @param {int} rowNum
     * @param {string} fieldKey
     * @return {*}
     */
    Subtable2.prototype.getFieldValue = function (rowNum, fieldKey) {
        return Light.getNodeValue(this.getFieldNode(rowNum, fieldKey), this._fields[fieldKey].type);
    };

    /**
     * @this Subtable
     * @param {int} rowNum
     * @param {string} fieldKey
     * @param {*} newValue
     */
    Subtable2.prototype.setFieldValue = function (rowNum, fieldKey, newValue) {
        if (this._fields[fieldKey] === undefined) {
            return;
        }

        var $node = this.getFieldNode(rowNum, fieldKey);
        Light.setNodeValue($node, newValue, this._fields[fieldKey].type);
    };

    /**
     * @return {string}
     */
    Subtable2.prototype.getName = function () {
        return this._name;
    };


    /**
     * @param handler function(newRowNum, $newRow) this = subtable
     */
    Subtable2.prototype.onAdd = function (handler) {
        this._onAddHandlers.push(handler);
    };

    /**
     * @param handler function(rowNum) this = subtable
     */
    Subtable2.prototype.onDel = function (handler) {
        this._onDelHandlers.push(handler);
    };

    var subtables2Store = {};

    Light.subtables2 = {
        get: function (name) {
            return subtables2Store[name];
        },

        getByNode: function ($node) {
            return subtables2Store[$node.closest("div.subtable2-div").attr('data-subtablename')];
        },

        init: function ($root) {
            $root.find('div.subtable2-div').each(function () {
                //Проверим, есть ли в этом div атрибут data-fields с описанием полей? Если есть, создадим объект Subtable
                var $div = $(this);
                //Проверим, что эта таблица находится в режиме редактирования по атрибуту data-fields
                var fieldsJson = $div.attr("data-fields");
                if (fieldsJson) {
                    var fields = JSON.parse(fieldsJson);
                    var name = $div.attr("data-subtablename");
                    var blockTemplate = $div.attr("data-blocktemplate");
                    subtables2Store[name] = new Subtable2(name, $div, fields, blockTemplate);
                }
            });
        }
    };

    //Сразу по ходу загрузки страницы инициализируем все подтаблицы, какие только найдём
    Light.subtables2.init($(document.body));

})();
