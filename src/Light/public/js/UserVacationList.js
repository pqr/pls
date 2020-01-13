$(function () {

    var calendarEl = document.querySelector(".CalendarDiv");

    var table = Light.tables.get('UserVacation');
    table.onReload(function() {
        var events = JSON.parse(table.getDivNode().find('.CalendarEvents').val());
        calendarEl.innerHTML = '';
        var calendar = new FullCalendar.Calendar(calendarEl, {
            plugins: ['dayGrid'],
            defaultView: 'dayGridMonth',
            locale: 'ru',
            firstDay: 1,
            events: events
        });
        calendar.render();
    });

});
