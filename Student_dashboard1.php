<?php
require_once "timeout.php";

if (strtolower(trim($_SESSION["role"] ?? "")) !== "student") {
    http_response_code(403);
    exit("Access Denied: Student only.");
}

$showLoginSuccess = !empty($_SESSION["login_success"]);
unset($_SESSION["login_success"]);

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

/* ---- Semester-wise subject marks + submissions added by admin ---- */
require_once "db.php";

$marksBySemester = [];
$submissions = [];
$announcements = [];
$timetableItems = [];
$examItems = [];

if (isset($conn) && $conn instanceof mysqli) {
    $marksUserId = (int)($_SESSION["user_id"] ?? 0);

    /* ---- Announcements published by admin ---- */
    try {
        $announcementResult = $conn->query(
            "SELECT id, title, message, category, created_at
             FROM announcements
             ORDER BY created_at DESC, id DESC"
        );

        if ($announcementResult) {
            while ($announcementRow = $announcementResult->fetch_assoc()) {
                $announcements[] = $announcementRow;
            }
            $announcementResult->free();
        }
    } catch (Throwable $e) {
        error_log("Announcements query failed: " . $e->getMessage());
    }

    /* ---- Timetable published by admin ---- */
    try {
        $timetableResult = $conn->query(
            "SELECT semester, day_of_week, start_time, end_time,
                    subject, faculty, room
             FROM timetable
             ORDER BY semester,
                      FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday',
                            'Thursday', 'Friday', 'Saturday'),
                      start_time"
        );

        if ($timetableResult) {
            while ($timetableRow = $timetableResult->fetch_assoc()) {
                $timetableItems[] = $timetableRow;
            }
            $timetableResult->free();
        }
    } catch (Throwable $e) {
        error_log("Timetable query failed: " . $e->getMessage());
    }

    /* ---- Exam and practical schedule published by admin ---- */
    try {
        $examResult = $conn->query(
            "SELECT semester, exam_type, subject, exam_date,
                    start_time, end_time, venue
             FROM exam_schedule
             ORDER BY exam_date, start_time"
        );

        if ($examResult) {
            while ($examRow = $examResult->fetch_assoc()) {
                $examItems[] = $examRow;
            }
            $examResult->free();
        }
    } catch (Throwable $e) {
        error_log("Exam schedule query failed: " . $e->getMessage());
    }

    try {
        $marksStmt = $conn->prepare(
            "SELECT semester, subject_name, marks_obtained, total_marks
             FROM subject_marks
             WHERE user_id = ?
             ORDER BY semester, subject_name"
        );

        if ($marksStmt) {
            $marksStmt->bind_param("i", $marksUserId);
            $marksStmt->execute();
            $marksResult = $marksStmt->get_result();

            while ($marksRow = $marksResult->fetch_assoc()) {
                $marksBySemester[(int)$marksRow["semester"]][] = $marksRow;
            }

            $marksStmt->close();
        }
    } catch (Throwable $e) {
        error_log("Subject marks query failed: " . $e->getMessage());
    }

    try {
        $subStmt = $conn->prepare(
            "SELECT subject_name, assignment_title, submission_date,
                    status, grade, feedback
             FROM student_assignments
             WHERE user_id = ?
             ORDER BY submission_date DESC, id DESC"
        );

        if ($subStmt) {
            $subStmt->bind_param("i", $marksUserId);
            $subStmt->execute();
            $subResult = $subStmt->get_result();

            while ($subRow = $subResult->fetch_assoc()) {
                $submissions[] = $subRow;
            }

            $subStmt->close();
        }
    } catch (Throwable $e) {
        error_log("Submissions query failed: " . $e->getMessage());
    }
}

$submissionLabels = [
    "graded"    => "✓ Graded",
    "submitted" => "✓ Submitted",
    "review"    => "⏳ Under Review",
];
?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student Hub - Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
}

body{
    background:linear-gradient(135deg,#F8FAFC,#EAF4F8);
    color:#2E2E2E;
    transition:0.3s;
}


/* NOTIFICATION */

#notification{
    background:#005F73;
    color:white;
    padding:12px;
    text-align:center;
    font-weight:bold;
}

#closeNotification{
    margin-left:15px;
    border:none;
    padding:5px 10px;
    border-radius:15px;
    cursor:pointer;
    transition:0.3s;
}

#closeNotification:hover{
    transform:scale(1.1);
}


/* THEME BUTTON */

#themeButton{
    position:absolute;
    top:18px;
    right:25px;
    width:44px;
    height:44px;
    border:none;
    border-radius:50%;
    background:white;
    color:#002147;
    font-size:20px;
    display:flex;
    justify-content:center;
    align-items:center;
    cursor:pointer;
    box-shadow:0 4px 10px rgba(0,0,0,0.2);
    transition:0.3s;
    z-index:10;
}

#themeButton:hover{
    transform:scale(1.1);
}


