# CHARUSAT - ITUE203: Web Development Frameworks
## Semester 3 (2026-27 ODD) - Faculty of Technology and Engineering (FTE)
### Project: StudentHub Portal (Completed through Practical 7)

---

## 📋 Table of Completed Practicals

| Practical | Title | Core Technology / Concept | Status |
| :--- | :--- | :--- | :--- |
| **Practical 1** | Project Initiation, Requirement Analysis, Sitemap, Wireframe, and GitHub Setup | Requirements, Sitemap, Wireframes, Git/GitHub | ✅ Complete |
| **Practical 2** | Semantic HTML5 Pages with Accessibility-Ready Structure | Semantic HTML5, Accessibility, ARIA, Breadcrumbs | ✅ Complete |
| **Practical 3** | Responsive UI Design using CSS Grid, Flexbox, and Mobile-First Layout | CSS3, Flexbox, CSS Grid, Responsive Design | ✅ Complete |
| **Practical 4** | JavaScript DOM Manipulation, Event Handling, and UI Interactivity | DOM, Events, Carousel Slider, Modal, Theme Switcher | ✅ Complete |
| **Practical 5** | Registration Form with Frontend Validation & User-Friendly Error Handling | Forms, Regex, Strength Meter, Canvas CAPTCHA | ✅ Complete |
| **Practical 6** | Rendering External JSON Data using Fetch API, Search, Filter & Pagination | JSON, Fetch API, Search, Filter, Sort, Cache | ✅ Complete |
| **Practical 7** | PHP Form Processing with Server-Side Validation and CSV/JSON File Storage | PHP POST, Validation, Sanitization, CSV/JSON, CSRF | ✅ Complete |

---

## 📌 Practical 1: Project Initiation, Requirement Analysis, Sitemap, Wireframe, and GitHub Setup

### 1. Problem Definition & Scope
**StudentHub** is a central digital campus web portal designed to streamline communication, course management, student profiles, and administrative tracking for university students, faculty members, and administrators.

#### User Roles & Permissions:
1. **Student**: Access course resources, view academic progress, browse campus events, submit feedback, update profile details, and submit support tickets.
2. **Faculty**: Manage course syllabus, publish notices, track student assignments, and conduct office hours.
3. **Administrator**: Oversee platform user accounts, manage campus events, view audit logs, and monitor system performance.

---

### 2. Official StudentHub Sitemap Architecture

```
                                 [ ROOT: StudentHub Home ]
                                        index.html
                                             │
      ┌──────────────────┬───────────────────┼───────────────────┬──────────────────┐
      │                  │                   │                   │                  │
  [ About Us ]    [ Course Catalog ]   [ Events & Fests ]   [ FAQs & Help ]    [ Contact Us ]
  about.html        courses.html          events.html          faq.html          contact.html
      │                  │                   │                   │                  │
      └──────────────────┼───────────────────┼───────────────────┼──────────────────┘
                         │                   │                   │
                  [ User Auth Gate ]  [ Frameworks Labs ]  [ Student Portal ]
                  login / register     P6 / P7 (PHP)        dashboard.html
                         │                   │             ┌─────┼─────┐
                         ▼                   ▼             ▼     ▼     ▼
                  [ Profile Setup ]   [ Admin Portal ]  [Profile][Settings][Feedback]
```

---

### 3. Project Folder Structure

