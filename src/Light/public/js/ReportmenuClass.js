(function() {
    "use strict";

    const urlParams = new URLSearchParams(location.search);
    const listTable = Light.table.get('Report' + urlParams.get('class'));
    listTable.initMassactionListeners();

})();