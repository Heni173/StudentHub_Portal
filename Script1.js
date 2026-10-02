// ==========================
// DARK / LIGHT MODE
// ==========================

let themeButton = document.getElementById("themeButton");

if(themeButton){

    themeButton.addEventListener("click", function(){

        document.body.classList.toggle("dark");

        if(document.body.classList.contains("dark")){

            localStorage.setItem("theme","dark");
            themeButton.innerHTML = "☀ Light Mode";

        }
        else{

            localStorage.setItem("theme","light");
            themeButton.innerHTML = "🌙 Dark Mode";

        }

    });

}


// Remember Theme

if(localStorage.getItem("theme") === "dark"){

    document.body.classList.add("dark");

    if(themeButton){
        themeButton.innerHTML = "☀ Light Mode";
    }

}



// ==========================
// LOGIN MODAL POPUP
// ==========================

let loginBtn = document.getElementById("loginBtn");
let modalBox = document.getElementById("modalBox");
let closeModal = document.getElementById("closeModal");


if(loginBtn && modalBox){

    loginBtn.addEventListener("click", function(){

        modalBox.style.display = "flex";

    });

}


if(closeModal && modalBox){

    closeModal.addEventListener("click", function(){

        modalBox.style.display = "none";

    });

}



// ==========================
// NOTIFICATION CLOSE
// ==========================

let closeNotification = document.getElementById("closeNotification");
let notification = document.getElementById("notification");


if(closeNotification && notification){

    closeNotification.addEventListener("click", function(){

        notification.style.display = "none";

    });

}



// ==========================
// FAQ COLLAPSE
// ==========================

let faqQuestion = document.querySelectorAll(".faq-question");


faqQuestion.forEach(function(question){

    question.addEventListener("click", function(){

        let answer = this.nextElementSibling;

        answer.classList.toggle("show");

    });

});



// ==========================
// SIMPLE IMAGE SLIDER
// ==========================

let sliderImage = document.getElementById("sliderImage");
let nextBtn = document.getElementById("nextBtn");
let prevBtn = document.getElementById("prevBtn");


let images = [
    "event1.jpg",
    "event2.jpg",
    "event3.jpg"
];


let index = 0;


if(sliderImage){

    sliderImage.src = images[index];

}


if(nextBtn){

    nextBtn.addEventListener("click",function(){

        index++;

        if(index >= images.length){
            index = 0;
        }

        sliderImage.src = images[index];

    });

}


if(prevBtn){

    prevBtn.addEventListener("click",function(){

        index--;

        if(index < 0){
            index = images.length-1;
        }

        sliderImage.src = images[index];

    });

}