/* HAMBURGER */

#menuButton{
    display:none;
    background:white;
    color:#002147;
    border:none;
    padding:10px 20px;
    border-radius:25px;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
}

#menuButton:hover{
    transform:scale(1.05);
}


/* HEADER */

header{
    position:relative;
    background:linear-gradient(135deg,#002147,#4dc8e0);
    color:white;
    text-align:center;
    padding:30px 20px;
    box-shadow:0 8px 20px rgba(0,0,0,0.18);
}

header img{
    width:180px;
    height:180px;
    object-fit:cover;
    border-radius:50%;
    border:5px solid rgb(13,0,0);
    padding:5px;
    background:white;
}

header h2{
    margin:15px 0;
    font-size:36px;
}

header h3{
    font-size:26px;
}


/* NAVIGATION */

nav{
    display:flex;
    justify-content:center;
    flex-wrap:wrap;
    gap:12px;
    margin-top:20px;
}

nav a{
    text-decoration:none;
    color:white;
    background:rgba(255,255,255,0.15);
    padding:10px 18px;
    border-radius:25px;
    font-weight:600;
    transition:0.3s;
}

nav a:hover,
nav a.active{
    background:white;
    color:#002147;
    transform:translateY(-2px);
}


/* MAIN */

main{
    width:92%;
    max-width:1250px;
    margin:auto;
    padding:45px 0;
}


/* DASHBOARD BOX */

.dashboard-box{
    background:white;
    width:100%;
    padding:35px;
    border-radius:20px;
    box-shadow:0 8px 20px rgba(0,0,0,0.12);
    margin-bottom:35px;
    text-align:center;
    transition:0.3s;
}

.dashboard-box h3{
    color:#002147;
    font-size:30px;
    margin-bottom:30px;
}


/* SUMMARY CARDS */

.summary-table{
    width:100%;
    border-collapse:separate;
    border-spacing:18px;
}

.summary-table td{
    width:33.33%;
    background:linear-gradient(135deg,#F8FAFC,#EAF4F8);
    padding:28px 20px;
    border-radius:18px;
    box-shadow:0 5px 15px rgba(0,0,0,0.08);
    font-size:18px;
    transition:0.3s;
    cursor:pointer;
}

.summary-table td:hover{
    transform:translateY(-7px);
    box-shadow:0 12px 25px rgba(0,0,0,0.16);
}

.summary-icon{
    font-size:38px;
    margin-bottom:12px;
}

.summary-title{
    display:block;
    color:#002147;
    font-size:20px;
    font-weight:bold;
    margin-bottom:10px;
}

.summary-number{
    font-size:27px;
    font-weight:bold;
    color:#005F73;
}

.summary-description{
    font-size:15px;
    color:#555;
    margin-top:5px;
}


/* SEARCH AND FILTER */

.submission-controls{
    width:100%;
    display:flex;
    justify-content:center;
    align-items:center;
    gap:15px;
    margin-bottom:25px;
    flex-wrap:wrap;
}

#submissionSearch{
    width:45%;
    min-width:250px;
    padding:13px 20px;
    border:1px solid #ccc;
    border-radius:30px;
    outline:none;
    font-size:16px;
    transition:0.3s;
}

#statusFilter,
#sortFilter{
    width:190px;
    padding:13px 18px;
    border:1px solid #ccc;
    border-radius:30px;
    outline:none;
    font-size:16px;
    background:white;
    cursor:pointer;
    transition:0.3s;
}

#submissionSearch:focus,
#statusFilter:focus,
#sortFilter:focus{
    border-color:#005F73;
    box-shadow:0 0 0 3px rgba(0,95,115,0.12);
}

body.dark #submissionSearch,
body.dark #statusFilter,
body.dark #sortFilter{
    background:#292929;
    color:white;
    border-color:#555;
}

body.dark #submissionSearch:focus,
body.dark #statusFilter:focus,
body.dark #sortFilter:focus{
    border-color:#4dc8e0;
    box-shadow:0 0 0 3px rgba(77,200,224,0.15);
}


/* RECENT SUBMISSION TABLE */

.table-container{
    width:100%;
    overflow-x:auto;
    border-radius:12px;
}

.details-table{
    width:100%;
    min-width:850px;
    border-collapse:collapse;
    background:white;
    overflow:hidden;
}

.details-table th{
    background:#002147;
    color:white;
    padding:17px 14px;
    font-size:18px;
    text-align:center;
}

.details-table td{
    padding:18px 14px;
    border:1px solid #ddd;
    font-size:17px;
    text-align:center;
    vertical-align:middle;
}

.details-table tbody tr{
    transition:0.3s;
}

.details-table tbody tr:hover{
    background:#EAF4F8;
    transform:scale(1.005);
}


/* LOADING */

#loadingSubmissions{
    text-align:center;
    padding:25px;
    color:#005F73;
    font-size:17px;
    font-weight:bold;
}

