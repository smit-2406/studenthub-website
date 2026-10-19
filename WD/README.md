# CHARUSAT - ITUE203: Web Development Frameworks
## Semester 3 (2026-27 ODD) - Faculty of Technology and Engineering (FTE)
### Project: StudentHub Portal (Completed Practicals 1 through 15)

---

## 📋 Table of Completed Practicals

| Practical | Title | Core Technology / Concept | Primary Files | Status |
| :--- | :--- | :--- | :--- | :--- |
| **Practical 1** | Project Initiation, Requirement Analysis, Sitemap, Wireframe & GitHub Setup | Requirements, Sitemap, Wireframes, Git/GitHub | `sitemap_wireframe.drawio`, `README.md` | ✅ Complete |
| **Practical 2** | Semantic HTML5 Pages with Accessibility-Ready Structure | Semantic HTML5, Accessibility, ARIA, Breadcrumbs | `index.html`, `about.html`, `courses.html`, `contact.html`, `faq.html` | ✅ Complete |
| **Practical 3** | Responsive UI Design using CSS Grid, Flexbox & Mobile-First Layout | CSS3, Flexbox, CSS Grid, Responsive Design | `css/styles.css` | ✅ Complete |
| **Practical 4** | JavaScript DOM Manipulation, Event Handling & UI Interactivity | DOM, Events, Carousel Slider, Modal, Theme Switcher | `js/main.js` | ✅ Complete |
| **Practical 5** | Registration Form with Frontend Validation & User-Friendly Error Handling | Forms, Regex, Strength Meter, Canvas CAPTCHA | `register.html`, `js/main.js` | ✅ Complete |
| **Practical 6** | Rendering External JSON Data using Fetch API, Search & Filter | JSON, Fetch API, Search, Filter, Sort, Cache | `explorer.html`, `js/explorer.js`, `css/explorer.css`, `data/*.json` | ✅ Complete |
| **Practical 7** | PHP Form Processing with Server-Side Validation & CSV/JSON File Storage | PHP POST, Validation, Sanitization, CSV/JSON, CSRF | `submissions.php`, `css/submissions.css`, `private/submissions.*` | ✅ Complete |
| **Practical 8** | MySQL Schema Design, ER Model, PDO Connectivity & Prepared Statements | Normalized 3NF Schema, Foreign Keys, MySQLi & PDO | `database.sql`, `db.php` | ✅ Complete |
| **Practical 9** | Secure User Registration with Database Insert, Duplicate Email Check & Hashing | BCRYPT `password_hash()`, Duplicate Verification | `register.php`, `database.sql` | ✅ Complete |
| **Practical 10** | Secure Login/Logout with Sessions, Role-Based Access, Timeout & Remember-Me | Sessions, `password_verify()`, RBAC, Token Hash | `login.php`, `logout.php`, `auth.php`, `dashboard.php`, `admin.php` | ✅ Complete |
| **Practical 11** | Student Management CRUD with Search, Filter & MySQLi Statements | Prepared Statements CRUD, Filter, CSV Export | `students.php` | ✅ Complete |
| **Practical 12** | Event Management CRUD with Poster Upload & File Validation | Multipart Upload, MIME/Size Validation, Renaming | `manage-events.php`, `events.php`, `uploads/events/.htaccess` | ✅ Complete |
| **Practical 13** | PHP JSON API and AJAX/Fetch-Based CRUD without Page Reload | RESTful JSON API, Fetch API, Debounce Search, Modal | `api-students.php`, `ajax-students.php`, `api/students.php` | ✅ Complete |
| **Practical 14** | Admin Dashboard with Role-Based Access Control, Dynamic Menu & Audit Log | RBAC Dashboard, Metrics, Status Toggle, Chart | `admin.php`, `auth.php` | ✅ Complete |
| **Practical 15** | Final Integration, Testing, Documentation, GitHub Submission & Local Deployment | System Integration, Verification Suite, Report | `test-suite.php`, `test-report.html`, `database.sql` | ✅ Complete |

---

## 🏛️ System Architecture & Database ER Diagram (Practical 8, 14, 15)

