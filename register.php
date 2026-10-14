<?php
session_start();

$status = $_GET["status"] ?? "";
$old = $_SESSION["old"] ?? [];
$errors = $_SESSION["errors"] ?? [];

unset($_SESSION["old"], $_SESSION["errors"]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Hub - Register</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-image: linear-gradient(
                rgba(248, 250, 252, 0.85),
                rgba(234, 244, 248, 0.85)
            ), url("background.jpg");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #2E2E2E;
            transition: 0.3s;
        }

        header {
            position: relative;
            background: linear-gradient(135deg, #002147, #4dc8e0);
            color: white;
            text-align: center;
            padding: 30px 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.18);
        }

        header img {
            width: 180px;
            height: 180px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid rgb(14, 1, 1);
            padding: 5px;
            background: white;
        }

        header h1 {
            margin: 15px 0;
            font-size: 36px;
        }

        header h2 {
            font-size: 25px;
        }

        #themeButton {
            position: absolute;
            top: 20px;
            right: 25px;
            width: 48px;
            height: 48px;
            border: none;
            border-radius: 50%;
            background: white;
            color: #002147;
            font-size: 21px;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.25);
            transition: 0.3s;
        }

        #themeButton:hover {
            transform: scale(1.1) rotate(10deg);
        }

        #menuButton {
            display: none;
            margin: 20px auto 0;
            border: none;
            background: white;
            color: #002147;
            padding: 10px 22px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        nav {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

        nav a {
            text-decoration: none;
            color: white;
            background: rgba(255, 255, 255, 0.15);
            padding: 10px 18px;
            border-radius: 25px;
            font-weight: 600;
            transition: 0.3s;
        }

        nav a:hover,
        nav a.active {
            background: white;
            color: #002147;
            transform: translateY(-2px);
        }

        main {
            width: 90%;
            margin: auto;
            padding: 40px 0;
        }

        .register-box {
            background: white;
            width: 650px;
            max-width: 95%;
            margin: auto;
            padding: 40px;
            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            transition: 0.3s;
        }

        .register-box h2 {
            text-align: center;
            color: #002147;
            margin-bottom: 15px;
            font-size: 30px;
        }

        .register-box p {
            text-align: center;
            margin-bottom: 30px;
            font-size: 22px;
        }

        .form-row {
            display: flex;
            align-items: center;
            margin-bottom: 18px;
            gap: 20px;
        }

        .form-row > label {
            width: 200px;
            min-width: 200px;
            font-size: 19px;
            white-space: nowrap;
        }

        .form-row input:not([type="radio"]),
        .form-row select {
            flex: 1;
            min-width: 0;
            padding: 14px;
            border-radius: 10px;
            border: 1px solid #ccc;
            font-size: 16px;
            transition: 0.3s;
        }

        .form-row input:focus,
        .form-row select:focus {
            outline: none;
            border-color: #005F73;
            box-shadow: 0 0 0 3px rgba(0, 95, 115, 0.12);
        }

        .form-icon {
            display: inline-block;
            width: 28px;
            font-size: 19px;
            margin-right: 5px;
        }

        .gender-box {
            display: flex;
            align-items: center;
            gap: 18px;
            font-size: 17px;
            flex: 1;
        }

        .gender-box label {
            width: auto;
            min-width: auto;
            font-size: 17px;
            display: flex;
            align-items: center;
            gap: 5px;
            margin: 0;
            white-space: nowrap;
        }

        .gender-box input[type="radio"] {
            width: auto;
            flex: none;
            margin: 0;
            padding: 0;
        }

        .password-container {
            flex: 1;
            min-width: 0;
        }

        .password-input-box {
            position: relative;
            width: 100%;
        }

        .password-input-box input {
            width: 100%;
            padding-right: 50px;
        }

        .password-input-box input::-ms-reveal,
        .password-input-box input::-ms-clear {
            display: none;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            font-size: 19px;
            cursor: pointer;
            padding: 4px;
            color: #002147;
            z-index: 2;
        }

        .password-toggle:hover {
            transform: translateY(-50%) scale(1.12);
        }

        body.dark .password-toggle {
            color: #4dc8e0;
        }

        #passwordStrength {
            font-size: 16px;
            font-weight: bold;
            margin-top: 6px;
        }

        .weak {
            color: red;
        }

        .medium {
            color: orange;
        }

        .strong {
            color: green;
        }

        .password-weak {
            border: 2px solid red !important;
            background: #fff0f0 !important;
        }

        .password-medium {
            border: 2px solid orange !important;
            background: #fff8e6 !important;
        }

        .password-strong {
            border: 2px solid green !important;
            background: #effff0 !important;
        }

        .terms-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-top: 5px;
            margin-bottom: 20px;
        }

        .terms-checkbox {
            width: 20px !important;
            height: 20px;
            flex: none !important;
            margin-top: 2px;
            cursor: pointer;
            accent-color: #002147;
        }

        .terms-label {
            width: auto !important;
            min-width: 0 !important;
            white-space: normal !important;
            font-size: 15px !important;
            line-height: 1.5;
            cursor: pointer;
        }

        .terms-label a {
            color: #005F73;
            font-weight: bold;
            text-decoration: none;
        }

        .terms-label a:hover {
            text-decoration: underline;
        }

        .terms-error {
            outline: 2px solid red;
            outline-offset: 3px;
            border-radius: 4px;
        }

        input[type="submit"],
        input[type="reset"] {
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            margin: 10px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        input[type="submit"] {
            background: #002147;
            color: white;
        }

        input[type="reset"] {
            background: #D90429;
            color: white;
        }

        input[type="submit"]:hover {
            background: #005F73;
            transform: scale(1.05);
        }

        input[type="reset"]:hover {
            background: #A4031F;
            transform: scale(1.05);
        }
        
        
.error-message {
    color: red;
    font-size: 13px;
    font-weight: 600;
    margin-top: 5px;
    margin-bottom: 0;
    line-height: 1.4;
    width: 100%;
    text-align: left;
}

.form-row:has(.error-message) {
    flex-wrap: wrap;
}

.form-row > .error-message {
    flex-basis: 100%;
    margin-left: 220px;
    margin-top: -12px;
}

.password-container .error-message {
    display: block;
    width: 100%;
    margin-top: 5px;
    margin-bottom: 0;
    text-align: left;
}

.error-field {
    border: 2px solid red !important;
    background: #fff5f5 !important;
}



        .success-message {
            color: #155724;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 15px 0 25px;
            padding: 15px;
            border-radius: 10px;
        }

        .server-message {
            color: #842029;
            background: #f8d7da;
            border: 1px solid #f5c2c7;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0;
            padding: 12px;
            border-radius: 8px;
        }

        .login-link {
            background: white;
            width: 650px;
            max-width: 95%;
            margin: 25px auto 0;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .login-link p {
            font-size: 20px;
            font-weight: 600;
        }

        .login-link a {
            color: #002147;
            font-weight: bold;
            font-size: 20px;
        }

        footer {
            background: linear-gradient(135deg, #002147, #4dc8e0);
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 40px;
        }

        footer p {
            margin: 5px 0;
        }

        body.dark {
            background: #101820;
            color: white;
        }

        body.dark .register-box,
        body.dark .login-link {
            background: #1d2a30;
            color: white;
        }

        body.dark .register-box h2 {
            color: #4dc8e0;
        }

        body.dark .register-box p {
            color: #d5e3e8;
        }

        body.dark .form-row input,
        body.dark .form-row select {
            background: #26363d;
            color: white;
            border-color: #526a73;
        }

        body.dark .form-row input::placeholder {
            color: #b8c7cc;
        }

        body.dark .login-link a,
        body.dark .terms-label a {
            color: #4dc8e0;
        }

        body.dark #themeButton {
            background: #26363d;
            color: white;
        }

        body.dark .terms-checkbox {
            accent-color: #4dc8e0;
        }

        @media (max-width: 700px) {
            #themeButton {
                top: 15px;
                right: 15px;
            }

            #menuButton {
                display: block;
                margin: 20px auto 0;
                border: none;
                background: white;
                color: #002147;
                padding: 10px 22px;
                border-radius: 25px;
                font-size: 16px;
                font-weight: bold;
                cursor: pointer;
            }

            #navigation {
                display: none;
                flex-direction: column;
                align-items: center;
            }

            #navigation.show {
                display: flex;
            }

            nav a {
                width: 220px;
                text-align: center;
            }

            header img {
                width: 140px;
                height: 140px;
            }

            header h1 {
                font-size: 30px;
            }

            header h2 {
                font-size: 22px;
            }

            .form-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .form-row > label {
                width: 100%;
                min-width: 0;
                white-space: normal;
            }

            .form-row input:not([type="radio"]),
            .form-row select {
                width: 100%;
            }

            .register-box,
            .login-link {
                width: 95%;
            }

            .error-message {
                margin-left: 0;
            }

            .password-container {
                width: 100%;
            }

            .gender-box {
                width: 100%;
                gap: 20px;
                justify-content: flex-start;
                margin-top: 3px;
            }

            .terms-row {
                width: 100%;
                gap: 10px;
            }

            .terms-label {
                width: auto !important;
                font-size: 14px !important;
            }
        }

        @media (max-width: 400px) {
            .register-box {
                padding: 25px 18px;
            }

            .register-box h2 {
                font-size: 25px;
            }

            .register-box p {
                font-size: 18px;
            }
        }
    </style>
