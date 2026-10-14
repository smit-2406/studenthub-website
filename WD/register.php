<?php
/**
 * CHARUSAT - ITUE203: Web Development Frameworks
 * StudentHub Portal - Secure User Registration (Practical 9)
 * Features:
 * - Frontend and Backend Regular Expression Validation
 * - Duplicate Username and Email Verification via MySQLi Prepared Statements
 * - Secure Password Hashing using password_hash() (BCRYPT)
 * - Anti-CSRF Token Validation
 * - Linked Users and Students Table Insertion
 * - Audit Log Entry for Security Tracking
 */

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin.php' : 'dashboard.php'));
    exit;
}

$errors = [];
$successMessage = '';

// Predefined Options
$courses = [
    'B.Tech Computer Engineering',
    'B.Tech IT Engineering',
    'B.Tech Electrical',
    'B.Tech Mechanical',
    'B.Tech Civil'
];
$years = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
$genders = ['Male', 'Female', 'Other'];

// Form values sticky array
$form = [
    'full_name'  => '',
    'username'   => '',
    'student_id' => '',
    'email'      => '',
    'phone'      => '',
    'course'     => '',
    'year'       => '',
    'gender'     => 'Male',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validate CSRF Token
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($submittedCsrf)) {
        $errors[] = 'Security token invalid or expired. Please refresh and try again.';
    }

    // 2. Sanitize and Collect Inputs
    $form['full_name']  = trim((string)($_POST['full_name'] ?? ''));
    $form['username']   = trim((string)($_POST['username'] ?? ''));
    $form['student_id'] = strtoupper(trim((string)($_POST['student_id'] ?? '')));
    $form['email']      = strtolower(trim((string)($_POST['email'] ?? '')));
    $form['phone']      = trim((string)($_POST['phone'] ?? ''));
    $form['course']     = trim((string)($_POST['course'] ?? ''));
    $form['year']       = trim((string)($_POST['year'] ?? ''));
    $form['gender']     = trim((string)($_POST['gender'] ?? 'Male'));

    $password        = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    $termsAccepted   = isset($_POST['terms']);

    // 3. Backend Server-Side Validations
    if (!preg_match('/^[\p{L}\p{M} .\'-]{2,80}$/u', $form['full_name'])) {
        $errors[] = 'Full name must be 2 to 80 characters containing valid alphabetic letters.';
    }

    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $form['username'])) {
        $errors[] = 'Username must be 3 to 30 characters containing only letters, numbers, and underscores.';
    }

    if (!preg_match('/^[A-Z0-9]{5,20}$/', $form['student_id'])) {
        $errors[] = 'Student Roll/ID must be 5 to 20 alphanumeric characters (e.g., D26DCE173).';
    }

    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

    if (!preg_match('/^[6-9]\d{9}$/', $form['phone'])) {
        $errors[] = 'Mobile number must be a valid 10-digit number starting with 6, 7, 8, or 9.';
    }

    if (!in_array($form['course'], $courses, true)) {
        $errors[] = 'Please select a valid academic course program.';
    }

    if (!in_array($form['year'], $years, true)) {
        $errors[] = 'Please select a valid academic year.';
    }

    if (!in_array($form['gender'], $genders, true)) {
        $form['gender'] = 'Male';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W_]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Password and Confirm Password do not match.';
    }

    if (!$termsAccepted) {
        $errors[] = 'You must accept the StudentHub Terms of Service and Privacy Policy.';
    }

    // 4. Duplicate Checks via MySQLi Prepared Statements
    if (empty($errors)) {
        global $conn;

        // Check duplicate email or username in users table
        $checkStmt = $conn->prepare("SELECT id, username, email FROM users WHERE email = ? OR username = ? LIMIT 1");
        if ($checkStmt) {
            $checkStmt->bind_param("ss", $form['email'], $form['username']);
            $checkStmt->execute();
            $checkRes = $checkStmt->get_result();
            if ($existing = $checkRes->fetch_assoc()) {
                if ($existing['email'] === $form['email']) {
                    $errors[] = 'An account with this email address already exists. Please log in.';
                }
                if ($existing['username'] === $form['username']) {
                    $errors[] = 'This username is already taken. Please choose another username.';
                }
            }
            $checkStmt->close();
        }

        // Check duplicate student_id in students table
        $sCheck = $conn->prepare("SELECT id FROM students WHERE student_id = ? OR email = ? LIMIT 1");
        if ($sCheck) {
            $sCheck->bind_param("ss", $form['student_id'], $form['email']);
            $sCheck->execute();
            if ($sCheck->get_result()->fetch_assoc()) {
                $errors[] = 'A student profile with this Student ID or Email is already registered.';
            }
            $sCheck->close();
        }
    }

    // 5. Database Insert using Transaction and Prepared Statements
    if (empty($errors)) {
        // Hash password securely with BCRYPT
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $conn->begin_transaction();
        try {
            // Insert into users
            $userStmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, 'student', 1)");
            $userStmt->bind_param("ssss", $form['username'], $form['email'], $passwordHash, $form['full_name']);
            $userStmt->execute();
            $newUserId = $conn->insert_id;
            $userStmt->close();

            // Insert into students
            $sem = ($form['year'] === '1st Year') ? 1 : (($form['year'] === '2nd Year') ? 3 : (($form['year'] === '3rd Year') ? 5 : 7));
            $dept = (strpos($form['course'], 'IT') !== false) ? 'Information Technology' :
                    ((strpos($form['course'], 'Electrical') !== false) ? 'Electrical Engineering' :
                    ((strpos($form['course'], 'Mechanical') !== false) ? 'Mechanical Engineering' :
                    ((strpos($form['course'], 'Civil') !== false) ? 'Civil Engineering' : 'Computer Engineering')));

            $studentStmt = $conn->prepare("INSERT INTO students (user_id, student_id, full_name, email, phone, department, course, semester, academic_year, gender, cgpa, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 8.50, 'Active')");
            $studentStmt->bind_param("issssssiss", $newUserId, $form['student_id'], $form['full_name'], $form['email'], $form['phone'], $dept, $form['course'], $sem, $form['year'], $form['gender']);
            $studentStmt->execute();
            $studentStmt->close();

            // Insert audit log
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $auditStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'USER_REGISTRATION', ?, ?)");
            $auditDetails = "New student registered: " . $form['username'] . " (" . $form['student_id'] . ")";
            $auditStmt->bind_param("iss", $newUserId, $auditDetails, $ip);
            $auditStmt->execute();
            $auditStmt->close();

            $conn->commit();

            $_SESSION['registration_success'] = 'Account registered successfully! You may now log in with your credentials.';
            header('Location: login.php?msg=reg_success');
            exit;
        } catch (Throwable $ex) {
            $conn->rollback();
            $errors[] = 'Registration failed due to a database error: ' . $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Register your StudentHub account with validated input and secure password encryption." />
  <title>Student Registration &mdash; CHARUSAT StudentHub</title>
  <link rel="stylesheet" href="css/styles.css" />
  <script>
    (function () {
      const savedTheme = localStorage.getItem('theme');
      if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
      }
    })();
  </script>
