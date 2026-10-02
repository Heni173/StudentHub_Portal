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


// REGISTRATION FORM

document.getElementById("registrationForm").addEventListener("submit", function(event) {

    event.preventDefault();

    let fullName = document.getElementById("fullName");
    let enrollment = document.getElementById("enrollment");
    let email = document.getElementById("email");
    let phone = document.getElementById("phone");
    let password = document.getElementById("password");
    let confirmPassword = document.getElementById("confirmPassword");
    let department = document.getElementById("department");
    let course = document.getElementById("course");
    let year = document.getElementById("year");
    let terms = document.getElementById("terms");

    let isValid = true;


    document.querySelectorAll(".error-message").forEach(function(error) {

        error.remove();

    });


    document.querySelectorAll(".error-field").forEach(function(field) {

        field.classList.remove("error-field");

    });


    document.querySelectorAll(".success-message").forEach(function(message) {

        message.remove();

    });


    terms.classList.remove("terms-error");


    function showError(field, message) {

        field.classList.add("error-field");

        let error = document.createElement("div");

        error.className = "error-message";

        error.innerText = message;

        field.parentElement.after(error);

        isValid = false;

    }


    // FULL NAME VALIDATION

    let namePattern = /^[A-Za-z ]+$/;

    if (fullName.value.trim() === "") {

        showError(
            fullName,
            "Please enter your full name."
        );

    } else if (!namePattern.test(fullName.value.trim())) {

        showError(
            fullName,
            "Please enter only characters and space."
        );

    }


    // ENROLLMENT VALIDATION

    let enrollmentPattern = /^[A-Za-z0-9]+$/;

    if (enrollment.value.trim() === "") {

        showError(
            enrollment,
            "Please enter your enrollment number."
        );

    } else if (!enrollmentPattern.test(enrollment.value.trim())) {

        showError(
            enrollment,
            "Please enter only numbers and characters."
        );

    }


    // EMAIL VALIDATION

    let emailPattern = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;

    if (email.value.trim() === "") {

        showError(
            email,
            "Please enter your email id."
        );

    } else if (!emailPattern.test(email.value.trim())) {

        showError(
            email,
            "Please enter the valid email id."
        );

    }


    // PHONE VALIDATION

    let phonePattern = /^[6-9][0-9]{9}$/;

    if (phone.value.trim() === "") {

        showError(
            phone,
            "Please enter your phone number."
        );

    } else if (!phonePattern.test(phone.value.trim())) {

        showError(
            phone,
            "Invalid phone number. Please enter a valid phone number."
        );

    }


    // PASSWORD VALIDATION

    let passwordValue = password.value;

    if (passwordValue === "") {

        showError(
            password,
            "Please enter your password."
        );

    } else {

        let firstCapital = /^[A-Z]/;
        let minimumLength = passwordValue.length >= 8;
        let hasLowercase = /[a-z]/;
        let hasNumber = /[0-9]/;
        let hasSpecial = /[!@#$%^&*(),.?":{}|<>]/;


        if (!firstCapital.test(passwordValue)) {

            showError(
                password,
                "Password must start with a capital letter."
            );

        } else if (!minimumLength) {

            showError(
                password,
                "Password must contain at least 8 characters."
            );

        } else if (
            !hasLowercase.test(passwordValue) ||
            !hasNumber.test(passwordValue) ||
            !hasSpecial.test(passwordValue)
        ) {

            showError(
                password,
                "Password must be strong and contain lowercase, number and special character."
            );

        }

    }


    // CONFIRM PASSWORD

    if (confirmPassword.value === "") {

        showError(
            confirmPassword,
            "Please confirm your password."
        );

    } else if (password.value !== confirmPassword.value) {

        showError(
            confirmPassword,
            "Password and Confirm Password must be same."
        );

    }


    // GENDER VALIDATION

    let gender = document.querySelector(
        'input[name="gender"]:checked'
    );

    if (!gender) {

        let genderBox = document.querySelector(".gender-box");

        let error = document.createElement("div");

        error.className = "error-message";

        error.innerText = "Please select your gender.";

        genderBox.parentElement.after(error);

        isValid = false;

    }


    // DEPARTMENT VALIDATION

    if (department.value === "") {

        showError(
            department,
            "Please select your department."
        );

    }


    // COURSE VALIDATION

    if (course.value === "") {

        showError(
            course,
            "Please select your course."
        );

    }


    // YEAR VALIDATION

    if (year.value === "") {

        showError(
            year,
            "Please select your year."
        );

    }


    // TERMS AND CONDITIONS VALIDATION

    if (!terms.checked) {

        terms.classList.add("terms-error");

        let error = document.createElement("div");

        error.className = "error-message";

        error.innerText = "Please accept the Terms and Conditions.";

        terms.parentElement.after(error);

        isValid = false;

    }


    // SUCCESS MESSAGE

    if (isValid) {

        let success = document.createElement("div");

        success.className = "success-message";

        success.innerText =
            "Congratulations! Your registration is done successfully.";

        document
            .querySelector(".register-box")
            .appendChild(success);

    }

});


// PASSWORD STRENGTH

let passwordInput = document.getElementById("password");

let passwordStrength =
    document.getElementById("passwordStrength");


passwordInput.addEventListener("input", function() {

    let password = passwordInput.value;


    passwordInput.classList.remove(
        "password-weak",
        "password-medium",
        "password-strong"
    );


    passwordStrength.classList.remove(
        "weak",
        "medium",
        "strong"
    );


    if (password.length === 0) {

        passwordStrength.innerText = "";

        return;

    }


    let score = 0;


    if (password.length >= 8) {

        score++;

    }


    if (/[A-Z]/.test(password)) {

        score++;

    }


    if (/[a-z]/.test(password)) {

        score++;

    }


    if (/[0-9]/.test(password)) {

        score++;

    }


    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {

        score++;

    }


    if (score <= 2) {

        passwordStrength.innerText =
            "Password Strength: Weak";

        passwordStrength.className = "weak";

        passwordInput.classList.add("password-weak");

    } else if (score <= 3) {

        passwordStrength.innerText =
            "Password Strength: Medium";

        passwordStrength.className = "medium";

        passwordInput.classList.add("password-medium");

    } else {

        passwordStrength.innerText =
            "Password Strength: Strong";

        passwordStrength.className = "strong";

        passwordInput.classList.add("password-strong");

    }

});


// SHOW / HIDE PASSWORD

let passwordToggle =
    document.getElementById("passwordToggle");

let confirmPasswordToggle =
    document.getElementById("confirmPasswordToggle");


passwordToggle.addEventListener("click", function() {

    if (passwordInput.type === "password") {

        passwordInput.type = "text";

        passwordToggle.innerHTML = "🙈";

        passwordToggle.title = "Hide Password";

    } else {

        passwordInput.type = "password";

        passwordToggle.innerHTML = "👁️";

        passwordToggle.title = "Show Password";

    }

});


confirmPasswordToggle.addEventListener("click", function() {

    if (confirmPassword.type === "password") {

        confirmPassword.type = "text";

        confirmPasswordToggle.innerHTML = "🙈";

        confirmPasswordToggle.title = "Hide Password";

    } else {

        confirmPassword.type = "password";

        confirmPasswordToggle.innerHTML = "👁️";

        confirmPasswordToggle.title = "Show Password";

    }

});


// CLEAR / RESET BUTTON

document.getElementById("registrationForm").addEventListener("reset", function() {

    setTimeout(function() {

        document.querySelectorAll(".error-field").forEach(function(field) {

            field.classList.remove("error-field");

        });


        document.querySelectorAll(".error-message").forEach(function(error) {

            error.remove();

        });


        document.querySelectorAll(".success-message").forEach(function(message) {

            message.remove();

        });


        passwordInput.classList.remove(
            "password-weak",
            "password-medium",
            "password-strong"
        );


        passwordStrength.classList.remove(
            "weak",
            "medium",
            "strong"
        );


        passwordStrength.innerText = "";


        passwordInput.type = "password";

        confirmPassword.type = "password";


        passwordToggle.innerHTML = "👁️";

        confirmPasswordToggle.innerHTML = "👁️";


        passwordToggle.title = "Show Password";

        confirmPasswordToggle.title = "Show Password";


        terms.classList.remove("terms-error");

    }, 0);

});