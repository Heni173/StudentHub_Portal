let faqs = [];
let currentPage = 1;
let itemsPerPage = 5;


/* Notification */

const notification = document.getElementById("notification");
const closeNotification = document.getElementById("closeNotification");

if (closeNotification && notification) {
    closeNotification.addEventListener("click", function () {
        notification.style.display = "none";
    });
}


/* Theme */

const themeButton = document.getElementById("themeButton");

if (themeButton) {
    let savedTheme = localStorage.getItem("theme");

    if (savedTheme === "dark") {
        document.body.classList.add("dark");
        themeButton.textContent = "☀️";
    } else {
        document.body.classList.remove("dark");
        themeButton.textContent = "🌙";
    }

    themeButton.addEventListener("click", function () {
        document.body.classList.toggle("dark");

        if (document.body.classList.contains("dark")) {
            themeButton.textContent = "☀️";
            localStorage.setItem("theme", "dark");
        } else {
            themeButton.textContent = "🌙";
            localStorage.setItem("theme", "light");
        }
    });
}


/* Mobile Menu */

const menuButton = document.getElementById("menuButton");
const navigation = document.getElementById("navigation");

if (menuButton && navigation) {
    menuButton.addEventListener("click", function () {
        navigation.classList.toggle("show");
    });
}


/* Image Slider */

const slides = document.querySelectorAll(".slide");
const dots = document.querySelectorAll(".dot");
const prevButton = document.getElementById("prevButton");
const nextButton = document.getElementById("nextButton");

let currentSlide = 0;

function showSlide(index) {

    if (slides.length === 0) {
        return;
    }

    if (index >= slides.length) {
        currentSlide = 0;
    } else if (index < 0) {
        currentSlide = slides.length - 1;
    } else {
        currentSlide = index;
    }

    slides.forEach(function (slide) {
        slide.classList.remove("active");
    });

    dots.forEach(function (dot) {
        dot.classList.remove("active");
    });

    slides[currentSlide].classList.add("active");

    if (dots[currentSlide]) {
        dots[currentSlide].classList.add("active");
    }
}

if (nextButton) {
    nextButton.addEventListener("click", function () {
        showSlide(currentSlide + 1);
    });
}

if (prevButton) {
    prevButton.addEventListener("click", function () {
        showSlide(currentSlide - 1);
    });
}

dots.forEach(function (dot, index) {

    dot.addEventListener("click", function () {
        showSlide(index);
    });

    dot.addEventListener("keydown", function (event) {

        if (event.key === "Enter" || event.key === " ") {

            event.preventDefault();

            showSlide(index);
        }
    });
});


/* Login Modal */

const modalButton = document.getElementById("modalButton");
const modalBox = document.getElementById("modalBox");
const closeModal = document.getElementById("closeModal");
const loginButton = document.getElementById("loginButton");

if (modalButton && modalBox) {

    modalButton.addEventListener("click", function () {
        modalBox.style.display = "flex";
    });
}

if (closeModal && modalBox) {

    closeModal.addEventListener("click", function () {
        modalBox.style.display = "none";
    });
}

if (loginButton) {

    loginButton.addEventListener("click", function () {
        window.location.href = "Login.html";
    });
}

if (modalBox) {

    modalBox.addEventListener("click", function (event) {

        if (event.target === modalBox) {
            modalBox.style.display = "none";
        }

    });
}


/* FAQ: Fetch, Search, Category Filter, Sorting and Pagination */

const faqContainer = document.getElementById("faqContainer");
const faqSearch = document.getElementById("faqSearch");
const faqCategory = document.getElementById("faqCategory");
const faqSort = document.getElementById("faqSort");
const faqPagination = document.getElementById("faqPagination");


/* Get Category */

function getCategory(faq) {

    if (faq.category) {
        return String(faq.category);
    }

    const text =
        String(faq.question || "") +
        " " +
        String(faq.answer || "");

    const lowerText = text.toLowerCase();

    if (
        lowerText.includes("register") ||
        lowerText.includes("registration") ||
        lowerText.includes("password") ||
        lowerText.includes("account")
    ) {
        return "Registration";
    }

    if (
        lowerText.includes("dashboard") ||
        lowerText.includes("submission") ||
        lowerText.includes("assignment") ||
        lowerText.includes("attendance")
    ) {
        return "Dashboard";
    }

    if (
        lowerText.includes("event") ||
        lowerText.includes("program") ||
        lowerText.includes("activity")
    ) {
        return "Events";
    }

    if (
        lowerText.includes("profile") ||
        lowerText.includes("contact")
    ) {
        return "Profile";
    }

    if (
        lowerText.includes("login") ||
        lowerText.includes("portal")
    ) {
        return "Login";
    }

    return "General";
}


/* Load Categories */

function loadCategoryOptions() {

    if (!faqCategory) {
        return;
    }

    const categories = [];

    faqs.forEach(function (faq) {

        const category = getCategory(faq);

        if (!categories.includes(category)) {
            categories.push(category);
        }

    });

    categories.sort(function (a, b) {
        return a.localeCompare(b);
    });

    faqCategory.innerHTML = `
        <option value="all">
            🏷️ All Categories
        </option>
    `;

    categories.forEach(function (category) {

        const option = document.createElement("option");

        option.value = category;
        option.textContent = "🏷️ " + category;

        faqCategory.appendChild(option);

    });
}


/* Load FAQs */