```
StudentHub/
├── sitemap_wireframe.drawio   # Draw.io XML file (Sitemap + Desktop/Tablet/Mobile Wireframes)
├── README.md                  # Complete Practicals 1–7 Documentation & Viva Guide
├── index.html                 # Page 1: Home Page (Carousel Slider, Dark Mode, Modal, FAQ)
├── about.html                 # Page 2: About Us (Campus history, vision, breadcrumb)
├── register.html              # Page 3: Registration (Regex validation, Strength Meter, Canvas CAPTCHA)
├── login.html                 # Page 4: User Login Page (Accessible inputs, breadcrumb)
├── dashboard.html             # Page 5: Student Dashboard (Academic stats, progress bars)
├── events.html                # Page 6: Campus Events & Activities (Slider, category grid)
├── faq.html                   # Page 7: Frequently Asked Questions (Collapsible accordions)
├── profile.html               # Page 8: Student Profile (Personal details, avatar banner)
├── contact.html               # Page 9: Contact & Support (Campus address, inquiry form)
├── admin.html                 # Page 10: Administrator Portal (User stats, system metrics)
├── feedback.html              # Page 11: Student Feedback Form (Rating inputs, comments)
├── courses.html               # Course Catalog (Degree programs, syllabus overview)
├── settings.html              # Account Settings (Preferences, security, notifications)
├── practical6.html            # Practical 6: Fetch API Data Explorer (Events, Students, FAQs)
├── practical7.php             # Practical 7: PHP Server-Side Form Processor (CSV & JSON storage)
├── css/
│   ├── styles.css             # Master Responsive CSS (Grid, Flexbox, Dark Theme, Transitions)
│   ├── practical6.css         # Practical 6 styling (Badges, pagination, filters)
│   └── practical7.css         # Practical 7 styling (Form switcher tabs, badges, tables)
├── js/
│   ├── main.js                # Core JS (DOM events, Slider, Modal, Theme, Regex, Canvas CAPTCHA)
│   └── practical6.js          # Practical 6 JS (Fetch API, debounce search, dependent dropdowns)
├── data/
│   ├── events.json            # 15 Campus Event records for Practical 6
│   ├── students.json          # 15 Student Directory records (with Country/State/City)
│   └── faqs.json              # 15 FAQ items for Practical 6
└── private/
    ├── .htaccess              # Apache rule blocking direct HTTP requests to CSV/JSON files
    ├── practical7_submissions.csv   # Server-side CSV record storage with flock
    └── practical7_submissions.json  # Server-side JSON record storage with flock
```

---

### 4. Practical 1: Key Questions, Analysis & Viva Answers

#### Q1: What is a URL and what are the parts of a URL?
- **URL (Uniform Resource Locator)**: A standardized web address that specifies the location of a resource on a computer network and the mechanism for retrieving it.
- **Syntax**: `https://www.studenthub.edu:443/courses/index.html?dept=cs&sem=3#curriculum`
  1. **Protocol / Scheme (`https://`)**: Rules governing communication and transport security (TLS/SSL).
  2. **Subdomain (`www`)**: Sub-level domain pointing to a specific cluster or service.
  3. **Domain Name (`studenthub.edu`)**: Unique registered human-readable network domain.
  4. **Port Number (`:443`)**: Network port on the host (Default 80 for HTTP, 443 for HTTPS).
  5. **Path (`/courses/index.html`)**: Physical or virtual filesystem resource route.
  6. **Query Parameters (`?dept=cs&sem=3`)**: Key-value pairs providing dynamic input to server or scripts.
  7. **Fragment / Anchor (`#curriculum`)**: Bookmark targeting an element ID directly on the rendered page without reloading.

#### Q2: How is an HTML file processed in a web browser?
1. **Network Fetch**: Browser sends HTTP GET and receives raw HTML byte streams.
2. **DOM Construction**: Tokenizer converts bytes &rarr; characters &rarr; tokens &rarr; nodes &rarr; **DOM (Document Object Model) Tree**.
3. **CSSOM Construction**: CSS bytes are parsed into tokens &rarr; style rules &rarr; **CSSOM (CSS Object Model) Tree**.
4. **Render Tree Creation**: Browser combines DOM and CSSOM, omitting hidden nodes (`display: none`).
5. **Layout (Reflow)**: Computes the precise geometry, coordinate position, and bounding box dimensions for every visible element.
6. **Paint**: Converts layout boxes into actual screen pixels (rasterization) across layered compositing steps.

