(function() {
    "use strict";

    // http://api.jquery.com/load/
    // Load data from the server and place the returned HTML into the matched element.
    var $homecontent = $("#homecontent");
    $homecontent.load("?a=Home&nouserrefreshtime=1", function() {
        Light.addStandardListeners($homecontent);
        Light.tables.init($homecontent)
    });
})();
