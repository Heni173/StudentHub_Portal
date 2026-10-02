// ==========================================
// NOTIFICATION
// ==========================================

let closeNotification = document.getElementById("closeNotification");
let notification = document.getElementById("notification");

closeNotification.addEventListener("click", function () {
    notification.style.display = "none";
});


// ==========================================
// HAMBURGER MENU
// ==========================================

let menuButton = document.getElementById("menuButton");
let navigation = document.getElementById("navigation");

menuButton.addEventListener("click", function () {
    navigation.classList.toggle("show");
});


// ==========================================
// DARK / LIGHT MODE
// LOCAL STORAGE
// ==========================================

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