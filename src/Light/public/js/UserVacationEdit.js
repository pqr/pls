(function () {

    Light.module.onFieldChange('VacationType', function (newValue) {
        if (newValue == 2) {
            // Выбрана удалённая работа
            Light.module.setFieldValue('AvailableRemote', 1);
        }
    })

})();