body.dark #loadingSubmissions{
    color:#4dc8e0;
}


/* NO RESULT */

#noSubmission{
    display:none;
    text-align:center;
    padding:25px;
    color:#777;
    font-size:17px;
}

body.dark #noSubmission{
    color:#ccc;
}


/* PAGINATION */

#pagination{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:8px;
    margin-top:25px;
    flex-wrap:wrap;
}

.page-button{
    border:none;
    background:#EAF4F8;
    color:#002147;
    min-width:40px;
    height:40px;
    padding:0 13px;
    border-radius:20px;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
}

.page-button:hover{
    background:#4dc8e0;
    color:#002147;
    transform:translateY(-2px);
}

.page-button.active{
    background:#002147;
    color:white;
}

.page-button:disabled{
    opacity:0.5;
    cursor:not-allowed;
    transform:none;
}

body.dark .page-button{
    background:#333;
    color:white;
}

body.dark .page-button:hover{
    background:#4dc8e0;
    color:#002147;
}

body.dark .page-button.active{
    background:#4dc8e0;
    color:#002147;
}


/* TABLE ICONS */

.table-icon{
    font-size:22px;
    margin-right:7px;
}


/* STATUS BADGES */

.status{
    display:inline-block;
    padding:7px 13px;
    border-radius:20px;
    font-size:14px;
    font-weight:bold;
}

.status.graded{
    background:#d9f7e5;
    color:#087443;
}

.status.submitted{
    background:#dceeff;
    color:#005F73;
}

.status.review{
    background:#fff0c9;
    color:#8a6100;
}


/* FOOTER */

footer{
    background:linear-gradient(135deg,#002147,#4dc8e0);
    color:white;
    text-align:center;
    padding:20px;
    margin-top:40px;
}

footer p{
    margin:5px 0;
}


/* DARK MODE */

body.dark{
    background:#121212;
    color:white;
}

body.dark header{
    background:linear-gradient(135deg,#002147,#4dc8e0);
}

body.dark footer{
    background:linear-gradient(135deg,#002147,#4dc8e0);
}

body.dark .dashboard-box{
    background:#1e1e1e;
    color:white;
}

body.dark .dashboard-box h3{
    color:#4dc8e0;
}

body.dark .summary-table td{
    background:#292929;
    color:white;
}

body.dark .summary-table td:hover{
    background:#333;
}

body.dark .summary-title{
    color:#4dc8e0;
}

body.dark .summary-number{
    color:#4dc8e0;
}

body.dark .summary-description{
    color:#ccc;
}

body.dark .details-table{
    background:#1e1e1e;
}

body.dark .details-table td{
    background:#1e1e1e;
    color:white;
    border-color:#555;
}

body.dark .details-table tbody tr:hover{
    background:#333;
}

body.dark .details-table th{
    background:#002147;
    color:white;
}


/* TABLET */

@media(max-width:900px){

    main{
        width:94%;
    }

    .dashboard-box{
        padding:25px;
    }

    .summary-table{
        border-spacing:10px;
    }

    .summary-table td{
        padding:22px 12px;
    }

    #submissionSearch{
        width:50%;
    }

}


/* MOBILE */

@media(max-width:700px){

    #menuButton{
        display:block;
        margin:15px auto;
    }

    #navigation{
        display:none;
        flex-direction:column;
        align-items:center;
    }

    #navigation.show{
        display:flex;
    }

    nav a{
        width:220px;
        text-align:center;
    }

    #themeButton{
        top:15px;
        right:15px;
    }

    header{
        padding:25px 15px;
    }

    header img{
        width:135px;
        height:135px;
    }

    header h2{
        font-size:28px;
    }

    header h3{
        font-size:22px;
    }

    main{
        width:94%;
        padding:25px 0;
    }

    .dashboard-box{
        padding:20px 14px;
        border-radius:16px;
    }

    .dashboard-box h3{
        font-size:25px;
        margin-bottom:20px;
    }


    /* SEARCH AND FILTER */

    .submission-controls{
        flex-direction:column;
        gap:12px;
    }

    #submissionSearch,
    #statusFilter,
    #sortFilter{
        width:90%;
        min-width:0;
    }


    /* SUMMARY CARDS */

    .summary-table,
    .summary-table tbody,
    .summary-table tr{
        display:block;
        width:100%;
    }

    .summary-table{
        border-spacing:0;
    }

    .summary-table td{
        display:block;
        width:100%;
        margin-bottom:15px;
        padding:22px 15px;
    }

    .summary-icon{
        font-size:34px;
    }

    .summary-title{
        font-size:19px;
    }

    .summary-number{
        font-size:25px;
    }


    /* RECENT SUBMISSIONS AS MOBILE CARDS */

    .table-container{
        overflow:visible;
    }

    .details-table{
        min-width:0;
        width:100%;
        display:block;
        background:transparent;
    }

    .details-table thead{
        display:none;
    }

    .details-table tbody{
        display:block;
        width:100%;
    }

    .details-table tr{
        display:block;
        width:100%;
        margin-bottom:18px;
        background:white;
        border-radius:15px;
        box-shadow:0 5px 15px rgba(0,0,0,0.10);
        overflow:hidden;
        border:1px solid #ddd;
    }

    .details-table td{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:15px;
        width:100%;
        min-height:55px;
        padding:13px 14px;
        border:none;
        border-bottom:1px solid #e0e0e0;
        text-align:right;
        font-size:15px;
        background:white;
    }

    .details-table td:last-child{
        border-bottom:none;
    }

    .details-table td::before{
        content:attr(data-label);
        font-weight:bold;
        color:#002147;
        text-align:left;
        min-width:100px;
    }

    .details-table td:nth-child(1)::before{
        content:"📚 Subject";
    }

    .details-table td:nth-child(2)::before{
        content:"📝 Assignment";
    }

    .details-table td:nth-child(3)::before{
        content:"📅 Date";
    }

    .details-table td:nth-child(4)::before{
        content:"📊 Status";
    }

    body.dark .details-table tr{
        background:#1e1e1e;
        border-color:#444;
    }

    body.dark .details-table td{
        background:#1e1e1e;
        color:white;
        border-color:#444;
    }

    body.dark .details-table td::before{
        color:#4dc8e0;
    }

    .status{
        font-size:12px;
        padding:6px 10px;
    }

}