</head>
<body>

  <!-- Skip Link -->
  <a class="skip-link" href="#main-content">Skip to main content</a>

  <!-- Header -->
  <header class="site-header" role="banner">
    <div class="container">
      <div class="header-inner">
        <a href="index.html" class="brand-logo">
          <span class="brand-icon" aria-hidden="true">SH</span>
          StudentHub
        </a>
        <button class="mobile-menu-btn" aria-expanded="false" aria-controls="main-nav" aria-label="Toggle navigation">☰</button>
        <nav class="main-nav" id="main-nav" role="navigation" aria-label="Primary navigation">
          <ul>
            <li><a href="index.html" class="nav-link">Home</a></li>
            <li><a href="about.html" class="nav-link">About</a></li>
            <li><a href="courses.html" class="nav-link">Courses</a></li>
            <li><a href="events.php" class="nav-link">Events</a></li>
            <li><a href="explorer.html" class="nav-link">Data Explorer</a></li>
            <li><a href="submissions.php" class="nav-link">Submissions</a></li>
            <li><a href="contact.html" class="nav-link">Contact</a></li>
          </ul>
        </nav>
        <div class="nav-actions">
          <button id="theme-toggle-btn" class="btn btn-outline btn-sm" aria-label="Toggle dark mode">🌙 Dark</button>
          <a href="login.php" class="btn btn-outline btn-sm">Login</a>
          <a href="register.php" class="btn btn-primary btn-sm" aria-current="page">Register</a>
        </div>
      </div>
    </div>
  </header>

  <!-- Breadcrumb Navigation -->
  <nav class="breadcrumb-wrapper" aria-label="Breadcrumb">
    <div class="container">
      <ol class="breadcrumb-list" role="list">
        <li class="breadcrumb-item"><a href="index.html">Home</a></li>
        <li class="breadcrumb-separator" aria-hidden="true">›</li>
        <li class="breadcrumb-item" aria-current="page">Student Registration</li>
      </ol>
    </div>
  </nav>

  <!-- Main Content -->
  <main id="main-content" class="main-content">
    <div class="container" style="max-width: 820px;">

      <section class="page-header" aria-labelledby="page-title">
        <h1 id="page-title" class="page-title">Student Registration</h1>
        <p class="page-subtitle">Secure account creation with duplicate checks, BCRYPT password hashing, and MySQL integration</p>
      </section>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" style="background:#fee2e2;border:1px solid #ef4444;color:#991b1b;padding:1rem 1.25rem;border-radius:8px;margin-bottom:1.5rem;" role="alert">
          <strong style="display:block;margin-bottom:0.5rem;">⚠️ Please correct the following errors:</strong>
          <ul style="margin:0;padding-left:1.25rem;">
            <?php foreach ($errors as $err): ?>
              <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="form-card" style="background:var(--bg-card);padding:2rem;border-radius:12px;border:1px solid var(--border);box-shadow:var(--shadow-md);">
        <form action="register.php" method="post" id="secure-registration-form" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>" />

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
            <!-- Full Name -->
            <div class="form-group">
              <label for="full_name" class="form-label">Full Name <span class="required">*</span></label>
              <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. Smit Popat" value="<?= htmlspecialchars($form['full_name']) ?>" required />
            </div>

            <!-- Username -->
            <div class="form-group">
              <label for="username" class="form-label">Username <span class="required">*</span></label>
              <input type="text" id="username" name="username" class="form-control" placeholder="e.g. smitpopat" value="<?= htmlspecialchars($form['username']) ?>" required />
              <small class="text-muted">3-30 alphanumeric characters</small>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
            <!-- Student ID -->
            <div class="form-group">
              <label for="student_id" class="form-label">Student ID / Roll No <span class="required">*</span></label>
              <input type="text" id="student_id" name="student_id" class="form-control" placeholder="e.g. D26DCE173" value="<?= htmlspecialchars($form['student_id']) ?>" required />
            </div>

            <!-- Email Address -->
            <div class="form-group">
              <label for="email" class="form-label">Email Address <span class="required">*</span></label>
              <input type="email" id="email" name="email" class="form-control" placeholder="e.g. smit.popat@charusat.edu.in" value="<?= htmlspecialchars($form['email']) ?>" required />
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">
            <!-- Mobile Phone -->
            <div class="form-group">
              <label for="phone" class="form-label">Mobile Number <span class="required">*</span></label>
              <input type="tel" id="phone" name="phone" class="form-control" placeholder="10-digit mobile" maxlength="10" value="<?= htmlspecialchars($form['phone']) ?>" required />
            </div>

            <!-- Course -->
            <div class="form-group">
              <label for="course" class="form-label">Course <span class="required">*</span></label>
              <select id="course" name="course" class="form-control" required>
                <option value="">-- Select Course --</option>
                <?php foreach ($courses as $c): ?>
                  <option value="<?= htmlspecialchars($c) ?>" <?= $form['course'] === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Academic Year -->
            <div class="form-group">
              <label for="year" class="form-label">Academic Year <span class="required">*</span></label>
              <select id="year" name="year" class="form-control" required>
                <option value="">-- Select Year --</option>
                <?php foreach ($years as $y): ?>
                  <option value="<?= htmlspecialchars($y) ?>" <?= $form['year'] === $y ? 'selected' : '' ?>><?= htmlspecialchars($y) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Gender -->
          <div class="form-group">
            <label class="form-label">Gender <span class="required">*</span></label>
            <div style="display:flex;gap:1.5rem;margin-top:0.35rem;">
              <?php foreach ($genders as $g): ?>
                <label style="display:inline-flex;align-items:center;gap:0.35rem;cursor:pointer;">
                  <input type="radio" name="gender" value="<?= htmlspecialchars($g) ?>" <?= $form['gender'] === $g ? 'checked' : '' ?> />
                  <?= htmlspecialchars($g) ?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
            <!-- Password -->
            <div class="form-group">
              <label for="password" class="form-label">Password <span class="required">*</span></label>
              <input type="password" id="password" name="password" class="form-control" placeholder="Min 8 chars (A-Z, a-z, 0-9, symbol)" required />
              <div id="password-strength-bar" style="height:4px;border-radius:2px;background:#e2e8f0;margin-top:6px;transition:all 0.3s;"></div>
              <small id="password-strength-text" class="text-muted">Password must be at least 8 characters</small>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
              <label for="confirm_password" class="form-label">Confirm Password <span class="required">*</span></label>
              <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required />
            </div>
          </div>

          <!-- Terms -->
          <div class="form-group" style="margin-top:0.5rem;">
            <label style="display:flex;align-items:flex-start;gap:0.5rem;cursor:pointer;">
              <input type="checkbox" name="terms" id="terms" style="margin-top:0.25rem;" required />
              <span>I confirm that all details provided are accurate and I agree to the <a href="#" style="color:var(--primary);">Terms of Service</a> and Student Code of Conduct.</span>
            </label>
          </div>

          <!-- Submit -->
          <div class="form-group" style="margin-top:1.5rem;">
            <button type="submit" class="btn btn-primary btn-block" style="padding:0.75rem;">
              Create Student Account &rarr;
            </button>
          </div>

          <p style="text-align:center;margin-top:1rem;color:var(--text-muted);">
            Already have an account? <a href="login.php" style="color:var(--primary);font-weight:600;">Sign in here</a>
          </p>
        </form>
      </div>

    </div>
  </main>

  <!-- Footer -->
  <footer class="site-footer" role="contentinfo">
    <div class="container">
      <div class="footer-bottom">
        <p>&copy; 2026 StudentHub &mdash; CHARUSAT ITUE203 Web Development Frameworks</p>
        <p>Faculty of Technology &amp; Engineering (FTE)</p>
      </div>
    </div>
  </footer>

  <script src="js/main.js"></script>
  <script>
    // Password strength indicator
    const pwdInput = document.getElementById('password');
    const bar = document.getElementById('password-strength-bar');
    const txt = document.getElementById('password-strength-text');

    if (pwdInput && bar && txt) {
      pwdInput.addEventListener('input', function () {
        const val = pwdInput.value;
        let score = 0;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[a-z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[\W_]/.test(val)) score++;

        if (val.length === 0) {
          bar.style.width = '0%';
          bar.style.background = '#e2e8f0';
          txt.textContent = 'Password must be at least 8 characters';
        } else if (score <= 2) {
          bar.style.width = '30%';
          bar.style.background = '#ef4444';
          txt.textContent = 'Weak password';
        } else if (score <= 4) {
          bar.style.width = '70%';
          bar.style.background = '#f59e0b';
          txt.textContent = 'Moderate password';
        } else {
          bar.style.width = '100%';
          bar.style.background = '#10b981';
          txt.textContent = 'Strong password! ✓';
        }
      });
    }
  </script>
</body>
</html>