```
       ┌────────────────────────┐
       │         users          │
       ├────────────────────────┤
       │ id (PK)                │
       │ username (UQ)          │
       │ email (UQ)             │
       │ password_hash          │
       │ full_name              │
       │ role [admin, student]  │
       │ is_active              │
       │ remember_token         │
       │ token_expiry           │
       │ last_login             │
       └───────────┬────────────┘
                   │ 1 : 1 (optional)
                   ▼
       ┌────────────────────────┐                  ┌────────────────────────┐
       │        students        │                  │    event_categories    │
       ├────────────────────────┤                  ├────────────────────────┤
       │ id (PK)                │                  │ id (PK)                │
       │ user_id (FK -> users)  │                  │ name (UQ)              │
       │ student_id (UQ)        │                  │ slug (UQ)              │
       │ full_name              │                  │ description            │
       │ email (UQ)             │                  └───────────┬────────────┘
       │ phone                  │                              │ 1 : N
       │ department             │                              ▼
       │ course                 │                  ┌────────────────────────┐
       │ semester               │                  │         events         │
       │ academic_year          │                  ├────────────────────────┤
       │ gender                 │                  │ id (PK)                │
       │ cgpa                   │                  │ category_id (FK)       │
       │ status [Active/Inact.] │                  │ title                  │
       └───────────┬────────────┘                  │ slug                   │
                   │                               │ description            │
                   │ 1 : N                         │ event_date, event_time │
                   ▼                               │ venue, max_participant│
       ┌────────────────────────┐                  │ poster_path (uploads/) │
       │     registrations      │                  │ status [Upcoming, etc.]│
       ├────────────────────────┤                  └───────────┬────────────┘
       │ id (PK)                │                              │
       │ event_id (FK -> events)│◀─────────────────────────────┘
       │ student_id (FK -> stud)│
       │ registration_date      │
       │ attendance_status      │
       │ notes                  │
       └────────────────────────┘
```

---

## 📂 Project Directory Structure

```
WD/
├── database.sql               # Complete MySQL schema & 21+ seeded records across all 6 tables
├── db.php                     # Dual MySQLi and PDO connection handler with auto-detection
├── auth.php                   # Authentication middleware, RBAC guards, session timeout & tokens
├── login.php                  # Secure authentication gate (Practical 10)
├── logout.php                 # Session destruction & cookie cleanup (Practical 10)
├── register.php               # Secure user registration with BCRYPT hashing (Practical 9)
├── dashboard.php              # Authenticated student dashboard (Practical 10)
├── admin.php                  # Administrator Portal: RBAC, Status Toggle & Analytics (Practical 14)
├── students.php               # Traditional Student CRUD with search & CSV export (Practical 11)
├── manage-events.php          # Admin Event CRUD with poster upload & validation (Practical 12)
├── events.php                 # Dynamic campus events catalog with poster rendering (Practical 12)
├── api-students.php           # RESTful PHP JSON API endpoint (GET, POST, PUT, DELETE) (Practical 13)
├── api/students.php           # REST alias endpoint for clean URL routing (Practical 13)
├── ajax-students.php          # Asynchronous single-page Fetch API CRUD interface (Practical 13)
├── test-suite.php             # Automated system diagnostic runner (10 verification checks) (Practical 15)
├── test-report.html           # Comprehensive test audit, CO matrix & deployment report (Practical 15)
├── submissions.php            # Offline CSV/JSON form processing & viewer (Practical 7)
├── explorer.html              # Fetch API live JSON explorer (Practical 6)
├── index.html                 # StudentHub landing page with carousel, theme switcher & FAQs
├── about.html                 # Campus background, vision, and leadership
├── courses.html               # Academic degree programs and syllabus directory
├── contact.html               # Campus contact details and inquiries
├── faq.html                   # Accessible collapsible accordions
├── feedback.html              # Student feedback rating forms
├── profile.html               # Student profile display page
├── settings.html              # Account preferences and notifications
├── sitemap_wireframe.drawio   # Sitemap & wireframes architecture
├── css/
│   ├── styles.css             # Responsive master styling (CSS Grid, Flexbox, Dark Mode)
│   ├── explorer.css           # Styling for live Fetch API data explorer
│   └── submissions.css        # Styling for CSV/JSON submission tables and cards
├── js/
│   ├── main.js                # Core JS (DOM events, carousel, modal, theme, canvas CAPTCHA)
│   └── explorer.js            # Fetch API logic, debounce search, pagination, dependent dropdowns
├── data/
│   ├── events.json            # 15 event seed records for Fetch API
│   ├── students.json          # 15 student seed records for Fetch API
│   └── faqs.json              # 15 FAQ seed records for Fetch API
├── uploads/
│   └── events/
│       └── .htaccess          # Security shield preventing script execution in upload dir
└── private/
    ├── .htaccess              # Blocks direct web access to sensitive storage
    ├── submissions.csv        # Secure server-side CSV storage with file locking
    └── submissions.json       # Secure server-side JSON storage with file locking
```