/* SMALL MOBILE */

@media(max-width:400px){

    .dashboard-box{
        padding:17px 10px;
    }

    .dashboard-box h3{
        font-size:22px;
    }

    .details-table td{
        font-size:14px;
        padding:12px 10px;
    }

    .details-table td::before{
        min-width:90px;
        font-size:13px;
    }

    .status{
        font-size:11px;
    }

}


.login-success-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin: 15px auto;
    padding: 14px 20px;
    width: calc(100% - 30px);
    max-width: 1200px;
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 500;
}

.close-login-banner {
    border: none;
    background: transparent;
    color: inherit;
    font-size: 25px;
    line-height: 1;
    cursor: pointer;
    padding: 0 4px;
}

.close-login-banner:hover {
    opacity: 0.7;
}


/* ADMIN ANNOUNCEMENTS */

.announcement-list{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:18px;
    text-align:left;
}

.announcement-card{
    background:linear-gradient(135deg,#F8FAFC,#EAF4F8);
    border-left:5px solid #005F73;
    border-radius:14px;
    padding:20px;
    box-shadow:0 5px 15px rgba(0,0,0,0.08);
    overflow-wrap:anywhere;
}

.announcement-card h4{
    color:#002147;
    font-size:21px;
    margin-bottom:10px;
}

.announcement-card p{
    line-height:1.7;
    white-space:pre-wrap;
    margin:12px 0;
}

.announcement-meta{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    flex-wrap:wrap;
    font-size:13px;
    color:#607d8b;
}

.announcement-category{
    display:inline-block;
    padding:5px 11px;
    border-radius:20px;
    font-weight:bold;
    text-transform:capitalize;
    background:#dceeff;
    color:#005F73;
}

.announcement-category.academic{background:#d9f7e5;color:#087443;}
.announcement-category.exam{background:#fff0c9;color:#8a6100;}
.announcement-category.event{background:#ece3ff;color:#5b34b0;}
.announcement-category.urgent{background:#fee2e2;color:#b42318;}

.announcements-empty{
    background:#EAF4F8;
    border-radius:12px;
    padding:22px;
    text-align:center;
    color:#607d8b;
}

body.dark .announcement-card{
    background:#222b35;
    color:#f1f5f9;
}

body.dark .announcement-card h4{
    color:#4dc8e0;
}

body.dark .announcement-meta{
    color:#b9cbd1;
}

body.dark .announcements-empty{
    background:#222b35;
    color:#ccc;
}

/* SUBJECT MARKS (ADDED) */

.marks-sem{
    color:#005F73;
    font-size:22px;
    margin:25px 0 12px;
    text-align:left;
}

.marks-scroll{
    width:100%;
    overflow-x:auto;
    border-radius:12px;
}

.marks-table{
    width:100%;
    min-width:550px;
    border-collapse:collapse;
    background:white;
}

.marks-table th{
    background:#002147;
    color:white;
    padding:15px 12px;
    font-size:17px;
    text-align:center;
}

.marks-table td{
    padding:15px 12px;
    border:1px solid #ddd;
    font-size:16px;
    text-align:center;
    vertical-align:middle;
}

.marks-table tbody tr:nth-child(even){
    background:#EAF4F8;
}

.marks-table tbody tr:hover{
    background:#d7f0f5;
}

.marks-empty{
    color:#777;
    font-size:17px;
    padding:15px;
}

body.dark .marks-sem{
    color:#4dc8e0;
}

body.dark .marks-table{
    background:#1e1e1e;
}

body.dark .marks-table td{
    background:#1e1e1e;
    color:white;
    border-color:#555;
}

body.dark .marks-table tbody tr:nth-child(even) td{
    background:#292929;
}

body.dark .marks-table tbody tr:hover td{
    background:#333;
}

body.dark .marks-empty{
    color:#ccc;
}



/* SUBMISSIONS ADDED BY ADMIN (ADDED) */

#subSearch{
    width:45%;
    min-width:250px;
    padding:13px 20px;
    border:1px solid #ccc;
    border-radius:30px;
    outline:none;
    font-size:16px;
    transition:0.3s;
}

#subStatus,
#subSort{
    width:190px;
    padding:13px 18px;
    border:1px solid #ccc;
    border-radius:30px;
    outline:none;
    font-size:16px;
    background:white;
    cursor:pointer;
    transition:0.3s;
}

#subSearch:focus,
#subStatus:focus,
#subSort:focus{
    border-color:#005F73;
    box-shadow:0 0 0 3px rgba(0,95,115,0.12);
}

body.dark #subSearch,
body.dark #subStatus,
body.dark #subSort{
    background:#292929;
    color:white;
    border-color:#555;
}

body.dark #subSearch:focus,
body.dark #subStatus:focus,
body.dark #subSort:focus{
    border-color:#4dc8e0;
    box-shadow:0 0 0 3px rgba(77,200,224,0.15);
}

