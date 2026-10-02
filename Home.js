let faqs = [];
let currentPage = 1;
let itemsPerPage = 5;


/* Notification */

const notification = document.getElementById("notification");
const closeNotification = document.getElementById("closeNotification");

if (closeNotification) {

    closeNotification.addEventListener("click", function() {

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


    themeButton.addEventListener("click", function() {

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

    menuButton.addEventListener("click", function() {

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


    slides.forEach(function(slide) {

        slide.classList.remove("active");

    });


    dots.forEach(function(dot) {

        dot.classList.remove("active");

    });


    slides[currentSlide].classList.add("active");


    if (dots[currentSlide]) {

        dots[currentSlide].classList.add("active");

    }

}


if (nextButton) {

    nextButton.addEventListener("click", function() {

        showSlide(currentSlide + 1);

    });

}


if (prevButton) {

    prevButton.addEventListener("click", function() {

        showSlide(currentSlide - 1);

    });

}


dots.forEach(function(dot, index) {

    dot.addEventListener("click", function() {

        showSlide(index);

    });

});


/* Login Modal */

const modalButton = document.getElementById("modalButton");
const modalBox = document.getElementById("modalBox");
const closeModal = document.getElementById("closeModal");
const loginButton = document.getElementById("loginButton");


if (modalButton && modalBox) {

    modalButton.addEventListener("click", function() {

        modalBox.style.display = "flex";

    });

}


if (closeModal && modalBox) {

    closeModal.addEventListener("click", function() {

        modalBox.style.display = "none";

    });

}


if (loginButton) {

    loginButton.addEventListener("click", function() {

        window.location.href = "Login.html";

    });

}


if (modalBox) {

    modalBox.addEventListener("click", function(event) {

        if (event.target === modalBox) {

            modalBox.style.display = "none";

        }

    });

}


/* FAQ */

const faqContainer = document.getElementById("faqContainer");
const faqSearch = document.getElementById("faqSearch");
const faqSort = document.getElementById("faqSort");
const faqPagination = document.getElementById("faqPagination");


async function loadFAQs() {
    try {

        faqContainer.innerHTML = "<p style='text-align:center;'>Loading FAQs...</p>";
        const response = await fetch("./faqs.json");
        
        if (!response.ok) {
            throw new Error("faqs.json file not found");
        }

        const data = await response.json();

        if (!Array.isArray(data)) {
            throw new Error("Invalid JSON format");
        }

        faqs = data;
        currentPage = 1;

        displayFAQs();

        }
    catch (error) {
        console.log("FAQ Error:", error);
        faqContainer.innerHTML = "<p style='text-align:center; color:red;'>Unable to load FAQs.</p>";
        faqPagination.innerHTML = "";
    }
}

function displayFAQs() {

    let searchText =faqSearch.value.trim().toLowerCase();

    let filteredFAQs = faqs.filter(function(faq) {

        let question =
            String(faq.question || "").toLowerCase();

        let answer =
            String(faq.answer || "").toLowerCase();

        if (searchText === "") {
            return true;
        }
        return question.includes(searchText) || answer.includes(searchText);
    });

    if (faqSort.value === "az") {

        filteredFAQs.sort(function(a, b) {
            return String(a.question).localeCompare(String(b.question));
        });

    }
    else {
        filteredFAQs.sort(function(a, b) {
            return String(b.question).localeCompare(String(a.question));
        });
    }

    let totalPages = Math.ceil(filteredFAQs.length / itemsPerPage);

    if (currentPage > totalPages && totalPages > 0) {
        currentPage = totalPages;
    }

    let start = (currentPage - 1) * itemsPerPage;
    let end =  start + itemsPerPage;
    let pageFAQs = filteredFAQs.slice(start, end);

    faqContainer.innerHTML = "";

    if (pageFAQs.length === 0) {
        faqContainer.innerHTML ="<p style='text-align:center;'>No FAQs found.</p>";
        faqPagination.innerHTML = "";
        return;
    }

    pageFAQs.forEach(function(faq) {

        let faqBox = document.createElement("div");
        faqBox.className = "faq";
        let question = document.createElement("button");
        question.className = "faq-question";
        question.type = "button";
        question.textContent =faq.question;
        let answer = document.createElement("div");
        answer.className = "faq-answer";
        answer.textContent = faq.answer;

        question.addEventListener("click", function() {

            let allAnswers = document.querySelectorAll(".faq-answer");
            
            allAnswers.forEach(function(otherAnswer) {

                if (otherAnswer !== answer) {
                    otherAnswer.classList.remove("show");
                }
            });
            answer.classList.toggle("show");
        });
        faqBox.appendChild(question);
        faqBox.appendChild(answer);
        faqContainer.appendChild(faqBox);
    });

    createPagination(totalPages);
}


function createPagination(totalPages) {

    faqPagination.innerHTML = "";

    if (totalPages <= 1) {
        return;
    }

    for (let i = 1; i <= totalPages; i++) {

        let button = document.createElement("button");
        button.type = "button";
        button.textContent = i;
        button.className = "page-button";

        if (i === currentPage) {
            button.classList.add("active");
        }

        button.addEventListener("click", function() {
            currentPage = i;
            displayFAQs();
        });

        faqPagination.appendChild(button);

    }
}

if (faqSearch) {

    faqSearch.addEventListener("input", function() {
        currentPage = 1;
        displayFAQs();
    });
}

if (faqSort) {

    faqSort.addEventListener("change", function() {
        currentPage = 1;
        displayFAQs();
    });
}

loadFAQs();