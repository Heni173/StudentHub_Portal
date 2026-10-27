# 🎓 StudentHub Portal
### Your Campus. Your Academics. Your Student Hub.

<p align="center">
  <strong>Learn • Explore • Grow</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Project-StudentHub-005F73?style=for-the-badge" alt="StudentHub Portal">
  <img src="https://img.shields.io/badge/Frontend-HTML%20%7C%20CSS%20%7C%20JS-E34F26?style=for-the-badge" alt="Frontend">
  <img src="https://img.shields.io/badge/Backend-PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/Database-MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
</p>

---

## 📌 1. About the Project

**StudentHub Portal** is a web-based student information and campus management system developed to provide students with a centralized platform for accessing academic information, personal profiles, announcements, examination schedules, and college events.

The website provides separate access for students and administrators. Students can view their available academic information, while administrators manage the academic records, announcements, schedules, events, and other information through authorized administrative functionality.

The project integrates frontend technologies, PHP backend processing, and MySQL databases to provide an organized and interactive college portal.

### 🎯 Project Objectives

- Centralize student information and academic updates.
- Provide registration, login, and logout functionality.
- Separate student and administrator access.
- Display personalized student profile information.
- Make announcements, timetables, exam schedules, and marks accessible to students.
- Allow administrators to manage academic information.
- Provide event management and student feedback functionality.
- Prevent duplicate email registration.
- Maintain relevant activity records through audit logging.

---

## ✨ 2. Key Features

### 🏠 2.1 Home Page & Navigation

The Home Page is the main entry point to the StudentHub Portal.

- Integrated login and logout functionality.
- Navigation menu for the available website sections.
- Campus highlights and informational content.
- Notification interface.
- Light/dark theme controls, where implemented.
- Responsive navigation and page layout.

Students can access the student dashboard after successful authentication. Protected pages are controlled according to the user's assigned role.

### 👤 2.2 Student Registration & Authentication

The registration and authentication module manages user accounts and portal access.

- Student registration form.
- Input validation.
- Duplicate email prevention.
- Password hashing and verification.
- Login using registered credentials.
- Session-based authentication.
- Logout functionality.
- Student and administrator roles.
- Session timeout handling, where configured.

**Duplicate Email Protection:** If an email address already exists in the database, the registration process rejects another registration using that email.

### 🛡️ 2.3 Role-Based Access Control (RBAC)

StudentHub Portal separates student and administrator permissions to protect restricted pages and operations.

**Student access**
- Student Dashboard.
- Student Profile.
- Academic information assigned to the logged-in student.
- Student-facing Events page.
- Feedback and other permitted portal pages.

**Administrator access**
- Protected Admin Dashboard.
- Academic information management.
- Announcement and schedule management.
- Subject marks and submission information management, where available.
- Event creation, updating, and deletion.
- Student feedback review.
- Authorized activity and audit records.

#### Student–Admin Access Protection

- Student credentials cannot be used to access protected administrator pages.
- Administrator credentials cannot be used to access student-only pages when those pages enforce the student role.
- Role permissions are checked for protected pages and administrative operations.
- Directly entering a restricted page URL must not bypass server-side authorization.
- Unauthorized access is denied or redirected according to the application's implemented logic.

#### Access Control Matrix

| Functionality | Student | Admin |
|---|---|---|
| Student Dashboard | Allowed | Denied on student-only pages |
| Student Profile | Allowed | Denied on student-only pages |
| Admin Dashboard | Denied | Allowed |
| View academic information | Permitted information | Authorized access |
| Manage academic records | Not permitted | Authorized operations |
| View college events | Allowed | Allowed |
| Add, update, delete events | Not permitted | Allowed |
| Submit feedback | Allowed | Not applicable to the student workflow |
| Review student feedback | Not permitted | Allowed |

**Security principle:** Authentication verifies the user's identity, while authorization determines which pages and operations that user is permitted to access.

### 📊 2.4 Student Dashboard

The Dashboard displays important academic information for the logged-in student.

**Latest Announcements**
- Displays recent announcements published through the administrative workflow.

**College Timetable**
- Displays available college timetable information.

**Exam & Practical Schedule**
- Provides access to examination and practical schedule details maintained by the administrator.

**Subject Marks**
- Displays the logged-in student's available subject marks.

