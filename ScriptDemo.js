// =====================================
// NOTIFICATION
// =====================================

let closeNotification = document.getElementById("closeNotification");

closeNotification.addEventListener("click", function(){

    document.getElementById("notification").style.display = "none";

});


// =====================================
// HAMBURGER MENU
// =====================================

let menuButton = document.getElementById("menuButton");

menuButton.addEventListener("click", function(){

    let navigation = document.getElementById("navigation");

    navigation.classList.toggle("show");

});


// =====================================
// OPEN LOGIN MODAL
// =====================================

let modalButton = document.getElementById("modalButton");

modalButton.addEventListener("click", function(){

    document.getElementById("modalBox").style.display = "flex";

});


// =====================================
// OK BUTTON → LOGIN PAGE
// =====================================

let loginButton = document.getElementById("loginButton");

loginButton.addEventListener("click", function(){

    window.location.href = "Login.html";

});


// =====================================
// CANCEL BUTTON
// =====================================

let closeModal = document.getElementById("closeModal");

closeModal.addEventListener("click", function(){

    document.getElementById("modalBox").style.display = "none";

});


// =====================================
// CLOSE MODAL BY CLICKING OUTSIDE
// =====================================

let modalBox = document.getElementById("modalBox");

modalBox.addEventListener("click", function(event){

    if(event.target === modalBox){

        modalBox.style.display = "none";

    }

});


// =====================================
// FAQ COLLAPSE
// =====================================

let questions = document.querySelectorAll(".faq-question");

questions.forEach(function(question){

    question.addEventListener("click", function(){

        let answer = question.nextElementSibling;

        answer.classList.toggle("show");

    });

});


// =====================================
// ANNOUNCEMENT CARDS
// =====================================

let cards = document.querySelectorAll("article");

cards.forEach(function(card){

    card.addEventListener("click", function(){

        card.classList.toggle("active");

    });

});


// =====================================
// LIGHT / DARK MODE
// =====================================

let themeButton = document.getElementById("themeButton");

themeButton.addEventListener("click", function(){

    document.body.classList.toggle("dark");


    if(document.body.classList.contains("dark")){

        themeButton.innerHTML = "☀️";

        themeButton.title = "Light Mode";

        localStorage.setItem("theme", "dark");

    }

    else{

        themeButton.innerHTML = "🌙";

        themeButton.title = "Dark Mode";

        localStorage.setItem("theme", "light");

    }

});


// =====================================
// LOAD SAVED THEME
// =====================================

let savedTheme = localStorage.getItem("theme");


if(savedTheme === "dark"){

    document.body.classList.add("dark");

    themeButton.innerHTML = "☀️";

    themeButton.title = "Light Mode";

}

else{

    themeButton.innerHTML = "🌙";

    themeButton.title = "Dark Mode";

}
