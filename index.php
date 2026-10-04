<?php

$errors = [];

if (isset($_GET["error"]) && $_GET["error"] != "") {
    $errors = explode("|", $_GET["error"]);
}

$success = isset($_GET["success"]) && $_GET["success"] == "1";

$fullName = $_GET["fullName"] ?? "";
$enrollment = $_GET["enrollment"] ?? "";
$email = $_GET["email"] ?? "";
$phone = $_GET["phone"] ?? "";
$gender = $_GET["gender"] ?? "";
$course = $_GET["course"] ?? "";
$department = $_GET["department"] ?? "";
$year = $_GET["year"] ?? "";
$terms = $_GET["terms"] ?? "";

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student Hub - Register</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
}

body{

    background-image:
    linear-gradient(
        rgba(248,250,252,0.85),
        rgba(234,244,248,0.85)
    ),
    url("background.jpg");

    background-size:cover;
    background-position:center;
    background-repeat:no-repeat;
    background-attachment:fixed;

    color:#2E2E2E;

    transition:0.3s;

}


/* HEADER */

header{

    background:linear-gradient(
        135deg,
        #002147,
        #4dc8e0
    );

    color:white;

    text-align:center;

    padding:18px 20px 27px;

    box-shadow:0 5px 15px rgba(0,0,0,0.2);

}


/* LOGO */

header img{

    width:165px;
    height:165px;

    object-fit:cover;

    border-radius:50%;

    border:4px solid #111;

    background:white;

    padding:4px;

}


/* TITLE */

header h1{

    font-size:34px;

    margin-top:12px;
    margin-bottom:10px;

    letter-spacing:1px;

}

header h2{

    font-size:23px;

    margin-bottom:16px;

}


/* THEME BUTTON */

#themeButton{

    position:absolute;

    top:10px;
    right:22px;

    width:44px;
    height:44px;

    border:none;

    border-radius:50%;

    background:white;

    font-size:20px;

    cursor:pointer;

    box-shadow:0 3px 10px rgba(0,0,0,0.2);

}


/* NAVIGATION */

nav{

    display:flex;

    justify-content:center;

    align-items:center;

    flex-wrap:wrap;

    gap:10px;

}


nav a{

    text-decoration:none;

    color:white;

    background:rgba(255,255,255,0.18);

    padding:11px 17px;

    border-radius:25px;

    font-size:14px;

    font-weight:600;

    transition:0.3s;

}


nav a:hover{

    background:white;

    color:#002147;

}


nav a.active{

    background:white;

    color:#002147;

}


/* HAMBURGER */

#menuButton{

    display:none;

    background:white;

    color:#002147;

    border:none;

    border-radius:25px;

    padding:10px 20px;

    font-weight:bold;

    margin:10px auto;

}


/* MAIN */

main{

    min-height:620px;

    padding:35px 20px 50px;

}


/* REGISTER BOX */

.register-box{

    width:585px;

    max-width:95%;

    margin:0 auto;

    background:rgba(255,255,255,0.96);

    border-radius:17px;

    padding:34px 40px 30px;

    box-shadow:0 5px 20px rgba(0,0,0,0.16);

}


/* TITLE */

.register-box h2{

    text-align:center;

    color:#002147;

    font-size:28px;

    margin-bottom:12px;

}


.register-box > p{

    text-align:center;

    font-size:20px;

    margin-bottom:28px;

}


/* FORM ROW */

.form-row{

    display:flex;

    align-items:center;

    margin-bottom:15px;

}


/* LABEL */

.form-row > label{

    width:195px;

    min-width:195px;

    font-size:17px;

    color:#111;

}


/* INPUT */

.form-row input[type="text"],
.form-row input[type="password"],
.form-row select{

    width:100%;

    height:48px;

    border:1px solid #c9c9c9;

    border-radius:9px;

    padding:0 13px;

    font-size:15px;

    background:white;

    outline:none;

}


.form-row input:focus,
.form-row select:focus{

    border-color:#4dc8e0;

    box-shadow:0 0 5px rgba(77,200,224,0.35);

}


/* GENDER */

.gender-box{

    display:flex;

    gap:18px;

    align-items:center;

}


.gender-box label{

    width:auto;

    min-width:auto;

    font-weight:normal;

    font-size:15px;

}