**Recent Submission Details**
- Displays available recent assignment or submission information.

Students can view the academic information provided to them but cannot directly modify protected academic records managed by the administrator.

### 🪪 2.5 Student Profile

The Profile page displays the available details associated with the logged-in student.

Profile information includes:

- Student details.
- Enrollment and account information.
- CGPA.
- Attendance.
- Other available academic information.

The displayed profile information corresponds to the student account that has logged in.

Students can view their own available information, while protected academic records remain under authorized administrative control.

### 📅 2.6 College Events Management

The Events page displays college events maintained by the administrator.

**Administrator functionalities**
- Add new events.
- Update existing events.
- Delete events.
- Maintain event details and status.
- Upload event posters, where supported.
- Apply poster file validation, where implemented.

**Student functionality**
- View the events displayed on the student-facing Events page.

**Integrated Event Updates:** When an administrator adds, updates, or deletes an event, the student-facing Events page reflects the saved changes through the connected database and implemented event display logic.

### 🏫 2.7 About Us

The About Us page provides information about the portal and its institutional context.

It helps visitors understand the purpose of StudentHub and provides relevant college information.

### 📞 2.8 Contact Us

The Contact Us page provides contact information for users who need to contact the institution or portal team.

### 💬 2.9 Student Feedback

The Feedback page provides a communication channel between students and administrators.

- Students can enter and submit feedback.
- Submitted feedback is sent through the application's configured feedback handling process.
- Administrators can review feedback received from students.

This functionality supports student suggestions, concerns, and communication with the administration.

### 📍 2.10 Location

The Location page provides location-related information about the college to help visitors identify the institution's location.

### 📋 2.11 Audit Logs

The `audit_logs` table supports the portal's activity-recording functionality.

Depending on the configured logging operations, audit logs can help maintain records of relevant activities for administrative monitoring and traceability.

Audit logging behavior depends on the actions that the application actually records.

---

## 🔄 3. Application Workflow

### Student Workflow

1. Open the StudentHub Home Page.
2. Register using valid details if an account is not already available.
3. Log in using the registered credentials.
4. Access the Dashboard to view announcements, timetable, examination and practical schedules, subject marks, and recent submission details.
5. Open Profile to view available personal and academic information, including CGPA and attendance.
6. Visit Events to view college events.
7. Use About Us, Contact Us, Feedback, and Location as required.
8. Log out when finished.

### Administrator Workflow

1. Log in using an authorized administrator account.
2. Access the protected Admin Dashboard.
3. Manage announcements and academic information through the available administrative modules.
4. Maintain timetables, exam and practical schedules, subject marks, and submission details where supported.
5. Add, update, or delete college events.
6. Review student feedback.
7. Access relevant activity records through the audit logging functionality.
8. Log out after completing administrative operations.

### Role-Based Access Workflow

```text
             StudentHub Portal
                     |
                 Login
                     |
             Verify Credentials
                     |
              Check User Role
                /         \
           Student         Admin
              |              |
        Student Pages    Admin Pages
              |              |
       View Own Data     Manage Records
              \              /
               Logout
```

Unauthorized users must not gain access to restricted pages by directly entering their URLs.

---

## 🛠️ 4. Technologies Used

| Technology | Purpose |
|---|---|
| HTML5 | Web page structure |
| CSS3 | Styling and page layouts |
| JavaScript | Client-side interactions |
| Bootstrap 5 | Responsive UI components |
| PHP | Backend processing and authentication |
| MySQL | Database storage |
| MySQLi / PDO | Database connectivity used by the relevant modules |
| XAMPP | Local development environment |
| phpMyAdmin | Database administration |
| Git & GitHub | Version control and project hosting |

---

## 🗄️ 5. Database Architecture

The combined SQL dump, `studenthub.sql`, contains three databases with a total of twelve tables.

### Database 1: `user_registration`

Stores user accounts and academic information.

| No. | Table | Purpose |
|---:|---|---|
| 1 | `academic_details` | Student academic details |
| 2 | `announcements` | Portal announcements |
| 3 | `audit_logs` | Activity and audit records |
| 4 | `exam_schedule` | Examination schedules |
| 5 | `student_assignments` | Assignment and submission-related information |
| 6 | `subject_marks` | Subject marks |
| 7 | `timetable` | College timetable |
| 8 | `users` | User accounts and roles |

