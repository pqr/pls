$(function () {
    "use strict";

    var domContentLoadedEventStart = window.performance && window.performance.timing ? window.performance.timing.domContentLoadedEventStart : 0;
    var fetchStart = window.performance && window.performance.timing ? window.performance.timing.fetchStart : 0;
    var domContentLoadedEventEnd = window.performance && window.performance.timing ? window.performance.timing.domContentLoadedEventEnd : 0;

    setTimeout(function() {
        var data = {
            module: Light.getModule(),
            href: window.location.href,
            useragent: window.navigator.userAgent,
            width: document.documentElement.clientWidth,
            height: document.documentElement.clientHeight,
            serverWorkingTime: Math.floor(__lightmetrics['working time'] * 1000),
            domContentLoadedEventStart: domContentLoadedEventStart - fetchStart,
            domContentLoadedEventDuration: domContentLoadedEventEnd - domContentLoadedEventStart
        };

        $.post('index.php?a=Core&ps=jslog', data);
    }, 1000);

});