---

## 🔑 Default Accounts for Testing & Viva Evaluation

| Role | Username | Email | Password | Destination Dashboard |
| :--- | :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin@charusat.ac.in` | `Admin@12345` | `admin.php` |
| **Student** | `smitpopat` | `smit.popat@charusat.edu.in` | `Student@12345` | `dashboard.php` |
| **Student** | `priyashah` | `priya.shah@charusat.edu.in` | `Student@12345` | `dashboard.php` |

---

## 📌 Practical Summaries & Viva Questions (Practicals 1–15)

### Practical 1: Project Initiation, Requirement Analysis, Sitemap & GitHub Setup
- **Core Technology**: Requirement analysis, Sitemap architecture, Figma/Draw.io Wireframes, Git.
- **Viva Questions**:
  1. *What is a URL and what are its parts?* Scheme (`https://`), Subdomain (`www`), Domain (`studenthub.edu`), Port (`443`), Path (`/courses`), Query parameters (`?sem=3`), Fragment (`#curriculum`).
  2. *How is an HTML file processed by a browser?* Browser retrieves bytes $\to$ Tokenizes into HTML tokens $\to$ Builds DOM tree $\to$ Fetches CSS and builds CSSOM $\to$ Combines into Render Tree $\to$ Performs Layout $\to$ Paints pixels.

### Practical 2: Semantic HTML5 Pages with Accessibility-Ready Structure
- **Core Technology**: Semantic elements (`<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<aside>`, `<footer>`), ARIA attributes (`aria-current`, `aria-label`, `role`), Breadcrumbs, Skip-to-content links.
- **Viva Questions**:
  1. *Why use `<main>` instead of `<div>`?* Semantic elements give meaningful structure to search engines and assistive screen readers (WCAG 2.1 Level AA compliance).

### Practical 3: Responsive UI Design using CSS Grid and Flexbox
- **Core Technology**: CSS Flexbox (1D layouts for navigation and toolbars), CSS Grid (2D layouts for card grids and dashboards), Media queries (`@media (max-width: 768px)`).
- **Viva Questions**:
  1. *When do you choose CSS Grid over Flexbox?* Use Flexbox for one-dimensional alignments (either a row or a column). Use CSS Grid for two-dimensional component layouts (rows and columns simultaneously).

### Practical 4: JavaScript DOM Manipulation, Event Handling & UI Interactivity
- **Core Technology**: `document.querySelector`, `addEventListener`, carousel image slider, modal dialogs, `localStorage` theme switcher (Light/Dark mode).
- **Viva Questions**:
  1. *How does `localStorage` remember the user's theme across browser reloads?* `localStorage.setItem('theme', 'dark')` persists key-value strings in the client origin without expiration. On document load, early inline script checks `localStorage.getItem('theme')` and sets `data-theme="dark"` before DOM paint to prevent screen flashing.

### Practical 5: Registration Form with Frontend Validation & User-Friendly Error Handling
- **Core Technology**: HTML5 form constraints, Regular Expression pattern matching, real-time password strength meter, HTML5 Canvas dynamic CAPTCHA.
- **Viva Questions**:
  1. *Why is frontend validation alone not sufficient?* Client-side validation can be bypassed easily by disabling JavaScript or using tools like cURL/Postman. Backend validation is mandatory for security.