.gender-box input{

    width:auto !important;

    height:auto !important;

    margin-right:4px;

}


/* TERMS */

.terms-row{

    display:flex;

    align-items:flex-start;

    gap:10px;

    margin-top:10px;

    margin-bottom:20px;

}


.terms-row input{

    width:19px;

    height:19px;

    margin-top:2px;

}


.terms-row label{

    font-size:13px;

    line-height:1.5;

}


.terms-row a{

    color:#005F73;

    font-weight:bold;

    text-decoration:none;

}


/* BUTTONS */

.button-row{

    text-align:center;

    margin-top:12px;

}


.button-row input{

    border:none;

    border-radius:25px;

    padding:12px 30px;

    margin:0 8px;

    font-size:15px;

    font-weight:bold;

    cursor:pointer;

    transition:0.3s;

}


.register-button{

    background:#002147;

    color:white;

}


.register-button:hover{

    background:#005F73;

}


.clear-button{

    background:#E4002B;

    color:white;

}


.clear-button:hover{

    background:#C00025;

}


/* ERROR */

.error-message{

    color:#d90429;

    font-size:13px;

    font-weight:600;

    margin-top:-8px;

    margin-bottom:10px;

    margin-left:195px;

}


.error-field{

    border:2px solid #d90429 !important;

    background:#fff7f7 !important;

}


/* SUCCESS */

.success-box{

    width:585px;

    max-width:95%;

    margin:0 auto;

    background:rgba(255,255,255,0.96);

    border-radius:17px;

    padding:50px 40px;

    text-align:center;

    box-shadow:0 5px 20px rgba(0,0,0,0.16);

}


.success-box h1{

    color:green;

    margin-bottom:15px;

}


.success-box p{

    font-size:17px;

}


.success-button{

    display:inline-block;

    margin-top:20px;

    padding:12px 25px;

    border-radius:25px;

    background:#002147;

    color:white;

    text-decoration:none;

    font-weight:bold;

}


/* FOOTER */

footer{

    background:linear-gradient(
        135deg,
        #002147,
        #4dc8e0
    );

    color:white;

    text-align:center;

    padding:20px;

}


footer p{

    margin:4px;

}


/* DARK MODE */

body.dark{

    background:#10191d;

    color:white;

}


body.dark .register-box,
body.dark .success-box{

    background:#1c292f;

    color:white;

}


body.dark .register-box h2{

    color:#4dc8e0;

}


body.dark .register-box > p{

    color:white;

}


body.dark .form-row > label{

    color:white;

}


body.dark .form-row input[type="text"],
body.dark .form-row input[type="password"],
body.dark .form-row select{

    background:#26363d;

    color:white;

    border-color:#526a73;

}


body.dark .terms-row label{

    color:white;

}


body.dark .terms-row a{

    color:#4dc8e0;

}


/* RESPONSIVE */

@media(max-width:800px){

    #menuButton{

        display:block;

    }


    nav{

        display:none;

        flex-direction:column;

    }


    nav.show{

        display:flex;

    }


    nav a{

        width:200px;

        text-align:center;

    }

}


@media(max-width:650px){

    header img{

        width:130px;

        height:130px;

    }


    header h1{

        font-size:29px;

    }


    header h2{

        font-size:21px;

    }


    .register-box{

        padding:28px 20px;

    }


    .form-row{

        display:block;

    }


    .form-row > label{

        display:block;

        width:100%;

        min-width:0;

        margin-bottom:7px;

    }


    .form-row input[type="text"],
    .form-row input[type="password"],
    .form-row select{

        width:100%;

    }


    .error-message{

        margin-left:0;

    }

}

</style>

</head>


<body>


<header>


<button id="themeButton" type="button">
🌙
</button>


<img src="logo.jpeg" alt="Student Hub Logo">


<h1>
STUDENT HUB
</h1>


<h2>
Registration
</h2>


<button id="menuButton" type="button">
☰
</button>


<nav id="navigation">


<a href="Home.html">
Home
</a>


<a href="index.php" class="active">
Register
</a>


<a href="Dashboard.html">
Dashboard
</a>


<a href="Profile.html">
Profile
</a>


<a href="Events.html">
Events
</a>


<a href="About.html">
About Us
</a>


<a href="ContactUs.html">
Contact Us
</a>