.feedback-cell{
    max-width:260px;
    overflow-wrap:anywhere;
    line-height:1.5;
}

#subNoResult{
    display:none;
    text-align:center;
    padding:25px;
    color:#777;
    font-size:17px;
}

body.dark #subNoResult{
    color:#ccc;
}

#subPagination{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:8px;
    margin-top:25px;
    flex-wrap:wrap;
}

.sub-page-button{
    border:none;
    background:#EAF4F8;
    color:#002147;
    min-width:40px;
    height:40px;
    padding:0 13px;
    border-radius:20px;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
}

.sub-page-button:hover{
    background:#4dc8e0;
    color:#002147;
    transform:translateY(-2px);
}

.sub-page-button.active{
    background:#002147;
    color:white;
}

.sub-page-button:disabled{
    opacity:0.5;
    cursor:not-allowed;
    transform:none;
}

body.dark .sub-page-button{
    background:#333;
    color:white;
}

body.dark .sub-page-button:hover{
    background:#4dc8e0;
    color:#002147;
}

body.dark .sub-page-button.active{
    background:#4dc8e0;
    color:#002147;
}

@media(max-width:900px){

    #subSearch{
        width:50%;
    }

}

@media(max-width:700px){

    #subSearch,
    #subStatus,
    #subSort{
        width:90%;
        min-width:0;
    }

}




/* TIMETABLE AND EXAM TABLE MOBILE LABEL FIX */
@media(max-width:700px){
    .details-table.schedule-table td:nth-child(n)::before{
        content:attr(data-label);
    }
}

</style>

</head>


<body>


<!-- NOTIFICATION -->

<div id="notification">

    🔔 New updates are available!

    <button id="closeNotification" type="button" aria-label="Close notification">
        ✕
    </button>

</div>


<!-- HEADER -->

<header>

    <button id="themeButton" type="button" title="Change theme" aria-label="Change theme">
        🌙
    </button>

    <img src="logo.jpeg" alt="Student Hub Logo">

    <h2>STUDENT HUB</h2>

    <h3>Dashboard</h3>

    <button id="menuButton" type="button" aria-label="Open navigation menu">
        ☰ Menu
    </button>

    <nav id="navigation">

        <a href="Home.php">Home</a>

        <a href="Student_dashboard1.php" class="active">Dashboard</a>

        <a href="Profile.php">Profile</a>

        <a href="Events.php">Events</a>

        <a href="About.html">About Us</a>

        <a href="ContactUs.html">Contact Us</a>

        <a href="Feedback.html">Feedback</a>

        <a href="Location.html">Location</a>
        <a href="logout.php">Logout</a>

    </nav>

</header>


<?php if ($showLoginSuccess): ?>
<div id="loginSuccessBanner" class="login-success-banner" role="status">
    <span>✓ Congratulations! You are logged in successfully.</span>
    <button type="button" id="closeLoginSuccess"
            class="close-login-banner" aria-label="Close notification">
        &times;
    </button>
</div>
<?php endif; ?>



<!-- MAIN -->

<main>


<!-- DASHBOARD SUMMARY -->

