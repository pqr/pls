var Forward = {

    onMAWBSelect: function (item) {
        //Преобразуем поле для ввода номера MAWB в объект jQuery
        var $mawbInput = $(this);
        //По этом объекту найдём подтаблицу в которой он находится
        var subtable = Light.subtables.getByNode($mawbInput);
        if (subtable.hasField('FileNumber')) {
            //Теперь можем использовать методы подтаблицы, чтобы заполнить поле с номером файла
            var rowNum = Light.subtables.Subtable.getRowNum($mawbInput);
            subtable.setFieldValue(rowNum, 'FileNumber', item.FileNumber);
        }
    },

    onFileNumberSelect: function (item) {
        //Преобразуем поле для ввода номера файла в объект jQuery
        var $fileNumberInput = $(this);
        //По этом объекту найдём подтаблицу в которой он находится
        var subtable = Light.subtables.getByNode($fileNumberInput);
        if (subtable.hasField('MAWBNumber')) {
            //Теперь можем использовать методы подтаблицы, чтобы заполнить поле с номером контейнера
            var rowNum = Light.subtables.Subtable.getRowNum($fileNumberInput);
            subtable.setFieldValue(rowNum, 'MAWBNumber', item.MAWBNumber);
        }
    },

    acMawbFileResultFormatter: function (item) {
        return '<span class="ac-mawb-filenumber">' + Light.htmlspecialchars(item.MAWBNumber) +
            '&nbsp;<span class="ac-filenumber">' + item.FileNumber +
            '</span><span class="ac-etd">ETD: ' + Light.getBeautyDate(item.ETD) + '</span></span>';
    },

    onLocationIATASelect: function (item) {
        var type = this.id.replace('IATA', ''); //Вернёт либо 'Departure' либо 'Arrival'
        //Найдём соответсвующее поле LocationName в той же форме
        $(this).closest("form").find("input[name=" + type + "LocationName]").val(item.LocationName);
    },

    onLocationNameSelect: function (item) {
        var type = this.id.replace('LocationName', ''); //Вернёт либо 'Departure' либо 'Arrival'
        //Найдём соответсвующее поле LocationName в той же форме
        $(this).closest("form").find("input[name=" + type + "IATA]").val(item.IATA);
    },

    acLocationIATANameFormatter: function (item) {
        return '<span class="ac-location-iata-name">' + Light.htmlspecialchars(item.IATA) + '<span class="ac-location-name">' +
            Light.htmlspecialchars(item.LocationName) + '</span></span>';
    },

    parseQuantity: function (num) {
        num = Light.parseFloat(num);
        var floor = Math.floor(num);
        var decimal = num - floor;
        if (decimal > 0) {
            if (decimal <= 0.5) {
                num = floor + 0.5;
            } else {
                num = floor + 1;
            }
        }

        return num;
    },

    addStandartListeners: function ($root) {
        //При изменении значения во вспомагательном поле AirlineAlphaCodeInput обновим текущий выбор в выпадающем списке
        $root.find("#AirlineAlphaCodeInput").on("keyup", function () {
            if (this.value.length === 2) {
                //#AirlineAlphaCode - это выпадающий список в котором колючами являются как раз коды авиалиний!
                $(this).next("#AirlineAlphaCode").val(this.value.toUpperCase());
            }
        });

        $root.find("#AirlineAlphaCode").on("keyup change", function () {
            var newVal = this.value == '%' ? '' : this.value;
            $(this).prev("#AirlineAlphaCodeInput").val(newVal);
        })
    }

};

Forward.addStandartListeners($("body"));