### Practical 6: Rendering External JSON Data using Fetch API, Search & Filter
- **Core Technology**: Asynchronous `fetch()`, Promises, `async/await`, Array methods (`map`, `filter`, `sort`, `slice`), dynamic pagination, `localStorage` response caching.
- **Implementation**: Located at `explorer.html`, `js/explorer.js`, and `css/explorer.css`.
- **Viva Questions**:
  1. *How is debounce implemented for live search?* A `setTimeout` timer clears previous pending invocations when new keystrokes occur, delaying API/filtering execution until 300ms after the user stops typing.

### Practical 7: PHP Form Processing with Server-Side Validation & CSV/JSON Storage
- **Core Technology**: `$_POST` handling, `filter_var()`, UTF-8 regex validation, CSRF tokens with `hash_equals()`, safe CSV formula escaping, advisory file locking with `flock($fp, LOCK_EX)`.
- **Implementation**: Located at `submissions.php`, `css/submissions.css`, and `private/submissions.*`.
- **Viva Questions**:
  1. *What is CSV Formula Injection?* If user input starts with `=`, `+`, `-`, or `@`, spreadsheet programs (Excel) execute them as formulas. Mitigated by prepending a single quote `'` to neutralize execution.

### Practical 8: MySQL Schema Design, ER Model, PDO Connectivity & Prepared Statements
- **Core Technology**: 3NF Normalized relational schema, Primary Keys, Foreign Keys (`ON DELETE CASCADE`), Indexes, MySQLi & PDO connectivity, database error handling.
- **Implementation**: Located at `database.sql` and `db.php`.
- **Viva Questions**:
  1. *What is the difference between MySQLi and PDO?* MySQLi is specific to MySQL/MariaDB databases and offers both procedural and OOP APIs. PDO (PHP Data Objects) is a database-abstraction layer that supports 12+ database drivers with consistent prepared statement APIs.
  2. *Why are Foreign Keys essential in relational databases?* They maintain Referential Integrity, preventing orphaned records and ensuring related records are deleted/updated predictably (`ON DELETE CASCADE` or `SET NULL`).

### Practical 9: Secure User Registration with Database Insert & Password Hashing
- **Core Technology**: BCRYPT password hashing using `password_hash($pass, PASSWORD_BCRYPT)`, duplicate email/username check via MySQLi prepared statements, atomic transactions (`begin_transaction`, `commit`, `rollback`), CSRF token check, security audit logs.
- **Implementation**: Located at `register.php`.
- **Viva Questions**:
  1. *Why should passwords never be hashed with MD5 or SHA-256?* MD5 and SHA-256 are fast cryptographic hashes designed for integrity, making them vulnerable to brute-force and GPU rainbow table attacks. BCRYPT includes a configurable work factor (cost) and automatic salt generation, making cracking computationally impractical.

### Practical 10: Secure Login/Logout with Sessions, Role-Based Access & Remember-Me Token
- **Core Technology**: `password_verify()`, `session_regenerate_id(true)`, RBAC route protection (`requireLogin()`, `requireRole('admin')`), session inactivity timeout (15 minutes), Remember-Me persistent authentication using cryptographically secure random bytes hashed with SHA-256 in the database.
- **Implementation**: Located at `login.php`, `logout.php`, `auth.php`, `dashboard.php`, and `admin.php`.
- **Viva Questions**:
  1. *What is Session Fixation and how is it prevented?* An attacker sets a known session ID before login. If the application keeps the same ID upon successful authentication, the attacker can hijack the session. Calling `session_regenerate_id(true)` replaces the session ID immediately after credential verification.
  2. *How is Remember-Me securely implemented without exposing credentials?* We generate `bin2hex(random_bytes(32))` and store its SHA-256 hash in `users.remember_token` with an expiration date. The client receives `userId:rawToken` in an HTTP-only, SameSite cookie. On return, the server verifies `hash_equals($user['remember_token'], hash('sha256', $rawToken))`.