#### Q3: How is page navigation flow managed among all HTML pages?
- Managed through relative hyperlinks within semantic `<header>` navigation menus, contextual `<nav aria-label="Breadcrumb">` paths, and structured `<footer>` site directories. Every page shares consistent link anchors, ensuring seamless bidirectional flow across modules.

#### Q4: How are GitHub commits maintained after each practical?
- Each practical follows structured atomic commits:
  ```bash
  git add .
  git commit -m "Practical X: <Short description of implemented feature>"
  git push origin main
  ```

---

## ♿ Practical 2: Semantic HTML5 Pages with Accessibility-Ready Structure

### Problem Definition:
Develop static HTML5 skeletons for at least 10 StudentHub pages such as Home, About, Register, Login, Dashboard, Events, Profile, Contact, Admin, FAQ, and Feedback. Use semantic tags and accessibility-friendly structure.

### Implemented Semantic & Accessibility Features:
- ✅ **11+ Semantic Pages Built**: `index.html`, `about.html`, `register.html`, `login.html`, `dashboard.html`, `events.html`, `profile.html`, `contact.html`, `admin.html`, `faq.html`, `feedback.html`, `courses.html`, `settings.html`.
- ✅ **Semantic Tags Utilized**: `<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<aside>`, `<footer>`, `<figure>`, `<figcaption>`, `<table>`, `<form>`, `<fieldset>`, `<legend>`.
- ✅ **Skip Links (Advanced Extension)**: Accessible `<a href="#main-content" class="skip-link">Skip to main content</a>` on all pages to allow screen-reader and keyboard users to bypass repetitive navigation.
- ✅ **Breadcrumb Trails (Intermediate Extension)**: `<nav aria-label="Breadcrumb" class="breadcrumb-wrapper">` implemented on all sub-pages for orientation.
- ✅ **ARIA Landmarks & Roles**: Explicit `role="banner"`, `role="navigation"`, `role="main"`, `role="alert"`, `aria-expanded`, `aria-controls`, `aria-label`, and `aria-current="page"`.
- ✅ **Explicit Input Labels**: Every input, select, and textarea element has a matching `<label for="id">` for full accessibility.

---

## 📱 Practical 3: Responsive UI Design using CSS Grid, Flexbox, and Mobile-First Layout

### Problem Definition:
Design responsive layouts for Home, About, Registration, Dashboard, and Events pages using CSS Grid and Flexbox. Ensure mobile-first responsiveness across desktop, tablet, and mobile screens.

### Layout Techniques Applied:
1. **CSS Grid (2D Systems)**:
   - Used for main content grid systems (`.grid-2`, `.grid-3`, `.grid-4`), multi-column dashboard stats, event listings, and footer links.
   - Media queries smoothly refactor columns: 4/3 columns on Desktop &rarr; 2 columns on Tablet (`max-width: 992px`) &rarr; 1 single column stack on Mobile (`max-width: 768px`).
2. **CSS Flexbox (1D Systems)**:
   - Used for navbar alignment, brand logo spacing, button toolbars, breadcrumb items, stat card icons, and form checkbox rows.
3. **Card & Button Transition Effects (Intermediate Extension)**:
   - Interactive hover lift: `transform: translateY(-4px); box-shadow: var(--shadow-lg); transition: all 0.25s ease;` applied to all cards and action buttons.
4. **Mobile Navigation Drawer**:
   - Compact hamburger button (`.mobile-menu-btn`) toggling an accessible dropdown navigation drawer on mobile viewports.

---

## ⚡ Practical 4: JavaScript DOM Manipulation, Event Handling, and UI Interactivity

### Problem Definition:
Add dynamic UI components such as collapsible FAQ, modal popup, image/content slider, notification banner, hamburger menu, and light/dark theme switcher using JavaScript.

### Implemented UI Components & Code Logic:
1. **Image / Content Slider (Carousel)**:
   - Multi-slide carousel on `index.html` and `events.html` showcasing hackathons, circulars, and framework labs.
   - Prev/Next buttons, active indicator dots, auto-sliding every 5 seconds, pause-on-hover, and keyboard arrow key navigation.
