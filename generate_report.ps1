$ErrorActionPreference = 'Stop'
$root = 'C:\xampp\htdocs\CampusConnect'
$htmlPath = Join-Path $env:TEMP 'CampusConnect_Project_Report_Source.html'
$docxPath = Join-Path $root 'CampusConnect_Project_Report.docx'
$pdfPath = Join-Path $root 'CampusConnect_Project_Report.pdf'
$html = @'
<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
@page { size: A4; margin: 1in 0.9in 0.85in 1.1in; }
body { font-family: "Times New Roman", serif; font-size: 11pt; color: #202b3c; line-height: 1.35; }
h1 { font-size: 17pt; color: #17365d; text-align: center; margin: 0 0 18pt; }
h2 { font-size: 13pt; color: #17365d; margin: 18pt 0 7pt; }
h3 { font-size: 11.5pt; color: #24527a; margin: 12pt 0 5pt; }
p { text-align: justify; margin: 0 0 8pt; }
li { margin: 0 0 4pt; }
table { border-collapse: collapse; width: 100%; margin: 8pt 0 14pt; font-size: 10pt; }
th { background: #dce6f1; color: #17365d; font-weight: bold; }
th, td { border: 1px solid #8797a8; padding: 6pt; vertical-align: top; }
.cover { text-align: center; padding-top: 85pt; }
.cover p { text-align: center; }
.brand { color: #24527a; font-size: 29pt; font-weight: bold; letter-spacing: 1pt; }
.tagline { color: #5b6f82; font-size: 14pt; }
.rule { border-top: 2px solid #24527a; margin: 20pt 65pt; }
.meta { width: 80%; margin: 25pt auto; }
.meta td { border: none; padding: 5pt 8pt; font-size: 11pt; }
.chapter { page-break-before: always; }
.frontbreak { page-break-before: always; }
.callout { background: #eef4fa; border-left: 4px solid #24527a; padding: 9pt 12pt; margin: 10pt 0; }
.small { font-size: 9.5pt; color: #526170; }
</style></head><body>
<div class="cover">
<p class="brand">CampusConnect</p><p class="tagline">Campus Services Management Portal</p><div class="rule"></div>
<p style="font-size:21pt;color:#17365d;font-weight:bold;">PROJECT REPORT</p>
<p style="font-size:14pt;">Submitted in partial fulfilment of the requirements for the course</p>
<p style="font-size:16pt;font-weight:bold;color:#24527a;">System Analysis and Design</p><br><br>
<table class="meta">
<tr><td><b>Submitted by</b></td><td>[Student Name]</td></tr><tr><td><b>Student ID</b></td><td>[Student ID]</td></tr>
<tr><td><b>Semester / Section</b></td><td>[Semester / Section]</td></tr><tr><td><b>Department / University</b></td><td>[Department / University Name]</td></tr>
<tr><td><b>Submitted to</b></td><td>[Course Instructor]</td></tr><tr><td><b>Submission date</b></td><td>15 November 2026</td></tr>
</table><div class="rule"></div><p class="small">Prepared from the CampusConnect project source code and database definition.</p>
</div>
<div class="frontbreak"></div>
<h1>ABSTRACT</h1>
<p>CampusConnect is a web-based campus services portal that brings common university interactions into one role-aware application. It provides separate workspaces for students, teachers, and administrators. Students can maintain their profile, view published notices, submit and track complaints, request teacher appointments, and request laboratory bookings. Teachers can review appointment requests and read notices. Administrators manage notices, complaints, teacher accounts, laboratory availability, and booking requests.</p>
<p>The application is implemented in PHP and MySQL and is designed to run locally with Apache and MySQL through XAMPP. Its relational database stores accounts, role-specific profiles, departments, notices, complaints, appointments, laboratories, and bookings. The implementation uses prepared SQL statements, password hashing, role checks, CSRF tokens, output escaping, and profile-picture upload validation. This report documents the system context, requirements, workflows, database design, implementation, security considerations, deployment steps, and a practical test plan. The test cases are proposed verification steps and do not imply that formal testing has already been completed.</p>
<p><b>Keywords:</b> campus portal, student services, PHP, MySQL, role-based access, system analysis and design.</p>
<h2>REPORT GUIDE</h2><p>Chapter 1 introduces the project. Chapter 2 describes stakeholders, requirements, and feasibility. Chapter 3 explains workflows and use cases. Chapter 4 presents architecture, data, and security design. Chapter 5 summarizes implementation. Chapter 6 provides a verification plan and deployment instructions. Chapter 7 discusses limitations, future improvements, and the conclusion.</p>
<div class="frontbreak"></div>
<h1>TABLE OF CONTENTS</h1>
<table><tr><th>Section</th><th>Title</th></tr>
<tr><td>Abstract</td><td>Project overview and report guide</td></tr><tr><td>Chapter 1</td><td>Introduction</td></tr>
<tr><td>Chapter 2</td><td>System Analysis and Requirements</td></tr><tr><td>Chapter 3</td><td>System Design and Workflows</td></tr>
<tr><td>Chapter 4</td><td>Database, Architecture, and Security</td></tr><tr><td>Chapter 5</td><td>Implementation</td></tr>
<tr><td>Chapter 6</td><td>Testing and Deployment</td></tr><tr><td>Chapter 7</td><td>Limitations, Future Enhancements, and Conclusion</td></tr>
<tr><td>Appendices</td><td>Installation checklist and database entity summary</td></tr><tr><td>References</td><td>Project files and technical references</td></tr></table>
<p class="small">Page numbers are omitted so the contents remain correct when the editable document is personalized or repaginated.</p>

<div class="chapter"></div><h1>CHAPTER 1: INTRODUCTION</h1>
<h2>1.1 Background</h2><p>Students and staff interact with campus services such as announcements, student support, faculty appointments, and shared laboratory facilities. When these activities are handled through separate channels, users may have difficulty locating information and following requests. CampusConnect provides a single browser-based entry point for these selected services and presents functions according to user role.</p>
<h2>1.2 Problem Statement</h2><p>The project addresses the need for an organized digital workflow for routine campus communication and service requests. Students need a place to submit requests and review their status; teachers need a focused way to respond to appointment requests; administrators need tools to publish information and manage records. The project is limited to the features implemented in the supplied source code and does not claim to replace a full university information system.</p>
<h2>1.3 Objectives</h2><ul><li>Provide a unified portal with role-based access for students, teachers, and administrators.</li><li>Allow students to create an account, manage permitted profile information, and use campus services.</li><li>Support publication and display of campus notices.</li><li>Provide trackable complaint, appointment, and laboratory booking workflows.</li><li>Reduce scheduling conflicts by checking appointment slots and overlapping approved lab bookings.</li><li>Store related records in MySQL and apply baseline application security controls.</li></ul>
<h2>1.4 Scope</h2><p><b>Included:</b> public landing page, student registration and login, role-specific dashboards, student profile and profile-picture management, notices, complaint submission and administration, teacher appointment requests and review, laboratory booking requests and administration, and administrator management of teachers and lab availability.</p>
<p><b>Outside the current scope:</b> online payments, class registration, attendance, grades, email/SMS notifications, institutional single sign-on, multi-campus support, a dedicated mobile application, and integrations with external university systems.</p>
<h2>1.5 Intended Users</h2><table><tr><th>User</th><th>Primary needs</th></tr>
<tr><td>Student</td><td>Access notices, manage profile, submit complaints, request teacher appointments, and request lab bookings.</td></tr>
<tr><td>Teacher</td><td>Review appointment requests, approve/reject requests, mark approved appointments completed, and read notices.</td></tr>
<tr><td>Administrator</td><td>Publish/archive notices, respond to complaints, create teacher accounts, manage labs, and review booking requests.</td></tr></table>
<h2>1.6 Project Deliverables</h2><p>The supplied project consists of PHP application pages, shared PHP helpers, CSS and JavaScript assets, a MySQL schema script, database migration scripts, and a README with local setup guidance.</p>

<div class="chapter"></div><h1>CHAPTER 2: SYSTEM ANALYSIS AND REQUIREMENTS</h1>
<h2>2.1 Current Challenges and Proposed Solution</h2><p>In a fragmented process, a notice may be missed, a complaint may be hard to track, and a service request may require repeated follow-up. CampusConnect consolidates supported interactions into one web interface. Requests are recorded with status values and linked to the relevant student, teacher, administrator, or laboratory, making their history available to authorized users.</p>
<h2>2.2 Functional Requirements</h2><table><tr><th>ID</th><th>Requirement</th><th>Actor</th></tr>
<tr><td>FR-01</td><td>Register a student using identity, contact, department, semester, address, and password information.</td><td>Student</td></tr>
<tr><td>FR-02</td><td>Authenticate a user and direct them to a dashboard appropriate to their role.</td><td>All roles</td></tr>
<tr><td>FR-03</td><td>Restrict protected pages and actions to authenticated users with the required role.</td><td>System</td></tr>
<tr><td>FR-04</td><td>View and update permitted student profile fields; upload, replace, or remove a profile picture.</td><td>Student</td></tr>
<tr><td>FR-05</td><td>View published campus notices; allow administrators to publish and archive notices.</td><td>All / Admin</td></tr>
<tr><td>FR-06</td><td>Submit complaints and view status and administrator response.</td><td>Student</td></tr>
<tr><td>FR-07</td><td>Update complaint status and response.</td><td>Admin</td></tr>
<tr><td>FR-08</td><td>Request a teacher appointment for a valid date/time and track its status.</td><td>Student</td></tr>
<tr><td>FR-09</td><td>Approve, reject, or complete appointment requests while checking approved time-slot conflicts.</td><td>Teacher</td></tr>
<tr><td>FR-10</td><td>Request an available laboratory for a date and time range and view booking history.</td><td>Student</td></tr>
<tr><td>FR-11</td><td>Review booking status; reject approval if a lab is unavailable or overlaps another approved booking.</td><td>Admin</td></tr>
<tr><td>FR-12</td><td>Create teacher accounts and add laboratories or change laboratory availability.</td><td>Admin</td></tr>
<tr><td>FR-13</td><td>End the authenticated session using a CSRF-protected POST request.</td><td>All roles</td></tr></table>
<h2>2.3 Non-Functional Requirements</h2><table><tr><th>Category</th><th>Requirement</th></tr>
<tr><td>Security</td><td>Password hashing, prepared statements, output escaping, role checks, session protections, and CSRF verification.</td></tr>
<tr><td>Data integrity</td><td>Relational keys and transactions for multi-record account/profile creation and profile updates.</td></tr>
<tr><td>Usability</td><td>Clear forms, validation messages, status labels, dashboards, and role-specific navigation.</td></tr>
<tr><td>Compatibility</td><td>Run as a PHP/MySQL web application on a compatible local stack such as XAMPP.</td></tr>
<tr><td>Maintainability</td><td>Separate database access, shared helpers, styling, and application pages.</td></tr>
<tr><td>Availability</td><td>Local access depends on configured web server, MySQL service, and imported database.</td></tr></table>
<h2>2.4 Business Rules</h2><ul><li>Public registration creates student accounts only; teacher accounts are created by an administrator. Administrator setup is performed manually as described in README.md.</li><li>Appointment and lab requests require a current or future date and valid time values. Appointment requests check whether a pending or approved request already occupies the chosen teacher slot.</li><li>Laboratory bookings require an available lab, an end time after the start time, and no overlapping pending or approved request at submission. Approval also checks for overlapping approved bookings.</li><li>Complaint statuses are Pending, In Progress, Resolved, or Rejected. Appointment and booking statuses are Pending, Approved, Rejected, or Completed.</li><li>Profile-picture uploads accept JPEG, PNG, or WebP up to 5 MB and reject invalid MIME types or dimensions above 4096 by 4096 pixels.</li></ul>
<h2>2.5 Feasibility</h2><p><b>Technical:</b> The application uses commonly available PHP, MySQL, Apache, HTML, CSS, and JavaScript and documents a local XAMPP setup. <b>Operational:</b> Role-specific workflows map to familiar campus responsibilities. <b>Economic:</b> The source project uses a conventional open-source local stack; hosting, maintenance, backups, and institutional deployment costs are outside this source-level assessment. <b>Schedule:</b> The delivered code contains the described core modules; formal delivery dates and effort estimates were not included in the repository.</p>

<div class="chapter"></div><h1>CHAPTER 3: SYSTEM DESIGN AND WORKFLOWS</h1>
<h2>3.1 System Context</h2><p>CampusConnect is used through a web browser. Requests are sent to PHP on a web server. The application validates inputs, checks the session and role, reads or updates MySQL, and returns rendered HTML. CSS and browser-side JavaScript provide presentation behavior.</p>
<p class="callout"><b>Context flow:</b> Student / Teacher / Administrator → Web Browser → Apache + PHP Application → MySQL Database → Resulting page or status message → User.</p>
<h2>3.2 Use-Case Summary</h2><table><tr><th>Actor</th><th>Use cases</th></tr>
<tr><td>Guest</td><td>View public home page; register as a student; sign in.</td></tr>
<tr><td>Student</td><td>View dashboard and notices; edit profile; manage profile picture; submit/track complaint; request/track appointment; request/track lab booking; sign out.</td></tr>
<tr><td>Teacher</td><td>View dashboard and notices; review appointment queue; approve, reject, or complete an appointment; sign out.</td></tr>
<tr><td>Administrator</td><td>Publish/archive notices; update complaints and responses; create teacher account; create/manage laboratories; review and update lab booking statuses; sign out.</td></tr></table>
<h2>3.3 Student Registration and Authentication</h2><ol><li>The guest fills in the student registration form.</li><li>The server validates required fields, email format, password length and confirmation, and department selection.</li><li>The password is hashed. A database transaction creates the user and student profile records.</li><li>After successful registration, the user is directed to sign in.</li><li>At login, the password is verified against the stored hash. The session identifier is regenerated and role information is stored in the session.</li><li>Role-aware routing directs the user to the student, teacher, or administrator dashboard.</li></ol>
<h2>3.4 Complaint Workflow</h2><p>A student submits a subject and description. The complaint is stored with Pending status. The administrator reviews the record and may set its status and provide a response. The student sees the latest status and any response in the complaint history.</p>
<h2>3.5 Appointment Workflow</h2><p>A student selects a teacher, date, time, and purpose. The application validates the request and checks the teacher's pending/approved time slot. The teacher may approve or reject it; an approved request can later be marked completed. Before approval, the teacher dashboard checks that no other appointment is already approved for the same date and time.</p>
<h2>3.6 Laboratory Booking Workflow</h2><p>A student selects an available lab and requests a date/time range with a purpose. The application validates the date and interval and checks overlapping pending or approved requests for that lab. The administrator can update the status. Approval is blocked if the laboratory is unavailable or another approved booking overlaps.</p>
<h2>3.7 Notice Workflow</h2><p>An administrator publishes a notice with a title and description. Published notices appear on authenticated notice pages and recent-notice dashboard sections. Administrators can archive a notice and later publish it again.</p>
<h2>3.8 Navigation and User Interface</h2><p>The public landing page introduces the service and links to login and registration. Shared dashboard helpers render role-specific navigation, page heading, account identity, profile avatar, and theme toggle. Student, teacher, and administrator pages expose relevant navigation. CSS provides visual styling; JavaScript supports theme preference and profile-picture preview.</p>

<div class="chapter"></div><h1>CHAPTER 4: DATABASE, ARCHITECTURE, AND SECURITY</h1>
<h2>4.1 Architecture</h2><table><tr><th>Layer</th><th>Components</th><th>Responsibility</th></tr>
<tr><td>Presentation</td><td>HTML generated by PHP, CSS, JavaScript</td><td>Forms, dashboards, navigation, validation feedback, and responsive layout.</td></tr>
<tr><td>Application</td><td>PHP pages and shared helpers</td><td>Authentication, authorization, validation, workflow rules, and database operations.</td></tr>
<tr><td>Data</td><td>MySQL database campusconnect_db</td><td>Persistent user profiles, service requests, notices, laboratories, and relationships.</td></tr>
<tr><td>Runtime</td><td>Apache/PHP and MySQL, locally via XAMPP</td><td>Serve the application and provide database connectivity.</td></tr></table>
<h2>4.2 Data Model</h2><p>The database is relational. A user account has one role and can have a role-specific student, teacher, or administrator record. Students and teachers belong to a department. Students create complaints, appointments, and lab bookings. Teachers receive appointments. Administrators author notices. Lab bookings refer to a laboratory.</p>
<table><tr><th>Table</th><th>Key data</th><th>Relationships / purpose</th></tr>
<tr><td>departments</td><td>department_id, department_name</td><td>Lookup referenced by student and teacher profiles.</td></tr>
<tr><td>users</td><td>user_id, name, email, password hash, role, created_at</td><td>Central account identity; email is unique.</td></tr>
<tr><td>students</td><td>student_id, user_id, department_id, phone, semester, address, profile_picture</td><td>One student profile per user; cascades when its user is deleted.</td></tr>
<tr><td>teachers</td><td>teacher_id, user_id, department_id, phone, designation, room_no</td><td>One teacher profile per user; department affiliation.</td></tr>
<tr><td>admins</td><td>admin_id, user_id</td><td>Administrator profile associated with a user.</td></tr>
<tr><td>notices</td><td>notice_id, admin_id, title, description, publish_date, status</td><td>Notice authored by an administrator.</td></tr>
<tr><td>complaints</td><td>complaint_id, student_id, subject, description, date, status, response</td><td>Student request with status index and optional administrator response.</td></tr>
<tr><td>appointments</td><td>appointment_id, student_id, teacher_id, date, time, purpose, status</td><td>Links student and teacher; indexed for teacher/time/status lookup.</td></tr>
<tr><td>labs</td><td>lab_id, lab_name, location, capacity, availability_status</td><td>Laboratory catalogue and availability.</td></tr>
<tr><td>lab_bookings</td><td>booking_id, student_id, lab_id, date, start_time, end_time, purpose, status</td><td>Student reservation request; indexed by lab/time/status.</td></tr></table>
<h2>4.3 Data Integrity and Status Values</h2><p>Primary keys identify records. Foreign keys connect role profiles and service records to their owners. Several relationships cascade deletion of dependent student/teacher data; notices depend on an administrator record. Unique constraints protect email addresses, user-to-profile links, department names, and laboratory names. Registration and profile updates use transactions where multiple related records change together.</p>
<table><tr><th>Workflow</th><th>Statuses</th></tr><tr><td>Complaint</td><td>Pending, In Progress, Resolved, Rejected</td></tr><tr><td>Appointment</td><td>Pending, Approved, Rejected, Completed</td></tr><tr><td>Lab booking</td><td>Pending, Approved, Rejected, Completed</td></tr><tr><td>Notice</td><td>Published, Archived</td></tr><tr><td>Laboratory</td><td>Available, Unavailable</td></tr></table>
<h2>4.4 Security Controls Observed in the Source</h2><ul><li><b>Authentication:</b> Passwords use PHP password hashing and verification. The session ID is regenerated after successful login.</li><li><b>Authorization:</b> Shared helpers require authentication and an exact role for protected dashboards.</li><li><b>CSRF protection:</b> Forms receive a session token and state-changing requests verify it using a constant-time comparison.</li><li><b>SQL injection prevention:</b> User-supplied values use prepared MySQL statements in application workflows.</li><li><b>Output encoding:</b> Dynamic values are escaped before being written to HTML.</li><li><b>Session cookies:</b> HTTP-only and SameSite=Lax options are configured; Secure is enabled when the request uses HTTPS.</li><li><b>Upload validation:</b> Profile uploads check upload status, size, MIME/image type, and dimensions, use random filenames, and limit accepted extensions.</li><li><b>Request validation:</b> Dates, times, status values, IDs, and field limits are checked in relevant workflows.</li></ul>
<p>These observations describe controls present in the source, not a penetration-test result or a guarantee of security. Production use should additionally configure HTTPS, secrets and database credentials appropriately, backups, least-privilege database access, server-side error handling, and ongoing security testing.</p>
<h2>4.5 Data Flow Summary</h2><p>For a form submission, the server checks the request method and CSRF token, validates submitted values and user role, then runs a prepared query or transaction against MySQL. It reports success or a validation error and renders the updated status. A user can only access records exposed by the page queries and role checks in the current implementation.</p>

<div class="chapter"></div><h1>CHAPTER 5: IMPLEMENTATION</h1>
<h2>5.1 Technology Stack</h2><table><tr><th>Technology</th><th>Use in the project</th></tr>
<tr><td>PHP</td><td>Server-side page rendering, session management, validation, and database workflows.</td></tr><tr><td>MySQL / MySQLi</td><td>Relational persistence and prepared statements; utf8mb4 connection encoding.</td></tr>
<tr><td>HTML</td><td>Page structure, service forms, navigation, and dashboards.</td></tr><tr><td>CSS</td><td>Public-site and dashboard styling, including responsive presentation.</td></tr>
<tr><td>JavaScript</td><td>Client-side theme preference and profile-picture preview.</td></tr><tr><td>XAMPP</td><td>Documented local Apache and MySQL runtime.</td></tr></table>
<h2>5.2 Main Application Components</h2><table><tr><th>Component</th><th>Implementation role</th></tr>
<tr><td>index.php</td><td>Public landing page introducing portal services and account access.</td></tr>
<tr><td>register.php / login.php / logout.php</td><td>Student registration, credential verification, session initialization, and CSRF-protected sign-out.</td></tr>
<tr><td>student_dashboard.php</td><td>Student overview with profile information, request counts, and recent published notices.</td></tr>
<tr><td>student_profile.php</td><td>Profile updates and profile-picture upload, replacement, or removal.</td></tr>
<tr><td>appointments.php / teacher_dashboard.php</td><td>Student appointment requests and teacher review/status updates.</td></tr>
<tr><td>complaints.php</td><td>Student complaint submission and personal complaint history.</td></tr>
<tr><td>labs.php</td><td>Student lab booking requests and booking history.</td></tr>
<tr><td>admin_dashboard.php</td><td>Administrative notice, complaint, teacher, lab, and booking management.</td></tr>
<tr><td>notices.php</td><td>Display of currently published notices to authenticated users.</td></tr>
<tr><td>includes/helpers.php</td><td>Session, escaping, CSRF, login/role checks, role routing, and shared dashboard rendering.</td></tr>
<tr><td>includes/db.php</td><td>MySQLi connection configuration and utf8mb4 setup.</td></tr>
<tr><td>database.sql / database_migrations/</td><td>Initial schema, sample department/lab data, and incremental schema updates.</td></tr></table>
<h2>5.3 Database Setup and Migrations</h2><p>The initial schema is defined in <i>database.sql</i>, which creates the database, tables, relations, indexes, and initial departments/labs. Two migration scripts support changes to an existing installation: one adds the student profile-picture field, and another expands the laboratory booking purpose field. README.md explains which migrations may be needed when upgrading.</p>
<h2>5.4 Configuration</h2><p>Database host, username, password, and database name are defined in <i>includes/db.php</i>. Supplied local defaults are localhost, root, an empty password, and campusconnect_db. These are local-development defaults and should be replaced with deployment-appropriate credentials before production use. README.md describes creating an initial administrator account manually; public administrator registration is intentionally not provided.</p>
<h2>5.5 Interface and Accessibility Notes</h2><p>The UI includes labeled form controls, visible validation messages, headings, status indicators, and navigation links. The landing page and dashboard use distinct visual themes, and dashboard code includes a light/dark theme toggle. Before production, keyboard-only navigation, contrast, screen-reader announcements, image alternatives, mobile breakpoints, and browser compatibility should be checked systematically.</p>

<div class="chapter"></div><h1>CHAPTER 6: TESTING AND DEPLOYMENT</h1>
<h2>6.1 Verification Approach</h2><p>The following test cases are derived from implemented workflows and are intended for execution in a configured test database. Record actual results, test data, date, environment, and defects when tests are run. This report does not claim these cases have been executed.</p>
<table><tr><th>ID</th><th>Test scenario</th><th>Expected result</th></tr>
<tr><td>TC-01</td><td>Register a student with valid unique data and matching password.</td><td>User and student profile are created; user is directed to login.</td></tr>
<tr><td>TC-02</td><td>Submit registration with invalid email, missing field, short password, mismatch, or duplicate email.</td><td>Registration is rejected with a suitable message and no partial account record.</td></tr>
<tr><td>TC-03</td><td>Sign in with a valid account for each role.</td><td>Session is established and user is routed to the corresponding dashboard.</td></tr>
<tr><td>TC-04</td><td>Open a protected page while logged out or attempt another role's dashboard.</td><td>Unauthenticated access redirects to login; wrong-role access receives an authorization response.</td></tr>
<tr><td>TC-05</td><td>Submit a complaint and update it from the administrator dashboard.</td><td>Complaint is stored, status/response are saved, and the student sees updated values.</td></tr>
<tr><td>TC-06</td><td>Request an appointment in a valid future slot, then attempt a conflicting slot.</td><td>First request is recorded; duplicate pending/approved slot request is rejected.</td></tr>
<tr><td>TC-07</td><td>Approve an appointment when another is already approved for that teacher/time.</td><td>Conflicting approval is prevented.</td></tr>
<tr><td>TC-08</td><td>Request an available lab for a valid interval and attempt an overlapping interval.</td><td>Valid request is recorded; overlapping request is rejected.</td></tr>
<tr><td>TC-09</td><td>Approve an overlapping lab booking or a booking for an unavailable lab.</td><td>Approval is prevented with an explanatory validation message.</td></tr>
<tr><td>TC-10</td><td>Upload supported and unsupported profile-picture formats, oversized images, and oversized dimensions.</td><td>Only valid JPEG/PNG/WebP within configured limits is accepted.</td></tr>
<tr><td>TC-11</td><td>Submit a state-changing form without a valid CSRF token.</td><td>Request is rejected with an invalid-submission response.</td></tr>
<tr><td>TC-12</td><td>Publish, archive, and republish a notice.</td><td>Published notices are visible; archived notices are absent from published-only views.</td></tr>
<tr><td>TC-13</td><td>Sign out using the dashboard form, then revisit a protected route.</td><td>Session ends and subsequent protected access requires login.</td></tr></table>
<h2>6.2 Deployment Prerequisites</h2><ul><li>Windows with XAMPP or equivalent PHP/MySQL environment.</li><li>Apache and MySQL services enabled.</li><li>PHP MySQLi extension available.</li><li>Database initialized from the project SQL file and any required migrations.</li><li>Correct connection settings in includes/db.php.</li></ul>
<h2>6.3 Local Installation Procedure</h2><ol><li>Place CampusConnect under the XAMPP htdocs directory.</li><li>Start Apache and MySQL from the XAMPP Control Panel.</li><li>Import database.sql using phpMyAdmin or the MySQL command line.</li><li>If upgrading an older database, apply relevant migrations documented in README.md.</li><li>Update database credentials in includes/db.php if they differ from local defaults.</li><li>Create the first administrator using the procedure in README.md; choose a unique, strong password and share credentials securely.</li><li>Open http://localhost/CampusConnect/ and register a student account to verify setup.</li><li>Run the test cases in section 6.1 and record actual outcomes before presenting a tested release.</li></ol>
<h2>6.4 Operational Considerations</h2><p>Back up the MySQL database and uploaded profile pictures together. Restrict access to configuration files and administrator credentials. Use HTTPS and a production-grade database account in a deployed environment. Configure PHP upload limits to support the intended image-size limit. Monitor application/server logs and verify upload directories are writable only as required.</p>

<div class="chapter"></div><h1>CHAPTER 7: LIMITATIONS, FUTURE ENHANCEMENTS, AND CONCLUSION</h1>
<h2>7.1 Current Limitations</h2><ul><li>The system is a focused campus-services project and does not provide complete university functions such as course registration, grading, attendance, or finance.</li><li>Student registration is self-service, while teacher and administrator account provisioning is managed separately.</li><li>The code supports local configuration but does not include deployment automation, institutional SSO, or external notification integrations.</li><li>Service requests are tracked within the portal; email and SMS notifications are not present in the supplied implementation.</li><li>Concurrency behavior should be tested under simultaneous booking submissions and approvals before high-volume deployment.</li><li>Formal automated test suites, test execution evidence, production usage data, and performance measurements were not supplied.</li></ul>
<h2>7.2 Recommended Future Enhancements</h2><ul><li>Add email/in-app notification delivery for status changes and newly published notices.</li><li>Introduce an audit log for administrative changes and request status transitions.</li><li>Provide advanced search, filtering, pagination, and downloadable reports for administrators.</li><li>Add configurable appointment office hours, campus holidays, and stronger concurrent booking protection.</li><li>Develop automated unit/integration tests and a continuous integration workflow.</li><li>Improve production configuration with environment-based secrets, HTTPS enforcement, backup/restore procedures, and monitoring.</li><li>Expand accessibility and responsive design testing and consider localized UI text.</li><li>Evaluate institutional SSO, additional user lifecycle controls, and privacy/retention policies if adopted.</li></ul>
<h2>7.3 Conclusion</h2><p>CampusConnect brings several recurring campus interactions into a single role-aware PHP/MySQL portal. Its implemented workflows cover notices, student complaints, teacher appointments, laboratory booking, and profile management. The database structure links users to service records, while validation, status tracking, role checks, and conflict checks support orderly processing. The project provides a practical foundation for further analysis, testing, and deployment. Before institutional use, it should undergo full functional and security testing, deployment hardening, operational planning, and review by its intended stakeholders.</p>

<div class="chapter"></div><h1>APPENDIX A: LOCAL INSTALLATION CHECKLIST</h1>
<table><tr><th>Step</th><th>Verification</th></tr><tr><td>1. Project files</td><td>CampusConnect is present under the web server document root.</td></tr>
<tr><td>2. Services</td><td>Apache and MySQL start successfully.</td></tr><tr><td>3. Database</td><td>database.sql imported and campusconnect_db created.</td></tr>
<tr><td>4. Existing database</td><td>Applicable migrations have been reviewed and executed once.</td></tr><tr><td>5. Connection</td><td>includes/db.php matches local MySQL host and credentials.</td></tr>
<tr><td>6. Administrator</td><td>Initial administrator user and admins profile are provisioned as documented.</td></tr><tr><td>7. Basic access</td><td>Home page loads, registration succeeds, and login routes to the student dashboard.</td></tr>
<tr><td>8. Operational readiness</td><td>Backup, HTTPS, upload limits, logging, and credential handling are reviewed for the target environment.</td></tr></table>
<h1>APPENDIX B: DATABASE ENTITY SUMMARY</h1><p>The initial database contains ten principal tables: departments, users, students, teachers, admins, notices, complaints, appointments, labs, and lab_bookings. The user table is the account identity source. Role-specific profile tables extend that identity. Service tables reference the student, teacher, administrator, or laboratory involved. See database.sql for exact column definitions, constraints, indexes, defaults, and seeded records.</p>
<h2>Relationship Overview</h2><ul><li>departments 1-to-many students</li><li>departments 1-to-many teachers</li><li>users 1-to-0/1 students, teachers, or admins depending on role</li><li>students 1-to-many complaints, appointments, and lab_bookings</li><li>teachers 1-to-many appointments</li><li>admins 1-to-many notices</li><li>labs 1-to-many lab_bookings</li></ul>
<h1>REFERENCES</h1><ol><li>CampusConnect source project: PHP application pages, helpers, database connection, assets, and SQL scripts.</li><li>CampusConnect README.md: local setup, administrator provisioning, modules, and migration guidance.</li><li>PHP Manual, Password Hashing: https://www.php.net/manual/en/book.password.php</li><li>PHP Manual, MySQLi Prepared Statements: https://www.php.net/manual/en/mysqli.quickstart.prepared-statements.php</li><li>MySQL Reference Manual, InnoDB Foreign Key Constraints: https://dev.mysql.com/doc/refman/8.4/en/innodb-foreign-key-constraints.html</li></ol>
<p class="small">Project-specific descriptions are based on the source files and schema included with the supplied CampusConnect workspace. External references are included for underlying PHP/MySQL concepts.</p>
</body></html>
'@
Set-Content -LiteralPath $htmlPath -Value $html -Encoding UTF8
$word = $null
$doc = $null
try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    $doc = $word.Documents.Open($htmlPath, $false, $true)
    $doc.PageSetup.PaperSize = 7
    $doc.PageSetup.TopMargin = $word.InchesToPoints(0.85)
    $doc.PageSetup.BottomMargin = $word.InchesToPoints(0.8)
    $doc.PageSetup.LeftMargin = $word.InchesToPoints(1.0)
    $doc.PageSetup.RightMargin = $word.InchesToPoints(0.9)
    $doc.Content.Font.Name = 'Times New Roman'
    $doc.Content.Font.Size = 11
    foreach ($section in $doc.Sections) {
        $footer = $section.Footers.Item(1)
        $footer.Range.Text = 'CampusConnect Project Report  |  Page '
        $footer.Range.ParagraphFormat.Alignment = 1
        $footer.Range.Collapse(0)
        $null = $footer.Range.Fields.Add($footer.Range, 33)
    }
    $doc.SaveAs2($docxPath, 16)
    $doc.ExportAsFixedFormat($pdfPath, 17)
    $pageCount = $doc.ComputeStatistics(2)
    $doc.Close(0)
    $doc = $null
    $word.Quit()
    $word = $null
    Remove-Item -LiteralPath $htmlPath -Force
    Get-Item $docxPath, $pdfPath | Select-Object Name, FullName, Length
    "Pages: $pageCount"
} finally {
    if ($doc -ne $null) { $doc.Close(0) }
    if ($word -ne $null) { $word.Quit() }
    if (Test-Path -LiteralPath $htmlPath) { Remove-Item -LiteralPath $htmlPath -Force }
}