</head>

<body>

    <header>
        <button id="themeButton" type="button" title="Change Theme">🌙</button>

        <img src="logo.jpeg" alt="Student Hub Logo">

        <h1>STUDENT HUB</h1>
        <h2>Registration</h2>

        <button id="menuButton" type="button">☰ Menu</button>

        <nav id="navigation">
            <a href="Home.html">Home</a>
            <a href="register.php" class="active">Register</a>
            <a href="Dashboard.html">Dashboard</a>
            <a href="Profile.html">Profile</a>
            <a href="Events.html">Events</a>
            <a href="About.html">About Us</a>
            <a href="ContactUs.html">Contact Us</a>
            <a href="Feedback.html">Feedback</a>
            <a href="Location.html">Location</a>
        </nav>
    </header>

    <main>
        <section class="register-box">
            <h2>🎓 Student Registration</h2>
            <p>Create your Student Hub account.</p>

            <?php if ($status === "success"): ?>
                <div class="success-message" role="status">
                    Congratulations! Your registration has been done successfully.
                </div>
            <?php elseif ($status === "error"): ?>
                <div class="server-message" role="alert">
                    Registration could not be completed. Please check the details and try again.
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="server-message" role="alert">
                    <ul style="margin-bottom:0;text-align:left">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form id="registrationForm" action="process_registration.php" method="POST">

                <div class="form-row">
                    <label for="fullName">
                        <span class="form-icon">👤</span><b>Full Name :</b>
                    </label>

                    <input
                        type="text"
                        id="fullName"
                        name="fullName"
                        placeholder="Enter Full Name"
                        value="<?php echo htmlspecialchars($old['fullName'] ?? ''); ?>"
                    >
                </div>

                <div class="form-row">
                    <label for="enrollment">
                        <span class="form-icon">🎓</span><b>Enrollment No. :</b>
                    </label>

                    <input
                        type="text"
                        id="enrollment"
                        name="enrollment"
                        placeholder="Enter Enrollment No."
                        value="<?php echo htmlspecialchars($old['enrollment'] ?? ''); ?>"
                    >
                </div>

                <div class="form-row">
                    <label for="username">
                        <span class="form-icon">🪪</span><b>Username :</b>
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Create Username"
                        value="<?php echo htmlspecialchars($old['username'] ?? ''); ?>"
                    >
                </div>

                <div class="form-row">
                    <label for="email">
                        <span class="form-icon">✉️</span><b>Email :</b>
                    </label>

                    <input
                        type="text"
                        id="email"
                        name="email"
                        placeholder="Enter Email"
                        value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>"
                    >
                </div>

                <div class="form-row">
                    <label for="phone">
                        <span class="form-icon">📱</span><b>Phone Number :</b>
                    </label>

                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        placeholder="Enter Phone Number"
                        value="<?php echo htmlspecialchars($old['phone'] ?? ''); ?>"
                    >
                </div>

                <div class="form-row">
                    <label for="password">
                        <span class="form-icon">🔒</span><b>Password :</b>
                    </label>

                    <div class="password-container">
                        <div class="password-input-box">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Create Password"
                                autocomplete="new-password"
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                id="passwordToggle"
                                title="Show Password"
                            >👁️</button>
                        </div>

                        <div id="passwordStrength"></div>
                    </div>
                </div>

                <div class="form-row">
                    <label for="confirmPassword">
                        <span class="form-icon">🔐</span><b>Confirm Password :</b>
                    </label>

                    <div class="password-container">
                        <div class="password-input-box">
                            <input
                                type="password"
                                id="confirmPassword"
                                name="confirmPassword"
                                placeholder="Confirm Password"
                                autocomplete="new-password"
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                id="confirmPasswordToggle"
                                title="Show Password"
                            >👁️</button>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <label>
                        <span class="form-icon">⚥</span><b>Gender :</b>
                    </label>

                    <div class="gender-box">
                        <label>
                            <input
                                type="radio"
                                name="gender"
                                value="Male"
                                <?php echo (($old['gender'] ?? '') === 'Male') ? 'checked' : ''; ?>
                            >
                            Male
                        </label>

                        <label>
                            <input
                                type="radio"
                                name="gender"
                                value="Female"
                                <?php echo (($old['gender'] ?? '') === 'Female') ? 'checked' : ''; ?>
                            >
                            Female
                        </label>
                    </div>
                </div>

                <div class="form-row">
                    <label for="course">
                        <span class="form-icon">📚</span><b>Course :</b>
                    </label>

                    <select id="course" name="course">
                        <option value="">Select your course</option>

                        <option
                            value="B.Tech/BE"
                            <?php echo (($old['course'] ?? '') === 'B.Tech/BE') ? 'selected' : ''; ?>
                        >B.Tech/BE</option>

                        <option
                            value="M.Tech/ME"
                            <?php echo (($old['course'] ?? '') === 'M.Tech/ME') ? 'selected' : ''; ?>
                        >M.Tech/ME</option>
                    </select>
                </div>

                <div class="form-row">
                    <label for="department">
                        <span class="form-icon">🏫</span><b>Department :</b>
                    </label>

                    <select id="department" name="department">
                        <option value="">Select</option>

                        <?php
                        $departments = [
                            "Computer Engineering",
                            "Information Technology",
                            "Mechanical Engineering",
                            "Civil Engineering",
                            "Electrical Engineering"
                        ];

                        foreach ($departments as $item):
                        ?>
                            <option
                                value="<?php echo htmlspecialchars($item); ?>"
                                <?php echo (($old['department'] ?? '') === $item) ? 'selected' : ''; ?>
                            >
                                <?php echo htmlspecialchars($item); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <label for="year">
                        <span class="form-icon">📅</span><b>Year :</b>
                    </label>

                    <select id="year" name="year">
                        <option value="">Select your year</option>

                        <?php foreach (["1st Year", "2nd Year", "3rd Year", "4th Year"] as $item): ?>
                            <option
                                value="<?php echo $item; ?>"
                                <?php echo (($old['year'] ?? '') === $item) ? 'selected' : ''; ?>
                            ><?php echo $item; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="terms-row">
                    <input
                        type="checkbox"
                        id="terms"
                        name="terms"
                        value="accepted"
                        class="terms-checkbox"
                    >

                    <label for="terms" class="terms-label">
                        I agree to the
                        <a href="#" onclick="return false;">Terms and Conditions</a>
                        and confirm that the information provided above is correct.
                    </label>
                </div>

                <center>
                    <input type="submit" value="Register">
                    <input type="reset" value="Clear">
                </center>

            </form>
        </section>

        <section class="login-link">
            <p>Already have an account? <a href="Login.html">Login Here</a></p>
        </section>
    </main>

    <footer>
        <p><b>Student Hub</b></p>
        <p>Learn • Explore • Grow</p>
        <p>&copy; 2026 Student Hub. All Rights Reserved.</p>
        <p>Designed &amp; Developed by Heni Patel</p>
    </footer>

    <script>
        let menuButton = document.getElementById("menuButton");
        let navigation = document.getElementById("navigation");

        menuButton.addEventListener("click", function () {
            navigation.classList.toggle("show");
        });

        let themeButton = document.getElementById("themeButton");
        let savedTheme = localStorage.getItem("theme");

        if (savedTheme === "dark") {
            document.body.classList.add("dark");
            themeButton.innerHTML = "☀️";
        } else {
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

        let form = document.getElementById("registrationForm");
        let fullName = document.getElementById("fullName");
        let enrollment = document.getElementById("enrollment");
        let username = document.getElementById("username");
        let email = document.getElementById("email");
        let phone = document.getElementById("phone");
        let passwordInput = document.getElementById("password");
        let confirmPassword = document.getElementById("confirmPassword");
        let department = document.getElementById("department");
        let course = document.getElementById("course");
        let year = document.getElementById("year");
        let terms = document.getElementById("terms");
        let passwordStrength = document.getElementById("passwordStrength");

        form.addEventListener("submit", function (event) {
            event.preventDefault();

            document.querySelectorAll(".error-message").forEach(error => error.remove());

            document.querySelectorAll(".error-field").forEach(field => {
                field.classList.remove("error-field");
            });

            terms.classList.remove("terms-error");

            let isValid = true;

        
function showError(field, message) {
    field.classList.add("error-field");

    let error = document.createElement("div");
    error.className = "error-message";
    error.innerText = message;

    if (field === passwordInput || field === confirmPassword) {
        field.closest(".password-container").appendChild(error);
    } else {
        field.insertAdjacentElement("afterend", error);
    }

    isValid = false;
}



            if (fullName.value.trim() === "") {
                showError(fullName, "Please enter your full name.");
            } else if (!/^[A-Za-z ]+$/.test(fullName.value.trim())) {
                showError(fullName, "Please enter only letters and spaces.");
            }

            if (enrollment.value.trim() === "") {
                showError(enrollment, "Please enter your enrollment number.");
            } else if (!/^[A-Za-z0-9]+$/.test(enrollment.value.trim())) {
                showError(enrollment, "Use only letters and numbers.");
            }

            if (username.value.trim() === "") {
                showError(username, "Please enter your username.");
            } else if (!/^[A-Za-z0-9_]+$/.test(username.value.trim())) {
                showError(username, "Username can contain letters, numbers and underscores only.");
            } else if (username.value.trim().length < 3) {
                showError(username, "Username must contain at least 3 characters.");
            }

            if (email.value.trim() === "") {
                showError(email, "Please enter your email address.");
            } else if (!/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/.test(email.value.trim())) {
                showError(email, "Please enter a valid email address.");
            }

            if (!/^[6-9][0-9]{9}$/.test(phone.value.trim())) {
                showError(phone, "Enter a valid 10-digit phone number.");
            }

            let passwordValue = passwordInput.value;

            if (passwordValue === "") {
                showError(passwordInput, "Please enter your password.");
            } else if (!/^[A-Z]/.test(passwordValue)) {
                showError(passwordInput, "Password must start with a capital letter.");
            } else if (passwordValue.length < 8) {
                showError(passwordInput, "Password must contain at least 8 characters.");
            } else if (
                !/[a-z]/.test(passwordValue) ||
                !/[0-9]/.test(passwordValue) ||
                !/[!@#$%^&*(),.?":{}|<>]/.test(passwordValue)
            ) {
                showError(
                    passwordInput,
                    "Include a lowercase letter, number and special character."
                );
            }

            if (confirmPassword.value === "") {
                showError(confirmPassword, "Please confirm your password.");
            } else if (passwordValue !== confirmPassword.value) {
                showError(confirmPassword, "Passwords do not match.");
            }

            if (!document.querySelector('input[name="gender"]:checked')) {
                let genderBox = document.querySelector(".gender-box");
                let error = document.createElement("div");

                error.className = "error-message";
                error.innerText = "Please select your gender.";

                genderBox.closest(".form-row").appendChild(error);
                isValid = false;
            }

            if (course.value === "") {
                showError(course, "Please select your course.");
            }

            if (department.value === "") {
                showError(department, "Please select your department.");
            }

            if (year.value === "") {
                showError(year, "Please select your year.");
            }

            if (!terms.checked) {
                terms.classList.add("terms-error");

                let error = document.createElement("div");
                error.className = "error-message";
                error.innerText = "Please accept the Terms and Conditions.";

                terms.closest(".terms-row").appendChild(error);
                isValid = false;
            }

            if (isValid) {
                form.submit();
            }
        });

        passwordInput.addEventListener("input", function () {
            let value = passwordInput.value;

            passwordInput.classList.remove(
                "password-weak",
                "password-medium",
                "password-strong"
            );

            passwordStrength.classList.remove("weak", "medium", "strong");

            if (value.length === 0) {
                passwordStrength.innerText = "";
                return;
            }

            let score = 0;

            if (value.length >= 8) score++;
            if (/[A-Z]/.test(value)) score++;
            if (/[a-z]/.test(value)) score++;
            if (/[0-9]/.test(value)) score++;
            if (/[^A-Za-z0-9]/.test(value)) score++;

            if (score <= 2) {
                passwordStrength.innerText = "Password Strength: Easy";
                passwordStrength.classList.add("weak");
                passwordInput.classList.add("password-weak");
            } else if (score <= 3) {
                passwordStrength.innerText = "Password Strength: Medium";
                passwordStrength.classList.add("medium");
                passwordInput.classList.add("password-medium");
            } else {
                passwordStrength.innerText = "Password Strength: Strong";
                passwordStrength.classList.add("strong");
                passwordInput.classList.add("password-strong");
            }
        });

        let passwordToggle = document.getElementById("passwordToggle");
        let confirmPasswordToggle = document.getElementById("confirmPasswordToggle");

        passwordToggle.addEventListener("click", function () {
            passwordInput.type = passwordInput.type === "password" ? "text" : "password";

            passwordToggle.innerHTML = passwordInput.type === "password" ? "👁️" : "🙈";

            passwordToggle.title = passwordInput.type === "password"
                ? "Show Password"
                : "Hide Password";
        });

        confirmPasswordToggle.addEventListener("click", function () {
            confirmPassword.type = confirmPassword.type === "password"
                ? "text"
                : "password";

            confirmPasswordToggle.innerHTML = confirmPassword.type === "password"
                ? "👁️"
                : "🙈";

            confirmPasswordToggle.title = confirmPassword.type === "password"
                ? "Show Password"
                : "Hide Password";
        });

        form.addEventListener("reset", function () {
            setTimeout(function () {
                document.querySelectorAll(".error-field").forEach(field => {
                    field.classList.remove("error-field");
                });

                document.querySelectorAll(".error-message").forEach(error => {
                    error.remove();
                });

                passwordInput.classList.remove(
                    "password-weak",
                    "password-medium",
                    "password-strong"
                );

                passwordStrength.className = "";
                passwordStrength.innerText = "";

                passwordInput.type = "password";
                confirmPassword.type = "password";

                passwordToggle.innerHTML = "👁️";
                confirmPasswordToggle.innerHTML = "👁️";

                terms.classList.remove("terms-error");
            }, 0);
        });
    </script>

</body>

</html>