2. **Collapsible FAQ Accordion**:
   - Single-item active expansion with smooth disclosure, updating `aria-expanded` and `hidden` attributes.
3. **Campus Info Modal Dialog**:
   - Accessible modal dialog triggered by "View Campus Info" button with backdrop overlay, close button, and keyboard `Escape` key dismiss.
4. **Dismissible Notification Banner**:
   - Close button hides announcement bar and saves dismissal state into `localStorage` (Advanced extension).
5. **Mobile Hamburger Menu**:
   - Toggles `.is-open` class on navigation and syncs `aria-expanded` state.
6. **Light / Dark Theme Switcher with `localStorage`**:
   - Toggles `data-theme="dark"` attribute on `<html>` root and persists preference across page reloads.

---

## 📝 Practical 5: Registration Form with Frontend Validation and User-Friendly Error Handling

### Problem Definition:
Create a student registration form with HTML5 input types and JavaScript validation for name, email, mobile number, password, confirm password, course, year, gender, and terms acceptance. Use Regular Expression for validations.

### Implemented Features:
1. **HTML5 Form Input Types**: `type="text"`, `type="email"`, `type="tel"`, `type="password"`, `type="radio"`, `type="checkbox"`, and `<select>`.
2. **Regular Expression Form Validations**:
   - **Full Name**: `/^[A-Za-z\s]{3,50}$/` (Alphabetical characters and spaces, min 3 characters).
   - **Email**: `/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/` (RFC-compliant email pattern).
   - **Mobile Number**: `/^[0-9]{10}$/` (Exactly 10 decimal digits).
   - **Password Complexity**: `/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/` (At least 8 characters, 1 uppercase, 1 lowercase, 1 number, and 1 special symbol).
3. **Real-Time Validation (Intermediate Extension)**:
   - Event listeners bound to `input` and `change` events for instantaneous user feedback before submission.
4. **Real-time Password Strength Meter**:
   - Live visual score bar calculating character variety and updating color (`weak` = red, `medium` = yellow, `strong` = green).
5. **Custom Canvas CAPTCHA (Advanced Extension)**:
   - Dynamic client-side CAPTCHA rendered on HTML5 `<canvas id="captcha-canvas">`.
   - Generates random 6-character alphanumeric strings, distorts characters with rotational transforms, adds background noise lines and dots, and provides a "Refresh CAPTCHA" button. Verified upon submission.

---

## 🔎 Practical 6: Rendering External JSON Data using Fetch API, Search and Filter

### Problem Definition:
Display event lists, student profiles, notices, or FAQs from external JSON files using Fetch API. Implement dynamic rendering, search, filter, sorting, and pagination.

### Implementation Summary:
1. **JSON Datasets**: Three JSON files located in `data/`, each containing **15 records**:
   - `events.json`: Technical hackathons, cultural festivals, workshops, sports fests.
   - `students.json`: Student directory containing ID, name, email, department, GPA, country, state, and city.
   - `faqs.json`: 15 categorized campus questions with answers.
2. **Fetch API Architecture**:
   - Asynchronous `fetch()` calls with `async/await`, error handling, and loading state animations.
3. **Search, Filter, Sort & Pagination**:
   - **Debounced Search**: Filters records by keyword across multiple fields.
   - **Category / Department Filter**: Filters datasets dynamically.
   - **Sorting**: Ascending and Descending sort order by date, name, or GPA.
   - **Pagination**: Displays 6 records per page with First, Prev, Page numbers, and Next controls.
4. **Dependent Dropdown (Intermediate Extension)**:
   - Dynamic cascade: **Country &rarr; State &rarr; City** filters student profiles.
5. **Offline `localStorage` Caching (Advanced Extension)**:
   - Automatically caches JSON responses in `localStorage`; includes "Load Cache" and "Clear Cache" buttons for offline demonstration.

---

## 🛡️ Practical 7: PHP Form Processing with Server-Side Validation and CSV/JSON File Storage