### Practical 11: Student Management CRUD with Search and Filter
- **Core Technology**: Complete Create, Read, Update, Delete (CRUD) operations, multi-parameter prepared statement querying, dynamic search by name/email/ID, course/year filters, delete confirmation dialog with CSRF protection, soft-delete via status flag, and CSV export.
- **Implementation**: Located at `students.php` (seeded with 21+ records).
- **Viva Questions**:
  1. *What is the difference between soft delete and hard delete?* Hard delete executes `DELETE FROM table WHERE id = ?`, permanently removing the record from disk. Soft delete executes `UPDATE table SET status = 'Inactive' WHERE id = ?`, keeping the data intact for audit trails, reporting, and referential integrity while hiding it from active views.
  2. *How do prepared statements prevent SQL injection?* Parameter values are transmitted to the database engine separately from the SQL statement template. The database compiles the SQL structure first, treating user parameters strictly as literal data, preventing malicious SQL code injection.

### Practical 12: Event Management CRUD with Poster Upload and File Validation
- **Core Technology**: Admin CRUD for events, `multipart/form-data` uploads, rigorous file validation:
  - Allowed MIME type check via `finfo` / `mime_content_type`: `image/jpeg`, `image/png`, `image/webp`.
  - Extension whitelist: `.jpg`, `.jpeg`, `.png`, `.webp`.
  - File size threshold: maximum 2 MB.
  - Unique cryptographic renaming: `event_{timestamp}_{random_hex}.{ext}`.
  - Upload directory protection: `uploads/events/.htaccess` disabling script execution.
  - Automatic unlinking (deletion) of old posters on update or delete.
  - Category and status filters (`Upcoming`, `Ongoing`, `Completed`, `Draft`).
- **Implementation**: Located at `manage-events.php`, `events.php`, and `uploads/events/.htaccess`.
- **Viva Questions**:
  1. *Why is checking `$_FILES['poster']['type']` insecure?* `$_FILES['file']['type']` is sent directly by the client browser's HTTP request header and can be forged easily. Using PHP's `finfo_file()` examines the file's actual magic bytes to determine the true MIME type.
  2. *Why do we rename uploaded files and place an `.htaccess` in the uploads folder?* Renaming prevents directory traversal attacks and overwriting existing files. The `.htaccess` file blocks execution of any `.php`, `.cgi`, or executable files in case an attacker bypasses extension checks.

### Practical 13: PHP JSON API and AJAX/Fetch-Based CRUD without Page Reload
- **Core Technology**: Asynchronous RESTful JSON API (`GET`, `POST`, `PUT`, `DELETE`), HTTP status codes (200, 201, 400, 404, 500), centralized JSON response envelopes, JavaScript Fetch API client, live debounced search (300ms), modal editing, real-time DOM updates without page refresh, and interactive live Network Inspector panel.
- **Implementation**: Located at `api-students.php`, `api/students.php`, and `ajax-students.php`.
- **Viva Questions**:
  1. *How does the Fetch API handle asynchronous HTTP methods like PUT and DELETE?* Fetch accepts a configuration object `fetch(url, { method: 'PUT', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data) })`. PHP reads the payload via `file_get_contents('php://input')` and parses it into an associative array with `json_decode()`.
  2. *Why return HTTP status codes like 201, 400, and 404 instead of always returning 200?* HTTP status codes conform to the REST architectural style and standard HTTP conventions, enabling client browsers, monitoring systems, and API consumers to understand the outcome of the request at the protocol level without inspecting custom JSON payload fields.

### Practical 14: Admin Dashboard with Role-Based Access Control, Dynamic Menu & Audit Log
- **Core Technology**: Strict RBAC route protection (`requireRole('admin')`), dynamic role-aware navigation menus, live SQL aggregate counters (`COUNT(*)`), User Account Status Toggle (Active/Inactive) with CSRF protection, interactive SVG/Canvas monthly event registrations analytics chart, and real-time administrative audit trail (`audit_logs` table).
- **Implementation**: Located at `admin.php` and `auth.php`.
- **Viva Questions**:
  1. *How is Role-Based Access Control (RBAC) securely verified on the server?* After successful login, the user's role is stored in `$_SESSION['role']`. The `requireRole($role)` function checks whether `$_SESSION['role'] === $role`. If unauthorized, it terminates execution immediately with `http_response_code(403)` and displays an access-denied page, preventing unauthorized execution of administrative queries.
  2. *What is an Audit Trail and why is it essential?* An audit log tracks sensitive administrative activities (such as creating events, altering user account status, and deleting student profiles) along with the performing user's ID, action type, description, and client IP address. It provides non-repudiation, traceability, and forensic evidence for compliance.