<section class="dashboard-box">

    <h3>📊 Dashboard Summary</h3>

    <table class="summary-table">

        <tr>

            <td>

                <div class="summary-icon">📚</div>

                <span class="summary-title">
                    Total Assignments
                </span>

                <div class="summary-number">
                    12 / 15
                </div>

                <div class="summary-description">
                    Submitted Successfully
                </div>

            </td>


            <td>

                <div class="summary-icon">⏰</div>

                <span class="summary-title">
                    Upcoming Deadlines
                </span>

                <div class="summary-number">
                    2 Pending
                </div>

                <div class="summary-description">
                    Due This Week
                </div>

            </td>


            <td>

                <div class="summary-icon">🗓️</div>

                <span class="summary-title">
                    Exam Schedule
                </span>

                <div class="summary-number">
                    Mid-Sem
                </div>

                <div class="summary-description">
                    Starts Next Month
                </div>

            </td>

        </tr>

    </table>

</section>


<!-- ANNOUNCEMENTS PUBLISHED BY ADMIN -->

<section class="dashboard-box" id="announcements">
    <h3>📢 Latest Announcements</h3>

    <?php if (count($announcements) > 0): ?>
        <div class="announcement-list">
            <?php foreach ($announcements as $announcement): ?>
                <?php
                $announcementCategory = strtolower(trim((string)($announcement["category"] ?? "general")));
                $allowedAnnouncementCategories = ["general", "academic", "exam", "event", "urgent"];
                if (!in_array($announcementCategory, $allowedAnnouncementCategories, true)) {
                    $announcementCategory = "general";
                }

                $announcementDate = "";
                if (!empty($announcement["created_at"])) {
                    $announcementTimestamp = strtotime((string)$announcement["created_at"]);
                    if ($announcementTimestamp !== false) {
                        $announcementDate = date("d M Y, h:i A", $announcementTimestamp);
                    }
                }
                ?>
                <article class="announcement-card">
                    <div class="announcement-meta">
                        <span class="announcement-category <?= h($announcementCategory) ?>">
                            <?= h(ucfirst($announcementCategory)) ?>
                        </span>
                        <?php if ($announcementDate !== ""): ?>
                            <span>📅 <?= h($announcementDate) ?></span>
                        <?php endif; ?>
                    </div>

                    <h4><?= h($announcement["title"] ?? "") ?></h4>
                    <p><?= h($announcement["message"] ?? "") ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="announcements-empty">
            📭 No announcements have been published yet.
        </div>
    <?php endif; ?>
</section>


<!-- TIMETABLE ADDED BY ADMIN -->

<section class="dashboard-box" id="studentTimetable">

    <h3>🗓️ College Timetable</h3>

    <?php if (count($timetableItems) > 0): ?>

        <div class="table-container">

            <table class="details-table schedule-table">
                <thead>
                    <tr>
                        <th>Semester</th>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Subject</th>
                        <th>Faculty</th>
                        <th>Room</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($timetableItems as $lecture): ?>
                        <?php
                        $lectureStart = strtotime((string)($lecture["start_time"] ?? ""));
                        $lectureEnd = strtotime((string)($lecture["end_time"] ?? ""));
                        ?>
                        <tr>
                            <td data-label="Semester"><?= (int)$lecture["semester"] ?></td>
                            <td data-label="Day"><?= h($lecture["day_of_week"] ?? "—") ?></td>
                            <td data-label="Time">
                                <?= $lectureStart !== false ? h(date("h:i A", $lectureStart)) : "—" ?>
                                -
                                <?= $lectureEnd !== false ? h(date("h:i A", $lectureEnd)) : "—" ?>
                            </td>
                            <td data-label="Subject"><?= h($lecture["subject"] ?? "—") ?></td>
                            <td data-label="Faculty"><?= h(($lecture["faculty"] ?? "") !== "" ? $lecture["faculty"] : "—") ?></td>
                            <td data-label="Room"><?= h(($lecture["room"] ?? "") !== "" ? $lecture["room"] : "—") ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>

    <?php else: ?>
        <div class="announcements-empty">🗓️ No timetable has been added yet.</div>
    <?php endif; ?>

</section>


<!-- EXAMS AND PRACTICALS ADDED BY ADMIN -->

<section class="dashboard-box" id="studentExamSchedule">

    <h3>📝 Exams &amp; Practicals Schedule</h3>

    <?php if (count($examItems) > 0): ?>

        <div class="table-container">

            <table class="details-table schedule-table">
                <thead>
                    <tr>
                        <th>Semester</th>
                        <th>Type</th>
                        <th>Subject</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Venue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($examItems as $exam): ?>
                        <?php
                        $examTimestamp = strtotime((string)($exam["exam_date"] ?? ""));
                        $examStart = strtotime((string)($exam["start_time"] ?? ""));
                        $examEnd = !empty($exam["end_time"])
                            ? strtotime((string)$exam["end_time"])
                            : false;
                        ?>
                        <tr>
                            <td data-label="Semester"><?= (int)$exam["semester"] ?></td>
                            <td data-label="Type"><?= h(ucfirst((string)($exam["exam_type"] ?? "—"))) ?></td>
                            <td data-label="Subject"><?= h($exam["subject"] ?? "—") ?></td>
                            <td data-label="Date">
                                <?= $examTimestamp !== false ? h(date("d M Y", $examTimestamp)) : "—" ?>
                            </td>
                            <td data-label="Time">
                                <?= $examStart !== false ? h(date("h:i A", $examStart)) : "—" ?>
                                <?php if ($examEnd !== false): ?>
                                    - <?= h(date("h:i A", $examEnd)) ?>
                                <?php endif; ?>
                            </td>
                            <td data-label="Venue"><?= h(($exam["venue"] ?? "") !== "" ? $exam["venue"] : "—") ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>

    <?php else: ?>
        <div class="announcements-empty">📝 No exam or practical schedule has been added yet.</div>
    <?php endif; ?>

