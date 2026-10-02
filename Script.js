// ===============================
// DARK / LIGHT MODE SWITCHER
// ===============================

let themeButton = document.createElement("button");

themeButton.innerHTML = "🌙 Dark Mode";

themeButton.id = "themeButton";


let nav = document.querySelector("nav");

nav.insertBefore(themeButton, nav.firstChild);



themeButton.addEventListener("click", function(){

    document.body.classList.toggle("dark");


    if(document.body.classList.contains("dark")){

        localStorage.setItem("theme","dark");

        themeButton.innerHTML="☀ Light Mode";

    }
    else{

        localStorage.setItem("theme","light");

        themeButton.innerHTML="🌙 Dark Mode";

    }

});



// Restore saved theme

if(localStorage.getItem("theme")=="dark"){

    document.body.classList.add("dark");

    themeButton.innerHTML="☀ Light Mode";

}




// ===============================
// CARD CLICK INTERACTION
// ===============================

let cards = document.querySelectorAll("article");


cards.forEach(function(card){

    card.addEventListener("click",function(){

        card.classList.toggle("active");

    });

});





// ===============================
// NOTIFICATION BANNER
// ===============================


let notification = document.createElement("div");


notification.id="notification";


notification.innerHTML=`

Welcome to Student Hub! Latest updates available.

<button id="closeNotification">
X
</button>

`;



document.body.prepend(notification);



document.getElementById("closeNotification")
.addEventListener("click",function(){

    notification.style.display="none";

});

// Modal Popup

let loginBtn = document.getElementById("loginBtn");
let modalBox = document.getElementById("modalBox");
let closeModal = document.getElementById("closeModal");


// Open Modal

loginBtn.addEventListener("click", function(){

    modalBox.style.display = "flex";

});


// Close Modal

closeModal.addEventListener("click", function(){

    modalBox.style.display = "none";

});