### Problem Definition:
Process submitted registration/contact form data using PHP. Validate and sanitize inputs on the server side and store records in CSV or JSON file format. Display success/error messages.

### Implementation Architecture:
1. **Dual Form Processing**:
   - **Contact / Support Request Form**: Full Name, Email, Subject, and Message.
   - **Student Registration Form**: Full Name, University Email, Mobile (10 digits), Course Program, Academic Year, and Gender.
2. **File Storage Format Selection**:
   - Form enables user selection of storage format: **CSV File**, **JSON File**, or **Both**.
3. **Safe Server-Side Validation & Sanitization**:
   - Server-side email validation with `filter_var($email, FILTER_VALIDATE_EMAIL)`.
   - Regex validation for name length and 10-digit mobile number format.
   - Whitelist validation for dropdown options (Subject, Course, Year, Gender).
   - Control-character stripping and length restriction (10 to 2,000 characters).
4. **Atomic Concurrency & File Security**:
   - Writes to `private/practical7_submissions.csv` using `fputcsv()` with exclusive advisory lock (`flock(..., LOCK_EX)`).
   - Writes to `private/practical7_submissions.json` using atomic file writes with lock protection.
   - CSV formula injection protection (`csvSafeValue` prepends `'` to inputs beginning with `=`, `+`, `-`, `@`).
   - `private/.htaccess` blocks direct web browser access to storage files.
5. **Security & UX Flow**:
   - **Anti-CSRF Token (Advanced Extension)**: Session-bound random 32-byte token validated using timing-attack safe `hash_equals()`.
   - **Post/Redirect/Get (PRG) Pattern**: Redirects upon successful POST submission using HTTP 303 to eliminate duplicate form submissions on browser reload.
6. **Webpage Stored Records Viewer (Intermediate Extension)**:
   - Displays stored records in responsive tables with quick tab switching between **CSV Stored Records** and **JSON Stored Records**.
   - All cell values sanitized using `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` to prevent Stored Cross-Site Scripting (XSS).

---

### Step-by-Step Instructions to Run Practical 7 in XAMPP:
1. Open XAMPP Control Panel and start the **Apache** server.
2. Ensure the project folder `WD` is placed inside `C:\xampp\htdocs\WD` (or accessed through alias).
3. Open your web browser and navigate to:
   ```
   http://localhost/WD/practical7.php
   ```
4. Choose between the **Contact Form** or **Student Registration Form**.
5. Select your desired storage format (**CSV**, **JSON**, or **Both**).
6. Fill in valid inputs and submit.
7. Observe the success banner and view your new record appended in the **Stored Records Viewer** table below!

---

### Practical 7 Verification Test Cases

| Test Case | Inputs Provided | Expected Server Action | Result |
| :--- | :--- | :--- | :--- |
| **TC-01: Valid Contact (CSV)** | Name: "Smit Popat", Email: "smit@charusat.ac.in", Subject: "Course Inquiry", Msg: "Requesting syllabus details.", Format: CSV | Validated, row appended to CSV, 303 redirect, flash banner | ✅ Pass |
| **TC-02: Valid Student Reg (JSON)** | Name: "Diya Patel", Email: "diya@charusat.ac.in", Mobile: "9825012345", Course: "B.Tech IT", Year: "2nd Year", Format: JSON | Validated, appended to JSON array, displayed in JSON table | ✅ Pass |
| **TC-03: Invalid Email** | Email: "invalid-email-string" | Server error: "Enter a valid email address." Storage untouched. | ✅ Pass |
| **TC-04: Short Mobile Number** | Mobile: "98250" (less than 10 digits) | Server error: "Mobile number must be exactly 10 digits." | ✅ Pass |
| **TC-05: Missing CSRF Token** | Manipulate or remove hidden `csrf_token` input | Session expired warning, submission blocked, file untouched | ✅ Pass |
| **TC-06: CSV Formula Injection Prevention** | Name: `=cmd|' /C calc'!A0` | Value sanitized to `'=cmd|' /C calc'!A0` preventing Excel execution | ✅ Pass |
| **TC-07: HTML / XSS Injection Prevention** | Message: `<script>alert("XSS")</script>` | Sanitized with `htmlspecialchars()`; displayed as inert plaintext | ✅ Pass |

