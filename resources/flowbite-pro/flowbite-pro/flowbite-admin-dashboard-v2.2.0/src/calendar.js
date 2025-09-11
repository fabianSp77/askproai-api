// Work in progress

import { Calendar } from "@fullcalendar/core";
import interactionPlugin from "@fullcalendar/interaction";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import listPlugin from "@fullcalendar/list";

// helper functions

function getRandomDate(currentYear, currentMonth) {
  const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();
  const day = Math.floor(Math.random() * (daysInMonth - 1)) + 1; // Adjusted to prevent events spanning more than two days
  const monthString = currentMonth < 10 ? `0${currentMonth}` : `${currentMonth}`;
  const dayString = day < 10 ? `0${day}` : `${day}`;
  return `${currentYear}-${monthString}-${dayString}`;
}

function generateRandomEvents(numEvents) {
  const currentYear = new Date().getFullYear();
  const currentMonth = new Date().getMonth() + 1;
  const events = [];

  // Sample event titles, descriptions, and locations
  const programmingTitles = [
    "JavaScript Workshop",
    "Python Coding Challenge",
    "Web Dev Bootcamp",
    "Data Science Meetup",
    "Algorithms Study Group",
  ];
  const creativeDesignTitles = [
    "Graphic Design Workshop",
    "UI/UX Design Sprint",
    "Digital Art Showcase",
    "Creative Writing Seminar",
    "Photography Masterclass",
  ];
  const cities = [
    "New York",
    "Los Angeles",
    "San Francisco",
    "Chicago",
    "London",
    "Paris",
    "Tokyo",
    "Sydney",
    "Berlin",
    "Toronto",
  ];
  const programmingDescriptions = [
    "Learn the fundamentals of JavaScript programming in this hands-on workshop. Perfect for beginners!",
    "Put your Python skills to the test in our coding challenge. Solve real-world problems and compete for prizes.",
    "Join our intensive web development bootcamp and master HTML, CSS, and JavaScript in just one week!",
    "Explore the latest trends and technologies in data science. Network with professionals and expand your knowledge.",
    "Dive deep into algorithms and problem-solving techniques with our study group. Prepare for coding interviews and competitions.",
  ];
  const creativeDesignDescriptions = [
    "Unleash your creativity in our graphic design workshop. Learn design principles and create stunning visuals.",
    "Join our UI/UX design sprint and collaborate with experts to prototype and improve user experiences.",
    "Experience the world of digital art in our showcase. Discover new techniques and get inspired by talented artists.",
    "Hone your writing skills in our creative writing seminar. Explore different genres and receive constructive feedback.",
    "Capture breathtaking moments in our photography masterclass. Learn composition, lighting, and editing techniques from professionals.",
  ];

  // Define color classes
  const colors = ["indigo", "primary", "teal", "yellow", "pink"];

  for (let i = 1; i <= numEvents; i++) {
    const startDate = getRandomDate(currentYear, currentMonth);
    let endDate = getRandomDate(currentYear, currentMonth);

    // Ensure event spans no more than two days
    const startDay = parseInt(startDate.split("-")[2]);
    const endDay = parseInt(endDate.split("-")[2]);
    if (endDay - startDay > 1) {
      endDate = `${currentYear}-${currentMonth < 10 ? "0" + currentMonth : currentMonth}-${startDay + 1}`;
    }

    const id = i;
    const isProgrammingEvent = Math.random() < 0.5; // 50% chance for programming event
    const titles = isProgrammingEvent ? programmingTitles : creativeDesignTitles;
    const descriptions = isProgrammingEvent ? programmingDescriptions : creativeDesignDescriptions;
    const title = titles[Math.floor(Math.random() * titles.length)];
    const color = colors[Math.floor(Math.random() * colors.length)]; // Random color from the list
    const city = cities[Math.floor(Math.random() * cities.length)];
    const location = `${title} - ${city}`;
    const description = descriptions[Math.floor(Math.random() * descriptions.length)];

    events.push({
      id,
      title,
      start: startDate,
      end: endDate,
      className: `fc-event-${color}`,
      extendedProps: {
        location,
        description,
      },
    });
  }

  return events;
}

function formatDateWithMonthName(dateStr) {
  const months = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
  ];
  const [year, month, day] = dateStr.split("-");
  return `${months[parseInt(month) - 1]} ${parseInt(day)}, ${year}`;
}

const calendarEl = document.getElementById("calendar");

