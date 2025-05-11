document.addEventListener('DOMContentLoaded', function() {
  // Wait for the DOM to be fully loaded
  initializeCalendar();
});

/**
 * Initialize the FullCalendar instance
 */
function initializeCalendar() {
  // Get the calendar element
  const calendarEl = document.getElementById('calendar');

  // Exit if calendar element doesn't exist
  if (!calendarEl) {
    console.error('Calendar element not found');
    return;
  }

  // Initialize FullCalendar
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,listMonth'
    },
    events: recurringEvents,
    eventClassNames: function(arg) {
      // Add class based on event type
      return ['fc-event', `${arg.event.extendedProps.type}-event`];
    },
    eventClick: function(info) {
      //redirect to event page
      if (info.event.extendedProps.type === 'gardening') {
        window.location.href = `../../pages/gardening_join/index.php?id=${info.event.extendedProps.id}`;
        return;
      } else if (info.event.extendedProps.type === 'recycling') {
        window.location.href = `../../pages/recycling_info/index.php?id=${info.event.extendedProps.id}`;
        return;
      }
    },
    eventTimeFormat: {
      hour: '2-digit',
      minute: '2-digit',
      meridiem: 'short'
    },
    firstDay: 1, // Start week on Monday
    height: 'auto',
    themeSystem: 'standard'
  });

  // Render the calendar
  calendar.render();
}