---

## 🎯 Viva Voce Comprehensive Guide (Practicals 1–7)

### Practical 1 & 2 (HTML5 & Accessibility)
1. **What is the difference between semantic and non-semantic HTML tags?**
   - Non-semantic tags (`<div>`, `<span>`) carry no inherent meaning about their content. Semantic tags (`<header>`, `<nav>`, `<main>`, `<article>`, `<section>`, `<footer>`) clearly communicate their purpose to developers, browsers, search engines, and screen-readers.
2. **Why are Skip Links essential in accessible web portals?**
   - Skip links allow keyboard and screen-reader users to jump straight to the `<main id="main-content">` landmark, skipping repetitive top menus.
3. **What are ARIA attributes and when should they be used?**
   - Accessible Rich Internet Applications (ARIA) attributes (e.g., `aria-expanded`, `aria-hidden`, `aria-controls`) bridge accessibility gaps when standard HTML tags cannot convey dynamic states.

### Practical 3 (Responsive Design)
4. **When should you choose CSS Grid over CSS Flexbox?**
   - Use **CSS Grid** for two-dimensional layouts where you need to manage both rows and columns simultaneously (e.g. card galleries, page layouts). Use **Flexbox** for one-dimensional layouts where items flow in a single direction (e.g. navigation bars, button rows).
5. **What is a "mobile-first" approach?**
   - Designing base styles for small screen viewports first, then using `min-width` media queries to progressively enhance the layout as screen size increases.

### Practical 4 (DOM Manipulation & Events)
6. **How does event delegation work in JavaScript?**
   - Event delegation attaches a single event listener to a parent container instead of multiple listeners on individual children, leveraging event bubbling (`e.target`).
7. **What is the difference between `localStorage` and `sessionStorage`?**
   - `localStorage` persists data indefinitely until explicitly cleared, even when the browser is closed. `sessionStorage` clears data as soon as the browser tab is closed.

### Practical 5 (Form Validation)
8. **Why is frontend validation alone never sufficient for web applications?**
   - Client-side validation can be bypassed by disabling JavaScript or using tools like cURL/Postman. Backend validation is mandatory for integrity and security.
9. **How does the Canvas CAPTCHA prevent automated bots?**
   - It renders characters as rasterized bitmap graphics with distortion lines and noise, making text difficult for simple scrapers to parse without advanced OCR.

### Practical 6 (Fetch API & JSON)
10. **What is the difference between `XMLHttpRequest` and the `Fetch API`?**
    - `fetch()` uses modern Promise-based syntax (`async/await`), providing cleaner asynchronous code flow compared to callback-heavy `XMLHttpRequest`.
11. **Why is debouncing important in live search inputs?**
    - Debouncing delays execution until a specified delay (e.g., 250ms) has passed since the last keystroke, preventing redundant calculations or network requests on every letter typed.

### Practical 7 (PHP & File Storage)
12. **Why must `flock()` be used when writing to files in PHP?**
    - When multiple users submit forms simultaneously, concurrent write operations can interleave and corrupt the CSV or JSON file. Advisory locking (`LOCK_EX`) ensures serial, thread-safe writes.
13. **What is a CSRF attack and how does an anti-CSRF token protect against it?**
    - Cross-Site Request Forgery tricks an authenticated user's browser into submitting unauthorized requests. An unpredictable session-bound CSRF token ensures the request originated from the genuine form.
14. **What is the Post/Redirect/Get (PRG) pattern?**
    - After processing a successful POST request, the server responds with a redirect (`header('Location: ...', true, 303)`). This prevents duplicate form submissions if the user refreshes the page.