async function loadFAQs() {

    if (!faqContainer) {
        return;
    }

    try {

        faqContainer.innerHTML =
            "<p style='text-align:center;'>⏳ Loading FAQs...</p>";

        const response = await fetch("./faqs.json");

        if (!response.ok) {
            throw new Error("faqs.json file not found");
        }

        const data = await response.json();

        if (!Array.isArray(data)) {
            throw new Error("Invalid JSON format");
        }

        faqs = data;

        loadCategoryOptions();

        currentPage = 1;

        displayFAQs();

    } catch (error) {

        console.log("FAQ Error:", error);

        faqContainer.innerHTML =
            "<p style='text-align:center; color:red;'>Unable to load FAQs. Please check faqs.json and run the page using Live Server.</p>";

        if (faqPagination) {
            faqPagination.innerHTML = "";
        }

    }

}


/* Display FAQs */

function displayFAQs() {

    if (
        !faqContainer ||
        !faqSearch ||
        !faqCategory ||
        !faqSort ||
        !faqPagination
    ) {
        return;
    }

    const searchText =
        faqSearch.value.trim().toLowerCase();

    const selectedCategory =
        faqCategory.value;


    /* Search + Category Filter */

    let filteredFAQs = faqs.filter(function (faq) {

        const question =
            String(faq.question || "").toLowerCase();

        const answer =
            String(faq.answer || "").toLowerCase();

        const category =
            getCategory(faq);


        const searchMatch =
            question.includes(searchText) ||
            answer.includes(searchText);


        const categoryMatch =
            selectedCategory === "all" ||
            category === selectedCategory;


        return (
            searchMatch &&
            categoryMatch
        );

    });


    /* Sorting */

    const sortOption = faqSort.value;

    filteredFAQs.sort(function (a, b) {

        let firstValue;
        let secondValue;

        if (
            sortOption === "answer-az" ||
            sortOption === "answer-za"
        ) {

            firstValue = String(a.answer || "");
            secondValue = String(b.answer || "");

        } else {

            firstValue = String(a.question || "");
            secondValue = String(b.question || "");

        }

        const comparison =
            firstValue.localeCompare(
                secondValue,
                undefined,
                {
                    sensitivity: "base"
                }
            );

        if (
            sortOption === "question-za" ||
            sortOption === "answer-za"
        ) {

            return -comparison;

        }

        return comparison;

    });


    /* Pagination */

    const totalPages =
        Math.ceil(
            filteredFAQs.length / itemsPerPage
        );

    if (totalPages === 0) {

        currentPage = 1;

    } else if (currentPage > totalPages) {

        currentPage = totalPages;

    }


    const start =
        (currentPage - 1) * itemsPerPage;

    const pageFAQs =
        filteredFAQs.slice(
            start,
            start + itemsPerPage
        );


    faqContainer.innerHTML = "";


    if (pageFAQs.length === 0) {

        faqContainer.innerHTML =
            "<p style='text-align:center;'>No FAQs found.</p>";

        faqPagination.innerHTML = "";

        return;

    }


    /* Create FAQ Cards */

    pageFAQs.forEach(function (faq) {

        const faqBox =
            document.createElement("div");

        faqBox.className = "faq";


        const question =
            document.createElement("button");

        question.className = "faq-question";

        question.type = "button";

        question.textContent =
            faq.question ||
            "No question available";

        question.setAttribute(
            "aria-expanded",
            "false"
        );


        const answer =
            document.createElement("div");

        answer.className = "faq-answer";

        answer.textContent =
            faq.answer ||
            "No answer available";


        question.addEventListener(
            "click",
            function () {

                const wasOpen =
                    answer.classList.contains("show");

                const allAnswers =
                    document.querySelectorAll(
                        ".faq-answer"
                    );

                const allQuestions =
                    document.querySelectorAll(
                        ".faq-question"
                    );


                allAnswers.forEach(
                    function (otherAnswer) {

                        otherAnswer.classList.remove(
                            "show"
                        );

                    }
                );


                allQuestions.forEach(
                    function (otherQuestion) {

                        otherQuestion.setAttribute(
                            "aria-expanded",
                            "false"
                        );

                    }
                );


                if (!wasOpen) {

                    answer.classList.add("show");

                    question.setAttribute(
                        "aria-expanded",
                        "true"
                    );

                }

            }
        );

        faqBox.appendChild(question);
        faqBox.appendChild(answer);
        faqContainer.appendChild(faqBox);
    });

    createPagination(totalPages);
}


/* Create Pagination */

function createPagination(totalPages) {

    if (!faqPagination) {
        return;
    }

    faqPagination.innerHTML = "";

    if (totalPages <= 1) {
        return;
    }


    for (
        let i = 1;
        i <= totalPages;
        i++
    ) {

        const button =
            document.createElement("button");

        button.type = "button";

        button.textContent = i;

        button.className = "page-button";

        button.setAttribute(
            "aria-label",
            "Go to FAQ page " + i
        );


        if (i === currentPage) {

            button.classList.add("active");

            button.setAttribute(
                "aria-current",
                "page"
            );

        }


        button.addEventListener(
            "click",
            function () {

                currentPage = i;

                displayFAQs();

            }
        );

    faqPagination.appendChild(button);

    }
}

/* Search Event */

if (faqSearch) {

    faqSearch.addEventListener(
        "input",
        function () {

            currentPage = 1;

            displayFAQs();

        }
    );

}


/* Category Filter Event */

if (faqCategory) {

    faqCategory.addEventListener(
        "change",
        function () {

            currentPage = 1;

            displayFAQs();

        }
    );

}


/* Sorting Event */

if (faqSort) {

    faqSort.addEventListener(
        "change",
        function () {

            currentPage = 1;

            displayFAQs();

        }
    );

}


/* Start FAQ */

loadFAQs();