if (calendarEl) {
  window.addEventListener("load", function () {
    function handleTimeToggle(checkboxId, collapseId) {
      const toggleTimeInputs = FlowbiteInstances.getInstance("Collapse", collapseId);
      const checkbox = document.getElementById(checkboxId);

      if (checkbox) {
        checkbox.addEventListener("change", function () {
          checkbox.checked ? toggleTimeInputs.expand() : toggleTimeInputs.collapse();
        });
      }
    }

    handleTimeToggle("select-new-time-range-container", "new-time-range-container");
    handleTimeToggle("select-update-time-range-container", "update-time-range-container");
  });

  document.addEventListener("DOMContentLoaded", function () {
    const numEvents = 10; // You can adjust the number of events you want to generate
    const randomEvents = generateRandomEvents(numEvents);

    const calendar = new Calendar(calendarEl, {
      plugins: [interactionPlugin, dayGridPlugin, timeGridPlugin, listPlugin],
      initialView: "dayGridMonth",
      editable: true,
      events: randomEvents,
      selectable: true,
      customButtons: {
        addEventButton: {
          text: "",
        },
      },
      headerToolbar: {
        left: "prev,next title today",
        center: "",
        right: "dayGridMonth,timeGridWeek,timeGridDay,listWeek addEventButton",
      },
      datesSet: function () {
        const addEventButton = calendarEl.querySelector(".fc-addEventButton-button");
        addEventButton.innerHTML = `<svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" /></svg>New event`;
        addEventButton.setAttribute("data-drawer-target", "createEventDrawer");
        addEventButton.setAttribute("data-drawer-show", "createEventDrawer");
        addEventButton.setAttribute("aria-controls", "createEventDrawer");
        addEventButton.setAttribute("data-drawer-placement", "right");
        addEventButton.setAttribute("id", "createEventButton");
      },
      dateClick: function (info) {
        FlowbiteInstances.getInstance("Drawer", "createEventDrawer").show();
        document.getElementById("new-start-date").value = info.dateStr;
        document.getElementById("new-end-date").value = info.dateStr;
      },
      eventClick: function (info) {
        FlowbiteInstances.getInstance("Modal", "readEventModal").show();
        const $readEventModal = document.getElementById("readEventModal");
        $readEventModal.setAttribute("data-event-id", info.event.id);

        // Populate title, description, and location
        $readEventModal.querySelector("#readEventTitle").textContent = info.event.title;

        info.event.extendedProps.description
          ? ($readEventModal.querySelector("#readEventDescription").textContent = info.event.extendedProps.description)
          : null;

        info.event.extendedProps.location
          ? ($readEventModal.querySelector("#readEventLocation").textContent = info.event.extendedProps.location)
          : null;

        // Format and populate start and end date
        $readEventModal.querySelector("#readEventStartDate").textContent = formatDateWithMonthName(info.event.startStr);

        info.event.endStr
          ? ($readEventModal.querySelector("#readEventEndDate").textContent =
              " - " + formatDateWithMonthName(info.event.endStr))
          : null;

        // Handle time range if it's not an all-day event
        const $readEventTime = $readEventModal.querySelector("#readEventTime");

        if (!info.event.allDay && info.event.startStr.includes("T")) {
          // Extract and format the time range (HH:mm)
          const startTime = info.event.startStr.split("T")[1].slice(0, 5); // Get time in HH:mm format
          const endTime = info.event.endStr ? info.event.endStr.split("T")[1].slice(0, 5) : startTime;

          // Populate the time range
          $readEventTime.textContent = `${startTime} - ${endTime}`;
        } else {
          // Clear the time range text if it's an all-day event
          $readEventTime.textContent = "All day";
        }
      },
    });
    calendar.render();

    const $newEventForm = document.getElementById("newEventForm");

    $newEventForm.addEventListener("submit", (event) => {
      event.preventDefault();
      const data = new FormData($newEventForm);
      const useTime = document.getElementById("select-new-time-range-container").checked;

      // Fetch the start and end dates (format: YYYY-MM-DD)
      let startDate = data.get("new-start-date");
      let endDate = data.get("new-end-date");

      // By default, keep dates in YYYY-MM-DD format
      let start = startDate;
      let end = endDate;

      // If the checkbox is checked, append time in HH:mm to dates
      if (useTime) {
        const startTime = data.get("new-start-time"); // format: HH:mm
        const endTime = data.get("new-end-time"); // format: HH:mm

        // Only append time if it exists, otherwise stick to the date
        if (startTime) {
          start = `${startDate}T${startTime}`; // Convert to YYYY-MM-DDTHH:mm
        }
        if (endTime) {
          end = `${endDate}T${endTime}`; // Convert to YYYY-MM-DDTHH:mm
        }
      }

      // Create the new event with start and end in the correct format
      const newEvent = {
        title: data.get("new-title"),
        start: start, // FullCalendar will accept either just the date or date+time
        end: end, // FullCalendar will accept either just the date or date+time
        classNames: [`fc-event-${data.get("new-color")}`],
        extendedProps: {
          location: data.get("new-location"),
          description: data.get("new-description"),
        },
      };

      // Add the event to FullCalendar
      calendar.addEvent(newEvent);

      // Reset the form after submission
      $newEventForm.reset();

      // Hide the drawer (assuming Flowbite is handling the drawer instance)
      FlowbiteInstances.getInstance("Drawer", "createEventDrawer").hide();

      FlowbiteInstances.getInstance("Collapse", "new-time-range-container").collapse();
    });

    const $updateEventForm = document.getElementById("updateEventForm");

    $updateEventForm.addEventListener("submit", (e) => {
      e.preventDefault();
      const data = new FormData($updateEventForm);
      const event = calendar.getEventById(document.getElementById("readEventModal").getAttribute("data-event-id"));

      // Fetch the start and end dates (format: YYYY-MM-DD)
      let startDate = data.get("update-start-date");
      let endDate = data.get("update-end-date");

      // By default, keep dates in YYYY-MM-DD format
      let start = startDate;
      let end = endDate;

      const useTime = document.getElementById("select-update-time-range-container").checked;

      // If the checkbox is checked, append time in HH:mm to dates
      if (useTime) {
        const startTime = data.get("update-start-time"); // format: HH:mm
        const endTime = data.get("update-end-time"); // format: HH:mm

        // Only append time if it exists, otherwise stick to the date
        if (startTime) {
          start = `${startDate}T${startTime}`; // Convert to YYYY-MM-DDTHH:mm
        }
        if (endTime) {
          end = `${endDate}T${endTime}`; // Convert to YYYY-MM-DDTHH:mm
        }
      }

      // Update the event title, dates, and extended properties
      event.setProp("title", data.get("update-title"));
      event.setStart(start);
      event.setEnd(end);
      event.setAllDay(!useTime);
      event.setExtendedProp("location", data.get("update-location"));
      event.setExtendedProp("description", data.get("update-description"));
      event.setProp("classNames", [`fc-event-${data.get("update-color")}`]);

      // Hide the update drawer after submission
      FlowbiteInstances.getInstance("Drawer", "updateEventDrawer").hide();
    });

    const deleteEventButton = document.getElementById("deleteEventButton");
    deleteEventButton.addEventListener("click", () => {
      FlowbiteInstances.getInstance("Modal", "readEventModal").hide();

      const confirmDeleteEventButton = document.getElementById("confirmEventDeleteButton");
      confirmDeleteEventButton.addEventListener("click", () => {
        const event = calendar.getEventById(readEventModal.getAttribute("data-event-id"));
        event.remove();
        FlowbiteInstances.getInstance("Modal", "deleteEventModal").hide();
      });
    });

    const updateEventDrawerButton = document.getElementById("updateEventDrawerButton");

    updateEventDrawerButton.addEventListener("click", () => {
      const event = calendar.getEventById(readEventModal.getAttribute("data-event-id"));
      const useTimeCheckbox = document.getElementById("select-update-time-range-container");
      const toggleTimeInputs = FlowbiteInstances.getInstance("Collapse", "update-time-range-container");

      // Populate date inputs
      const startDate = event.startStr.split("T")[0]; // Extract date portion
      const endDate = event.endStr ? event.endStr.split("T")[0] : startDate; // Extract date portion

      document.getElementById("update-title").value = event.title;
      document.getElementById("update-start-date").value = startDate;
      document.getElementById("update-end-date").value = endDate;
      document.getElementById("update-location").value = event.extendedProps.location;
      document.getElementById("update-description").value = event.extendedProps.description;

      // Check if the event has time and it's not an all-day event
      if (!event.allDay && event.startStr.includes("T")) {
        // Extract time from start and end (format: HH:mm)
        const startTime = event.startStr.split("T")[1].slice(0, 5); // HH:mm format
        const endTime = event.endStr ? event.endStr.split("T")[1].slice(0, 5) : startTime;

        // Populate the time inputs
        document.getElementById("update-start-time").value = startTime;
        document.getElementById("update-end-time").value = endTime;

        // Check the "add time" checkbox and expand the time inputs
        useTimeCheckbox.checked = true;
        toggleTimeInputs.expand();
      } else {
        // If no time is set, collapse the time inputs and uncheck the checkbox
        useTimeCheckbox.checked = false;
        toggleTimeInputs.collapse();
      }

      // Show the update drawer
      FlowbiteInstances.getInstance("Drawer", "updateEventDrawer").show();
    });

    const colorPickerTargets = document.querySelectorAll("[data-color-picker-target]");

    // Iterate through each element and add event listener
    colorPickerTargets.forEach(function (target) {
      target.addEventListener("click", function () {
        // Get the target ID element
        const targetId = this.getAttribute("data-color-picker-target");
        const targetElement = document.getElementById(targetId);

        // Get the value from data-color-picker-value attribute
        const colorValue = this.getAttribute("data-color-picker-value");

        // Remove 'data-color-selected' from all buttons in the group
        const groupButtons = document.querySelectorAll(`[data-color-picker-target="${targetId}"]`);
        groupButtons.forEach(function (button) {
          button.removeAttribute("data-color-selected");
        });

        // Set 'data-color-selected' for the clicked button
        this.setAttribute("data-color-selected", "true");

        // Set the value of the target element
        if (targetElement) {
          targetElement.value = colorValue;
        } else {
          console.error("Target element not found with ID:", targetId);
        }
      });
    });
  });
}