### Practical 15: Final Integration, Testing, Documentation, GitHub Submission & Local Deployment
- **Core Technology**: Full-stack portal integration, automated diagnostic test runner (`test-suite.php`), comprehensive verification audit report (`test-report.html`), Course Outcome (CO1–CO5) achievement mapping, WCAG 2.1 AA accessibility audit, security hardening, and Apache Virtual Host configuration (`http://studenthub.local`).
- **Implementation**: Located at `test-suite.php`, `test-report.html`, `database.sql`, and `README.md`.
- **Viva Questions**:
  1. *What steps are required to configure an Apache Virtual Host in XAMPP?*
     1. Map `127.0.0.1 studenthub.local` in `C:\Windows\System32\drivers\etc\hosts`.
     2. In `C:\xampp\apache\conf\extra\httpd-vhosts.conf`, add a `<VirtualHost *:80>` block defining `ServerName studenthub.local` and `DocumentRoot "C:/Codding Pratice/WD"`.
     3. Restart Apache from the XAMPP Control Panel.
  2. *What is the difference between client-side and server-side validation in full-stack architecture?* Client-side validation (HTML5 attributes and JavaScript) enhances user experience by providing immediate feedback before transmission. Server-side validation (PHP filter functions, regex, and duplicate queries) is the authoritative security boundary that protects database integrity and prevents malicious bypass.

---

## 🚀 How to Run Locally with XAMPP

1. **Start Apache & MySQL** in the XAMPP Control Panel.
2. **Import Database Schema**:
   - Open phpMyAdmin (`http://localhost/phpmyadmin`) or MySQL terminal.
   - Run the contents of `database.sql` into database `studenthub_db`.
   - Alternatively, navigate to `http://localhost/WD/db.php` in your browser; it will auto-detect your port (3306 or 3307) and initialize all tables and seed records automatically!
3. **Access the Application Pages**:
   - Portal Home: `http://localhost/WD/index.html`
   - User Sign In: `http://localhost/WD/login.php`
   - User Registration: `http://localhost/WD/register.php`
   - Student Dashboard: `http://localhost/WD/dashboard.php`
   - Admin Operations Hub: `http://localhost/WD/admin.php`
   - Student CRUD (Standard): `http://localhost/WD/students.php`
   - AJAX Live API CRUD: `http://localhost/WD/ajax-students.php`
   - Event Management: `http://localhost/WD/manage-events.php`
   - Public Events Catalog: `http://localhost/WD/events.php`
   - Live Data Explorer: `http://localhost/WD/explorer.html`
   - Offline Submissions: `http://localhost/WD/submissions.php`
   - Automated Diagnostics: `http://localhost/WD/test-suite.php`
   - Final Test & Audit Report: `http://localhost/WD/test-report.html`

---

## 🌐 Apache Virtual Host Deployment (Practical 15 Intermediate Extension)

To access StudentHub via a custom local domain (`http://studenthub.local`):

1. **Edit Hosts File** (`C:\Windows\System32\drivers\etc\hosts`):
   ```text
   127.0.0.1    studenthub.local
   127.0.0.1    www.studenthub.local
   ```
2. **Add VirtualHost** in `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:
   ```apache
   <VirtualHost *:80>
       ServerAdmin admin@charusat.ac.in
       DocumentRoot "C:/Codding Pratice/WD"
       ServerName studenthub.local
       ServerAlias www.studenthub.local
       <Directory "C:/Codding Pratice/WD">
           Options Indexes FollowSymLinks MultiViews
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
3. Restart Apache in the XAMPP Control Panel and navigate to:
   **`http://studenthub.local`**
