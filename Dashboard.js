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


// SUBMISSION DATA

let allSubmissions = [];

let submissionBody = document.getElementById("submissionBody");

let submissionSearch = document.getElementById("submissionSearch");

let statusFilter = document.getElementById("statusFilter");

let sortFilter = document.getElementById("sortFilter");

let noSubmission = document.getElementById("noSubmission");

let loadingSubmissions = document.getElementById("loadingSubmissions");

let pagination = document.getElementById("pagination");


// PAGINATION VARIABLES

let currentPage = 1;

let submissionsPerPage = 5;


// FETCH JSON DATA

fetch("students.json")

    .then(function (response) {

        if (!response.ok) {

            throw new Error("Failed to load students.json");

        }

        return response.json();

    })

    .then(function (data) {

        allSubmissions = data;

        loadingSubmissions.style.display = "none";

        displaySubmissions(allSubmissions);

    })

    .catch(function (error) {

        console.error("Error:", error);

        loadingSubmissions.style.display = "none";

        submissionBody.innerHTML = `
            <tr>
                <td colspan="4">
                    ❌ Error loading submission data.
                </td>
            </tr>
        `;

        pagination.innerHTML = "";

    });


// DISPLAY SUBMISSIONS

function displaySubmissions(submissions) {

    submissionBody.innerHTML = "";

    if (submissions.length === 0) {

        noSubmission.style.display = "block";

        pagination.innerHTML = "";

        return;

    }

    noSubmission.style.display = "none";


    // PAGINATION CALCULATION

    let totalPages = Math.ceil(submissions.length / submissionsPerPage);

    if (currentPage > totalPages) {

        currentPage = totalPages;

    }


    let startIndex = (currentPage - 1) * submissionsPerPage;

    let endIndex = startIndex + submissionsPerPage;

    let paginatedSubmissions = submissions.slice(startIndex, endIndex);


    // DISPLAY CURRENT PAGE

    paginatedSubmissions.forEach(function (submission) {

        let row = document.createElement("tr");

        let statusClass = "";

        let statusIcon = "";


        if (submission.status.includes("Graded")) {

            statusClass = "graded";

            statusIcon = "✓";

        } else if (submission.status === "Submitted") {

            statusClass = "submitted";

            statusIcon = "✓";

        } else if (submission.status === "Under Review") {

            statusClass = "review";

            statusIcon = "⏳";

        }


        row.innerHTML = `
            <td data-label="Subject">
                ${submission.subject}
            </td>

            <td data-label="Assignment">
                ${submission.assignmentTitle}
            </td>

            <td data-label="Date">
                ${submission.submissionDate}
            </td>

            <td data-label="Status">
                <span class="status ${statusClass}">
                    ${statusIcon} ${submission.status}
                </span>
            </td>
        `;

        submissionBody.appendChild(row);

    });


    // CREATE PAGINATION

    createPagination(totalPages);

}


// SEARCH AND FILTER

function filterSubmissions() {

    let searchText = submissionSearch.value.toLowerCase().trim();

    let selectedStatus = statusFilter.value;

    let selectedSort = sortFilter.value;


    let filteredSubmissions = allSubmissions.filter(function (submission) {

        let subject = submission.subject.toLowerCase();

        let assignment = submission.assignmentTitle.toLowerCase();


        let matchesSearch =
            subject.includes(searchText) ||
            assignment.includes(searchText);


        let status = "";


        if (submission.status.includes("Graded")) {

            status = "graded";

        } else if (submission.status === "Submitted") {

            status = "submitted";

        } else if (submission.status === "Under Review") {

            status = "review";

        }


        let matchesStatus =
            selectedStatus === "all" ||
            status === selectedStatus;


        return matchesSearch && matchesStatus;

    });


    // SORTING

    if (selectedSort === "subjectAsc") {

        filteredSubmissions.sort(function (a, b) {

            return a.subject.localeCompare(b.subject);

        });

    }


    if (selectedSort === "subjectDesc") {

        filteredSubmissions.sort(function (a, b) {

            return b.subject.localeCompare(a.subject);

        });

    }


    if (selectedSort === "dateAsc") {

        filteredSubmissions.sort(function (a, b) {

            return new Date(a.submissionDate) - new Date(b.submissionDate);

        });

    }


    if (selectedSort === "dateDesc") {

        filteredSubmissions.sort(function (a, b) {

            return new Date(b.submissionDate) - new Date(a.submissionDate);

        });

    }


    displaySubmissions(filteredSubmissions);

}


// SEARCH EVENT

submissionSearch.addEventListener("input", function () {

    currentPage = 1;

    filterSubmissions();

});


// STATUS FILTER EVENT

statusFilter.addEventListener("change", function () {

    currentPage = 1;

    filterSubmissions();

});


// SORT EVENT

sortFilter.addEventListener("change", function () {

    currentPage = 1;

    filterSubmissions();

});


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

            filterSubmissions();

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

            filterSubmissions();

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

            filterSubmissions();

        }

    });


    pagination.appendChild(nextButton);

}
//await new Promise(r => setTimeout(r, 3000));  // 3 સેકન્ડ રાહ જુઓ