<a href="Feedback.html">
Feedback
</a>


<a href="Location.html">
Location
</a>


</nav>

</header>


<main>


<?php if ($success) { ?>


<div class="success-box">

<h1>
✓ Congratulations!
</h1>

<p>
Your registration has been done successfully.
</p>

</div>

<?php } else { ?>


<section class="register-box">


<h2>
🎓 Student Registration
</h2>


<p>
Create your Student Hub account.
</p>


<form action="process.php" method="POST">


<!-- FULL NAME -->

<div class="form-row">

<label>
👤 <b>Full Name :</b>
</label>

<input
type="text"
name="fullName"
placeholder="Enter Full Name"
value="<?php echo htmlspecialchars($fullName); ?>"
>

</div>


<?php

if (in_array("Full Name is required.", $errors)) {

    echo '<div class="error-message">Full Name is required.</div>';

}

if (in_array("Full Name should contain only letters and spaces.", $errors)) {

    echo '<div class="error-message">Full Name should contain only letters and spaces.</div>';

}

?>


<!-- ENROLLMENT -->

<div class="form-row">

<label>
🎓 <b>Enrollment No. :</b>
</label>

<input
type="text"
name="enrollment"
placeholder="Enter Enrollment No."
value="<?php echo htmlspecialchars($enrollment); ?>"
>

</div>


<?php

if (in_array("Enrollment Number is required.", $errors)) {

    echo '<div class="error-message">Enrollment Number is required.</div>';

}

if (in_array("Enrollment Number should contain only letters and numbers.", $errors)) {

    echo '<div class="error-message">Enrollment Number should contain only letters and numbers.</div>';

}

?>


<!-- EMAIL -->

<div class="form-row">

<label>
✉️ <b>Email :</b>
</label>

<input
type="text"
name="email"
placeholder="Enter Email"
value="<?php echo htmlspecialchars($email); ?>"
>

</div>


<?php

if (in_array("Email is required.", $errors)) {

    echo '<div class="error-message">Email is required.</div>';

}

if (in_array("Please enter a valid email address.", $errors)) {

    echo '<div class="error-message">Please enter a valid email address.</div>';

}

?>


<!-- PHONE -->

<div class="form-row">

<label>
📱 <b>Phone Number :</b>
</label>

<input
type="text"
name="phone"
placeholder="Enter Phone Number"
value="<?php echo htmlspecialchars($phone); ?>"
>

</div>


<?php

if (in_array("Phone Number is required.", $errors)) {

    echo '<div class="error-message">Phone Number is required.</div>';

}

if (in_array("Phone Number must contain exactly 10 digits and start with 6-9.", $errors)) {

    echo '<div class="error-message">Phone Number must contain exactly 10 digits and start with 6-9.</div>';

}

?>


<!-- PASSWORD -->

<div class="form-row">

<label>
🔐 <b>Password :</b>
</label>

<input
type="password"
name="password"
placeholder="Create Password"
>

</div>


<?php

if (in_array("Password is required.", $errors)) {

    echo '<div class="error-message">Password is required.</div>';

}

if (in_array("Password must be at least 8 characters long.", $errors)) {

    echo '<div class="error-message">Password must be at least 8 characters long.</div>';

}

if (in_array("Password must start with a capital letter.", $errors)) {

    echo '<div class="error-message">Password must start with a capital letter.</div>';

}

?>


<!-- CONFIRM PASSWORD -->

<div class="form-row">

<label>
🔐 <b>Confirm Password :</b>
</label>

<input
type="password"
name="confirmPassword"
placeholder="Confirm Password"
>

</div>


<?php

if (in_array("Confirm Password is required.", $errors)) {

    echo '<div class="error-message">Confirm Password is required.</div>';

}

if (in_array("Password and Confirm Password do not match.", $errors)) {

    echo '<div class="error-message">Password and Confirm Password do not match.</div>';

}

?>


<!-- GENDER -->

<div class="form-row">

<label>
⚥ <b>Gender :</b>
</label>

<div class="gender-box">

<label>

<input
type="radio"
name="gender"
value="Male"
<?php if ($gender == "Male") echo "checked"; ?>
>

Male

</label>


<label>

<input
type="radio"
name="gender"
value="Female"
<?php if ($gender == "Female") echo "checked"; ?>
>

Female

</label>

</div>

</div>


<?php

if (in_array("Please select your gender.", $errors)) {

    echo '<div class="error-message">Please select your gender.</div>';

}

?>


<!-- COURSE -->

<div class="form-row">

<label>
📚 <b>Course :</b>
</label>

<select name="course">

<option value="">
Select your course
</option>

<option value="B.Tech/BE"
<?php if ($course == "B.Tech/BE") echo "selected"; ?>>
B.Tech/BE
</option>

<option value="M.Tech/ME"
<?php if ($course == "M.Tech/ME") echo "selected"; ?>>
M.Tech/ME
</option>

</select>

</div>


<?php

if (in_array("Please select your course.", $errors)) {

    echo '<div class="error-message">Please select your course.</div>';

}

?>


<!-- DEPARTMENT -->

<div class="form-row">

<label>
🏫 <b>Department :</b>
</label>

<select name="department">

<option value="">
Select
</option>

<option value="Computer Engineering"
<?php if ($department == "Computer Engineering") echo "selected"; ?>>
Computer Engineering
</option>

<option value="Information Technology"
<?php if ($department == "Information Technology") echo "selected"; ?>>
Information Technology
</option>

<option value="Mechanical Engineering"
<?php if ($department == "Mechanical Engineering") echo "selected"; ?>>
Mechanical Engineering
</option>

<option value="Civil Engineering"
<?php if ($department == "Civil Engineering") echo "selected"; ?>>
Civil Engineering
</option>

<option value="Electrical Engineering"
<?php if ($department == "Electrical Engineering") echo "selected"; ?>>
Electrical Engineering
</option>

</select>

</div>


<?php

if (in_array("Please select your department.", $errors)) {

    echo '<div class="error-message">Please select your department.</div>';

}

?>


<!-- YEAR -->

<div class="form-row">

<label>
📅 <b>Year :</b>
</label>

<select name="year">

<option value="">
Select your year
</option>

<option value="1st Year"
<?php if ($year == "1st Year") echo "selected"; ?>>
1st Year
</option>

<option value="2nd Year"
<?php if ($year == "2nd Year") echo "selected"; ?>>
2nd Year
</option>

<option value="3rd Year"
<?php if ($year == "3rd Year") echo "selected"; ?>>
3rd Year
</option>

<option value="4th Year"
<?php if ($year == "4th Year") echo "selected"; ?>>
4th Year
</option>

</select>

</div>


<?php

if (in_array("Please select your year.", $errors)) {

    echo '<div class="error-message">Please select your year.</div>';

}

?>


<!-- TERMS -->

<div class="terms-row">

<input
type="checkbox"
name="terms"
value="accepted"
<?php if ($terms == "accepted") echo "checked"; ?>
>

<label>

I agree to the

<a href="#">
Terms and Conditions
</a>

and confirm that the information provided above is correct.

</label>

</div>


<?php

if (in_array("Please accept the Terms and Conditions.", $errors)) {

    echo '<div class="error-message">Please accept the Terms and Conditions.</div>';

}

?>


<!-- BUTTONS -->

<div class="button-row">

<input
type="submit"
value="Register"
class="register-button"
>


<input
type="reset"
value="Clear"
class="clear-button"
>

</div>


</form>

</section>


<?php } ?>


</main>


<footer>

<p>
<b>Student Hub</b>
</p>

<p>
Learn • Explore • Grow
</p>

<p>
© 2026 Student Hub. All Rights Reserved.
</p>

</footer>


<script>


/* HAMBURGER MENU */

let menuButton = document.getElementById("menuButton");

let navigation = document.getElementById("navigation");


menuButton.addEventListener("click", function(){

    navigation.classList.toggle("show");

});


/* DARK / LIGHT MODE */

let themeButton = document.getElementById("themeButton");

let savedTheme = localStorage.getItem("theme");


if(savedTheme === "dark"){

    document.body.classList.add("dark");

    themeButton.textContent = "☀️";

}


themeButton.addEventListener("click", function(){

    document.body.classList.toggle("dark");


    if(document.body.classList.contains("dark")){

        localStorage.setItem("theme","dark");

        themeButton.textContent = "☀️";

    }
    else{

        localStorage.setItem("theme","light");

        themeButton.textContent = "🌙";

    }

});


</script>


</body>

</html>