### Database 2: `studenthub`

Stores student records and event registration information.

| No. | Table | Purpose |
|---:|---|---|
| 1 | `events` | Event names, dates, and venues |
| 2 | `registrations` | Student-event registration records |
| 3 | `students` | Student details and status |

### Database 3: `studenthub_events`

Stores event information used by the event management module.

| No. | Table | Purpose |
|---:|---|---|
| 1 | `events` | Event title, description, date, category, poster, and status |

### Database Summary

- **Total databases:** 3
- **Total tables:** 12
- **SQL dump:** `studenthub.sql`
- **Database server:** MySQL / MariaDB through XAMPP

**Note:** The `events` tables in `studenthub` and `studenthub_events` belong to different databases and have different structures.

---

## ⚙️ 6. System Requirements

Before running the project, ensure that the following are available:

- XAMPP with Apache and MySQL/MariaDB.
- A PHP version compatible with the project.
- Visual Studio Code or another code editor.
- A modern web browser.
- phpMyAdmin.
- StudentHub Portal source code.
- The `studenthub.sql` database dump.

---

## 🚀 7. Installation & Setup

### Step 1: Obtain the Source Code

Clone the repository:

```bash
git clone YOUR_GITHUB_REPOSITORY_URL
```

Alternatively, download the repository as a ZIP file and extract it.

### Step 2: Place the Project in XAMPP

Place the project in the existing XAMPP `htdocs` directory.

Example location:

```text
C:\xampp\htdocs\25CS061_studenthubPortal
```

Keep the original folder structure and filenames.

### Step 3: Start XAMPP

1. Open XAMPP Control Panel.
2. Start Apache.
3. Start MySQL.
4. Confirm that both services are running.

### Step 4: Import the Database

1. Open `http://localhost/phpmyadmin/`.
2. Select the Import tab.
3. Choose `studenthub.sql`.
4. Start the import.
5. Verify that all three databases and their tables are present.

**Important:** Back up existing databases before importing because the SQL dump may contain statements that drop existing tables.

### Step 5: Verify Database Connections

Open the existing PHP database connection files and verify:

- Database host.
- Database username.
- Database password.
- Database name for each module.
- Character set, where configured.

For a default local XAMPP installation, the host and username are commonly `localhost` and `root`, respectively, but use the settings configured on your system.

Never upload actual credentials to a public repository.

### Step 6: Run the Website

Open the project through localhost.

Example:

```text
http://localhost/25CS061_studenthubPortal/
```

If the project uses a specific entry page, open its actual filename and path.

### Step 7: Verify the Application

Test student registration, duplicate email rejection, login/logout, student dashboard, profile information, administrator access, event updates, feedback submission, and database connectivity.

---

## 🧪 8. Testing Checklist

The following scenarios should be tested before the final submission.

| No. | Test Scenario | Expected Result |
|---:|---|---|
| 1 | Open Home Page | Home page and navigation appear |
| 2 | Register a new email | Valid registration is processed |
| 3 | Register the same email again | Duplicate registration is rejected |
| 4 | Student login with valid credentials | Student authentication succeeds |
| 5 | Login with invalid credentials | Access is denied |
| 6 | Student opens Dashboard | Academic information is displayed |
| 7 | Student opens Profile | Logged-in student's available details are displayed |
| 8 | Student attempts to open Admin Dashboard | Access is denied |
| 9 | Admin attempts to open a student-only page | Access is denied when the student role is required |
| 10 | User enters a restricted URL directly | Server-side authorization blocks access |
| 11 | Admin updates academic information | Saved changes appear where connected |
| 12 | Admin adds, updates, or deletes an event | Student-facing Events page reflects changes |
| 13 | Student submits feedback | Feedback reaches the configured administrative workflow |
| 14 | User logs out | Session is terminated according to the implemented logic |
| 15 | Check audit logging | Configured activities are recorded |
| 16 | Import SQL dump | Required databases and tables are available |

Record the actual result of every test. Mark a test as passed only after verifying it.

---

## 🔐 9. Security & Privacy

Security measures to implement and verify include:

- **Password Security:** Use secure password hashing and password verification.
- **Duplicate Email Protection:** Validate existing email addresses before creating accounts.
- **Input Validation:** Validate input on both client and server sides.
- **SQL Injection Prevention:** Use prepared statements where applicable.
- **Session Security:** Protect authenticated sessions and handle logout and timeout.
- **Role-Based Authorization:** Verify permissions on protected pages and backend operations.
- **Student Data Protection:** Prevent students from modifying protected academic records.
- **File Upload Validation:** Validate poster file types and sizes where uploads are implemented.
- **Error Handling:** Avoid exposing database credentials or sensitive server information.
- **Data Privacy:** Do not publish real passwords, credentials, or unnecessary personal information.

**Important:** Hiding a navigation link is not sufficient security. The backend must verify the user's role before allowing access to protected pages and operations.

---

## ♿ 10. Accessibility & Responsive Design

The portal should be checked for:

- Responsive layouts on desktop, tablet, and mobile.
- Clear labels for form fields.
- Readable text and sufficient color contrast.
- Meaningful alternative text for informative images.
- Keyboard accessibility for interactive controls.
- Clear validation messages and error feedback.

---

## 📸 11. Screenshots & Demonstration

Capture genuine screenshots from the working website.

Recommended screenshots for the final practical report:

1. Home Page with navigation and login.
2. Student registration form.
3. Duplicate email validation.
4. Successful student login.
5. Student Dashboard showing academic information.
6. Student Profile showing available CGPA and attendance.
7. Admin Dashboard.
8. Admin academic information management.
9. Student attempting to access an Admin page and being denied.
10. Admin attempting to access a student-only page and being denied.
11. Event management and updated student-facing Events page.
12. Feedback submission and administrative review.
13. Audit logs, where applicable.
14. Databases and tables in phpMyAdmin.
15. Successful SQL import.
16. GitHub repository with source code and documentation.

Use screenshots from the actual application as evidence of functionality.

---

## 📁 12. Project Structure

The project is maintained in the existing folder:

```text
25CS061_studenthubPortal/
│
├── Home.html
├── Register.html
├── Login.html
├── Dashboard.html
├── Profile.html
├── Events.html
├── About.html
├── ContactUs.html
├── Feedback.html
├── Location.html
│
├── PHP backend files
├── Database connection files
├── Authentication and session files
├── Academic management files
├── Event management files
├── CSS, JavaScript, and image assets
│
├── uploads/
│   └── events/
│
├── studenthub.sql
└── README.md
```

This is a high-level overview. Actual filenames and folder locations may differ according to the existing implementation. Keep the original project structure and document the files actually present in the repository.

---

## 🎓 13. Learning Outcomes

This project demonstrates practical concepts in:

- Frontend and backend integration.
- PHP form handling and server-side validation.
- MySQL database design and connectivity.
- Registration and authentication.
- Duplicate email validation.
- Session management.
- Role-based access control.
- Academic information management.
- Event CRUD operations.
- Student feedback handling.
- Audit logging concepts.
- Git and GitHub project documentation.

---

## 🔮 14. Future Scope

Potential future improvements include:

- Email notifications for academic updates.
- Enhanced dashboard analytics.
- Advanced academic reports.
- Additional accessibility improvements.
- Automated testing for important workflows.
- Expanded audit reporting and administrative monitoring.

---

## 👩‍💻 15. Project Information

| Field | Details |
|---|---|
| Project Name | StudentHub Portal |
| Student Name | Heni Patel |
| Enrollment Number | 25CS061 |
| Department | Computer Science and Engineering |
| Institution | CHARUSAT |
| Submission Type | Final Practical Submission |

---

## 📦 16. Final Submission Checklist

- [ ] GitHub repository link is available.
- [ ] Integrated source code is uploaded.
- [ ] `studenthub.sql` is included.
- [ ] `README.md` contains project documentation and setup instructions.
- [ ] Test and Security Report is included.
- [ ] Actual project screenshots are included.
- [ ] Database import has been verified.
- [ ] Student and administrator workflows have been tested.
- [ ] Student-to-admin and admin-to-student access restrictions have been tested.
- [ ] No credentials or sensitive student data are exposed.

---

<p align="center">

### 🎓 StudentHub Portal

**Learn • Explore • Grow**

*One portal. Academic updates. Campus connections.*

</p>
