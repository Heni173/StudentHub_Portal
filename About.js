// ==========================================
// HAMBURGER MENU
// ==========================================

let menuButton =
    document.getElementById("menuButton");

let navigation =
    document.getElementById("navigation");


menuButton.addEventListener("click", function () {

    navigation.classList.toggle("show");

});


// ==========================================
// LIGHT / DARK MODE
// ==========================================

let themeButton =
    document.getElementById("themeButton");


// Get saved theme from localStorage

let savedTheme =
    localStorage.getItem("theme");


// Apply saved theme when page loads

if (savedTheme === "dark") {

    document.body.classList.add("dark");

    themeButton.innerHTML = "☀️";

}
else {

    document.body.classList.remove("dark");

    themeButton.innerHTML = "🌙";

}


// ==========================================
// CHANGE THEME
// ==========================================

themeButton.addEventListener("click", function () {

    // Toggle dark class

    document.body.classList.toggle("dark");


    // Check current theme

    if (document.body.classList.contains("dark")) {

        // Save dark mode

        localStorage.setItem("theme", "dark");

        // Change button icon

        themeButton.innerHTML = "☀️";

    }
    else {

        // Save light mode

        localStorage.setItem("theme", "light");

        // Change button icon

        themeButton.innerHTML = "🌙";

    }

});