</section>


<!-- SUBJECT MARKS (ADDED) -->

<section class="dashboard-box">

    <h3>🎓 Subject Marks</h3>

    <?php if (count($marksBySemester) > 0): ?>

        <?php foreach ($marksBySemester as $marksSem => $marksRows): ?>

            <h4 class="marks-sem">Semester <?= (int)$marksSem ?></h4>

            <div class="marks-scroll">
                <table class="marks-table">
                    <thead>
                        <tr>
                            <th>📚 Subject</th>
                            <th>📝 Marks Obtained</th>
                            <th>📊 Total Marks</th>
                            <th>📈 Percentage</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($marksRows as $marksItem): ?>
                        <?php
                        $marksTotal = (float)$marksItem["total_marks"];
                        $marksPercent = $marksTotal > 0
                            ? ((float)$marksItem["marks_obtained"] / $marksTotal) * 100
                            : 0;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$marksItem["subject_name"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars((string)$marksItem["marks_obtained"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars((string)$marksItem["total_marks"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= number_format($marksPercent, 2) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <p class="marks-empty">No subject marks available yet.</p>

    <?php endif; ?>

</section>


<!-- Old static submission elements: kept hidden only so Dashboard.js
     keeps working without errors. Real data is shown in the section below. -->

<div hidden aria-hidden="true">

    <input type="text" id="submissionSearch">

    <select id="statusFilter">
        <option value="all">All</option>
        <option value="graded">Graded</option>
        <option value="submitted">Submitted</option>
        <option value="review">Review</option>
    </select>

    <select id="sortFilter">
        <option value="default">Sort</option>
        <option value="subjectAsc">Subject A - Z</option>
        <option value="subjectDesc">Subject Z - A</option>
        <option value="dateAsc">Date Oldest - Newest</option>
        <option value="dateDesc">Date Newest - Oldest</option>
    </select>

    <div id="loadingSubmissions"></div>

    <table class="details-table">
        <tbody id="submissionBody"></tbody>
    </table>

    <div id="noSubmission"></div>

    <div id="pagination"></div>

</div>


<!-- RECENT SUBMISSIONS (added by admin from assignments.php) -->

<section class="dashboard-box">

    <h3>📋 Recent Submission Details</h3>


    <!-- SEARCH AND FILTER -->

    <div class="submission-controls">

        <input
            type="text"
            id="subSearch"
            placeholder="🔍 Search subject or assignment..."
            aria-label="Search submissions">


        <select
            id="subStatus"
            aria-label="Filter submissions by status">

            <option value="all">
                📊 All Status
            </option>

            <option value="graded">
                ✓ Graded
            </option>

            <option value="submitted">
                ✓ Submitted
            </option>

            <option value="review">
                ⏳ Under Review
            </option>

        </select>


        <select
            id="subSort"
            aria-label="Sort submissions">

            <option value="default">
                ↕ Sort By
            </option>

            <option value="subjectAsc">
                Subject A - Z
            </option>

            <option value="subjectDesc">
                Subject Z - A
            </option>

            <option value="dateAsc">
                Date Oldest - Newest
            </option>

            <option value="dateDesc">
                Date Newest - Oldest
            </option>

        </select>

    </div>


    <div class="table-container">

        <table class="details-table">

            <thead>

                <tr>

                    <th>📚 Subject</th>

                    <th>📝 Assignment Title</th>

                    <th>📅 Submission Date</th>

                    <th>📊 Status</th>

                    <th>💬 Feedback</th>

                </tr>

            </thead>


            <tbody id="subBody">

            <?php foreach ($submissions as $sub): ?>

                <?php
                $subStatus = (string)$sub["status"];
                $subLabel = $submissionLabels[$subStatus] ?? $subStatus;

                if ($subStatus === "graded" && !empty($sub["grade"])) {
                    $subLabel .= " (" . $sub["grade"] . ")";
                }

                $subTimestamp = strtotime((string)$sub["submission_date"]);
                $subDateValue = $subTimestamp ? date("Y-m-d", $subTimestamp) : "";
                $subDateLabel = $subTimestamp ? date("d M Y", $subTimestamp) : "—";
                $subFeedback = trim((string)($sub["feedback"] ?? ""));
                ?>

                <tr
                    data-subject="<?= h(strtolower((string)$sub["subject_name"])) ?>"
                    data-title="<?= h(strtolower((string)$sub["assignment_title"])) ?>"
                    data-date="<?= h($subDateValue) ?>"
                    data-status="<?= h($subStatus) ?>">

                    <td data-label="📚 Subject"><?= h($sub["subject_name"]) ?></td>

                    <td data-label="📝 Assignment"><?= h($sub["assignment_title"]) ?></td>

                    <td data-label="📅 Date"><?= h($subDateLabel) ?></td>

                    <td data-label="📊 Status">
                        <span class="status <?= h($subStatus) ?>"><?= h($subLabel) ?></span>
                    </td>

                    <td data-label="💬 Feedback" class="feedback-cell">
                        <?= $subFeedback !== "" ? h($subFeedback) : "—" ?>
                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>


        <div id="subNoResult">
            🔍 No submissions found.
        </div>


        <div id="subPagination"></div>

    </div>

</section>


</main>


<!-- FOOTER -->

<footer>

    <p><b>Student Hub</b></p>

    <p>Learn • Explore • Grow</p>

    <p>&copy; 2026 Student Hub. All Rights Reserved.</p>

    <p>Designed & Developed by Heni Patel</p>

</footer>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

<script src="Dashboard.js"></script>


<script>
const closeLoginSuccess = document.getElementById("closeLoginSuccess");
const loginSuccessBanner = document.getElementById("loginSuccessBanner");

if (closeLoginSuccess && loginSuccessBanner) {
    closeLoginSuccess.addEventListener("click", function () {
        loginSuccessBanner.remove();
    });
}
</script>



<script>
/* Search / filter / sort / pagination for submissions added by admin */
(function () {
    const body = document.getElementById("subBody");

    if (!body) {
        return;
    }

    const rows = Array.from(body.querySelectorAll("tr[data-title]"));
    const searchInput = document.getElementById("subSearch");
    const statusSelect = document.getElementById("subStatus");
    const sortSelect = document.getElementById("subSort");
    const noResult = document.getElementById("subNoResult");
    const pagination = document.getElementById("subPagination");

    const perPage = 5;
    let currentPage = 1;

    function getRows() {
        const text = searchInput.value.trim().toLowerCase();
        const status = statusSelect.value;

        const list = rows.filter(function (row) {
            const matchText = text === "" ||
                row.dataset.subject.includes(text) ||
                row.dataset.title.includes(text);

            const matchStatus = status === "all" || row.dataset.status === status;

            return matchText && matchStatus;
        });

        const sort = sortSelect.value;

        list.sort(function (a, b) {
            if (sort === "subjectAsc") {
                return a.dataset.subject.localeCompare(b.dataset.subject);
            }
            if (sort === "subjectDesc") {
                return b.dataset.subject.localeCompare(a.dataset.subject);
            }
            if (sort === "dateAsc") {
                return a.dataset.date.localeCompare(b.dataset.date);
            }
            if (sort === "dateDesc") {
                return b.dataset.date.localeCompare(a.dataset.date);
            }
            return rows.indexOf(a) - rows.indexOf(b);
        });

        return list;
    }

    function makeButton(label, disabled, active, onClick) {
        const button = document.createElement("button");

        button.type = "button";
        button.className = "sub-page-button" + (active ? " active" : "");
        button.textContent = label;
        button.disabled = disabled;
        button.addEventListener("click", onClick);

        return button;
    }

    function render() {
        const list = getRows();
        const totalPages = Math.max(1, Math.ceil(list.length / perPage));

        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        rows.forEach(function (row) {
            row.style.display = "none";
        });

        const start = (currentPage - 1) * perPage;

        list.slice(start, start + perPage).forEach(function (row) {
            row.style.display = "";
            body.appendChild(row);
        });

        if (rows.length === 0) {
            noResult.textContent = "📭 No submissions have been added yet.";
            noResult.style.display = "block";
        } else if (list.length === 0) {
            noResult.textContent = "🔍 No submissions found.";
            noResult.style.display = "block";
        } else {
            noResult.style.display = "none";
        }

        pagination.innerHTML = "";

        if (list.length > perPage) {
            pagination.appendChild(makeButton("←", currentPage === 1, false, function () {
                currentPage--;
                render();
            }));

            for (let p = 1; p <= totalPages; p++) {
                pagination.appendChild(makeButton(String(p), false, p === currentPage, function () {
                    currentPage = p;
                    render();
                }));
            }

            pagination.appendChild(makeButton("→", currentPage === totalPages, false, function () {
                currentPage++;
                render();
            }));
        }
    }

    [searchInput, statusSelect, sortSelect].forEach(function (el) {
        el.addEventListener("input", function () {
            currentPage = 1;
            render();
        });
    });

    render();
})();
</script>

</body>

</html>