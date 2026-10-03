// NOTIFICATION

let closeNotification = document.getElementById("closeNotification");
let notification = document.getElementById("notification");

closeNotification.addEventListener("click", function () {
    notification.style.display = "none";
});


// HAMBURGER MENU

let menuButton = document.getElementById("menuButton");
let navigation = document.getElementById("navigation");

menuButton.addEventListener("click", function () {
    navigation.classList.toggle("show");
});


// DARK / LIGHT MODE

let themeButton = document.getElementById("themeButton");
let savedTheme = localStorage.getItem("theme");

if (savedTheme === "dark") {
    document.body.classList.add("dark");
    themeButton.innerHTML = "☀️";
} else {
    document.body.classList.remove("dark");
    themeButton.innerHTML = "🌙";
}

themeButton.addEventListener("click", function () {

    document.body.classList.toggle("dark");

    if (document.body.classList.contains("dark")) {
        localStorage.setItem("theme", "dark");
        themeButton.innerHTML = "☀️";
    } else {
        localStorage.setItem("theme", "light");
        themeButton.innerHTML = "🌙";
    }

});


// FETCH EVENTS FROM JSON

let eventsBody = document.getElementById("eventsBody");
let searchInput = document.getElementById("searchInput");
let dateFilter = document.getElementById("dateFilter");
let sortFilter = document.getElementById("sortFilter");
let noResults = document.getElementById("noResults");
let loading = document.getElementById("loading");
let pagination = document.getElementById("pagination");

let allEvents = [];


// PAGINATION VARIABLES

let currentPage = 1;
let eventsPerPage = 6;


// FETCH API

fetch("events.json")
    .then(function (response) {

        if (!response.ok) {
            throw new Error("Failed to load events.json");
        }

        return response.json();

    })
    .then(function (data) {

        allEvents = data;

        loading.style.display = "none";

        displayEvents(allEvents);

    })
    .catch(function (error) {

        console.error("Error:", error);

        loading.style.display = "none";

        eventsBody.innerHTML = "";

        let errorCard = document.createElement("div");

        errorCard.className = "event-card";

        errorCard.innerHTML =
            '<div class="event-icon">⚠️</div>' +
            '<h3>Unable to Load Events</h3>' +
            '<p class="event-description">' +
            'Please check your events.json file and try again.' +
            '</p>';

        eventsBody.appendChild(errorCard);

        pagination.innerHTML = "";

    });


// DISPLAY EVENTS

function displayEvents(events) {

    eventsBody.innerHTML = "";

    if (events.length === 0) {

        noResults.style.display = "block";

        pagination.innerHTML = "";

        return;

    }

    noResults.style.display = "none";


    // PAGINATION CALCULATION

    let totalPages = Math.ceil(events.length / eventsPerPage);

    if (currentPage > totalPages) {
        currentPage = totalPages;
    }

    let startIndex = (currentPage - 1) * eventsPerPage;

    let endIndex = startIndex + eventsPerPage;

    let paginatedEvents = events.slice(startIndex, endIndex);


    // DISPLAY CURRENT PAGE EVENTS

    paginatedEvents.forEach(function (event, index) {

        let card = document.createElement("div");

        card.className = "event-card";


        let icon = "🎓";

        if (event.icon) {
            icon = event.icon;
        }


        let description = "Join us for this exciting college event.";

        if (event.description) {
            description = event.description;
        } else if (event.details) {
            description = event.details;
        }


        let location = "CHARUSAT Campus";

        if (event.location) {
            location = event.location;
        }


        let number = document.createElement("div");

        number.className = "event-number";

        number.innerHTML = startIndex + index + 1;


        let iconBox = document.createElement("div");

        iconBox.className = "event-icon";

        iconBox.innerHTML = icon;


        let title = document.createElement("h3");

        title.innerHTML = event.name;


        let details = document.createElement("p");

        details.className = "event-description";

        details.innerHTML = description;


        let date = document.createElement("div");

        date.className = "event-date";

        date.innerHTML = "📅 " + event.date;


        let locationBox = document.createElement("div");

        locationBox.className = "event-location";

        locationBox.innerHTML = "📍 " + location;


        card.appendChild(number);

        card.appendChild(iconBox);

        card.appendChild(title);

        card.appendChild(details);

        card.appendChild(date);

        card.appendChild(locationBox);


        eventsBody.appendChild(card);

    });


    // CREATE PAGINATION

    createPagination(totalPages);

}


// SEARCH EVENTS

searchInput.addEventListener("input", function () {

    currentPage = 1;

    filterEvents();

});


// FILTER EVENTS BY DATE

dateFilter.addEventListener("change", function () {

    currentPage = 1;

    filterEvents();

});


// SORT EVENTS

sortFilter.addEventListener("change", function () {

    currentPage = 1;

    filterEvents();

});


// SEARCH + DATE FILTER + SORT

function filterEvents() {

    let searchText = searchInput.value.toLowerCase().trim();

    let selectedDate = dateFilter.value;

    let selectedSort = sortFilter.value;


    let filteredEvents = allEvents.filter(function (event) {

        let eventName = event.name.toLowerCase();

        let matchesSearch = eventName.includes(searchText);

        let matchesDate = true;


        if (selectedDate !== "") {

            matchesDate = event.date === selectedDate;

        }


        return matchesSearch && matchesDate;

    });


    // SORTING

    if (selectedSort === "nameAsc") {

        filteredEvents.sort(function (a, b) {

            return a.name.localeCompare(b.name);

        });

    }


    if (selectedSort === "nameDesc") {

        filteredEvents.sort(function (a, b) {

            return b.name.localeCompare(a.name);

        });

    }


    if (selectedSort === "dateAsc") {

        filteredEvents.sort(function (a, b) {

            return new Date(a.date) - new Date(b.date);

        });

    }


    if (selectedSort === "dateDesc") {

        filteredEvents.sort(function (a, b) {

            return new Date(b.date) - new Date(a.date);

        });

    }


    displayEvents(filteredEvents);

}


// PAGINATION

function createPagination(totalPages) {

    pagination.innerHTML = "";


    if (totalPages <= 1) {

        return;

    }


    // PREVIOUS BUTTON

    let previousButton = document.createElement("button");

    previousButton.className = "page-button";

    previousButton.innerHTML = "←";

    previousButton.disabled = currentPage === 1;


    previousButton.addEventListener("click", function () {

        if (currentPage > 1) {

            currentPage--;

            filterEvents();

        }

    });


    pagination.appendChild(previousButton);


    // PAGE NUMBER BUTTONS

    for (let i = 1; i <= totalPages; i++) {

        let pageButton = document.createElement("button");

        pageButton.className = "page-button";

        pageButton.innerHTML = i;


        if (i === currentPage) {

            pageButton.classList.add("active");

        }


        pageButton.addEventListener("click", function () {

            currentPage = i;

            filterEvents();

        });


        pagination.appendChild(pageButton);

    }


    // NEXT BUTTON

    let nextButton = document.createElement("button");

    nextButton.className = "page-button";

    nextButton.innerHTML = "→";

    nextButton.disabled = currentPage === totalPages;


    nextButton.addEventListener("click", function () {

        if (currentPage < totalPages) {

            currentPage++;

            filterEvents();

        }

    });


    pagination.appendChild(nextButton);

}

