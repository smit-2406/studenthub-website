<?php
/**
 * CHARUSAT - ITUE203: Web Development Frameworks
 * StudentHub Portal - Secure Authentication (Practical 10)
 * Features:
 * - password_verify() with BCRYPT hashes
 * - session_regenerate_id(true) to mitigate Session Fixation
 * - Role-Based Access Control Redirection (Admin vs Student)
 * - Session Inactivity Timeout
 * - Secure Remember-Me Token using random bytes + SHA256 DB hash
 * - Last-Login Timestamp Tracking
 */

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

// If already authenticated, redirect to appropriate destination
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin.php' : 'dashboard.php'));
    exit;
}

$error = '';
$info = '';

// Check incoming message query parameters
$msg = $_GET['msg'] ?? '';
if ($msg === 'logged_out') {
    $info = 'You have been securely logged out.';
} elseif ($msg === 'timeout') {
    $error = 'Your session expired due to 15 minutes of inactivity. Please log in again.';
} elseif ($msg === 'login_required') {
    $error = 'You must log in to access that protected page.';
} elseif ($msg === 'reg_success' || isset($_SESSION['registration_success'])) {
    $info = $_SESSION['registration_success'] ?? 'Registration successful! You may now log in.';
    unset($_SESSION['registration_success']);
}

$loginIdentity = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($submittedCsrf)) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $loginIdentity = trim((string)($_POST['identity'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $rememberMe = isset($_POST['remember_me']);

        if ($loginIdentity === '' || $password === '') {
            $error = 'Please enter both your username/email and password.';
        } else {
            global $conn;

            // Prepared statement to search by username or email
            $stmt = $conn->prepare("SELECT id, username, email, password_hash, full_name, role, is_active FROM users WHERE (username = ? OR email = ?) LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("ss", $loginIdentity, $loginIdentity);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();

                if ($user && (int)$user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
                    // Password verified successfully!
                    // 1. Prevent Session Fixation attack
                    session_regenerate_id(true);

                    // 2. Set Session State
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['last_activity'] = time();

                    // 3. Update Last Login Timestamp
                    $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    if ($updateStmt) {
                        $userId = (int)$user['id'];
                        $updateStmt->bind_param("i", $userId);
                        $updateStmt->execute();
                        $updateStmt->close();
                    }

                    // 4. Handle Remember-Me Token (Advanced Extension)
                    if ($rememberMe) {
                        $rawToken = bin2hex(random_bytes(32));
                        $hashedToken = hash('sha256', $rawToken);
                        $expiryTime = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 days

                        $tokenStmt = $conn->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?");
                        if ($tokenStmt) {
                            $userId = (int)$user['id'];
                            $tokenStmt->bind_param("ssi", $hashedToken, $expiryTime, $userId);
                            $tokenStmt->execute();
                            $tokenStmt->close();

                            // Set Secure Cookie
                            $cookieValue = $user['id'] . ':' . $rawToken;
                            setcookie('studenthub_remember', $cookieValue, [
                                'expires'  => time() + (86400 * 30),
                                'path'     => '/',
                                'httponly' => true,
                                'samesite' => 'Lax'
                            ]);
                        }
                    }

                    // 5. Audit Log Entry
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    $auditStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'USER_LOGIN', 'Successful login authentication', ?)");
                    if ($auditStmt) {
                        $userId = (int)$user['id'];
                        $auditStmt->bind_param("is", $userId, $ip);
                        $auditStmt->execute();
                        $auditStmt->close();
                    }

                    // 6. Role-Based Redirection
                    $redirect = $_SESSION['redirect_after_login'] ?? null;
                    unset($_SESSION['redirect_after_login']);

                    if ($redirect && !strpos($redirect, 'login.php')) {
                        header('Location: ' . $redirect);
                    } elseif ($user['role'] === 'admin') {
                        header('Location: admin.php');
                    } else {
                        header('Location: dashboard.php');
                    }
                    exit;
                } else {
                    $error = 'Invalid credentials. Please verify your username/email and password.';

                    // Log failed attempt
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    $auditStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (NULL, 'LOGIN_FAILED', ?, ?)");
                    if ($auditStmt) {
                        $failDetails = "Failed attempt for identity: " . substr($loginIdentity, 0, 50);
                        $auditStmt->bind_param("ss", $failDetails, $ip);
                        $auditStmt->execute();
                        $auditStmt->close();
                    }
                }
            } else {
                $error = 'Database query failed. Please verify database connectivity.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Secure login with password verification, role-based redirection, and remember-me tokens." />
  <title>Login &mdash; CHARUSAT StudentHub</title>
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
          <a href="login.php" class="btn btn-outline btn-sm" aria-current="page">Login</a>
          <a href="register.php" class="btn btn-primary btn-sm">Register</a>
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
        <li class="breadcrumb-item" aria-current="page">Login</li>
      </ol>
    </div>
  </nav>

  <!-- Main Content -->
  <main id="main-content" class="main-content">
    <div class="container" style="max-width: 520px;">

      <section class="page-header" aria-labelledby="page-title">
        <h1 id="page-title" class="page-title">User Authentication</h1>
        <p class="page-subtitle">Access your StudentHub dashboard with role-based permissions</p>
      </section>

      <?php if (!empty($info)): ?>
        <div class="alert alert-success" style="background:#dcfce7;border:1px solid #22c55e;color:#15803d;padding:0.9rem 1.25rem;border-radius:8px;margin-bottom:1.5rem;" role="status">
          ✓ <?= htmlspecialchars($info) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="background:#fee2e2;border:1px solid #ef4444;color:#991b1b;padding:0.9rem 1.25rem;border-radius:8px;margin-bottom:1.5rem;" role="alert">
          ⚠️ <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <!-- Demo Credentials Helper Card -->
      <div style="background:var(--bg-alt);border:1px dashed var(--border);border-radius:8px;padding:0.85rem 1rem;margin-bottom:1.25rem;font-size:0.85rem;color:var(--text-muted);">
        <strong>🔑 Default Accounts for Viva / Testing:</strong><br>
        • <strong>Admin:</strong> <code>admin</code> | Password: <code>Admin@12345</code><br>
        • <strong>Student:</strong> <code>smitpopat</code> | Password: <code>Student@12345</code>
      </div>

      <div class="form-card" style="background:var(--bg-card);padding:2rem;border-radius:12px;border:1px solid var(--border);box-shadow:var(--shadow-md);">
        <form action="login.php" method="post" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>" />

          <!-- Username or Email -->
          <div class="form-group">
            <label for="identity" class="form-label">Username or Email <span class="required">*</span></label>
            <input
              type="text"
              id="identity"
              name="identity"
              class="form-control"
              placeholder="e.g. admin or smitpopat"
              value="<?= htmlspecialchars($loginIdentity) ?>"
              autocomplete="username"
              required
            />
          </div>

          <!-- Password -->
          <div class="form-group">
            <label for="password" class="form-label">Password <span class="required">*</span></label>
            <input
              type="password"
              id="password"
              name="password"
              class="form-control"
              placeholder="Enter your password"
              autocomplete="current-password"
              required
            />
          </div>

          <!-- Remember Me + Forgot Password -->
          <div class="form-group" style="display:flex;justify-content:space-between;align-items:center;margin-top:0.5rem;">
            <label style="display:inline-flex;align-items:center;gap:0.4rem;cursor:pointer;font-size:0.9rem;">
              <input type="checkbox" name="remember_me" id="remember_me" />
              <span>Remember me (30 days)</span>
            </label>
            <a href="faq.html" style="font-size:0.85rem;color:var(--primary);">Need help?</a>
          </div>

          <!-- Submit -->
          <div class="form-group" style="margin-top:1.5rem;">
            <button type="submit" class="btn btn-primary btn-block" style="padding:0.75rem;">
              Sign In to StudentHub &rarr;
            </button>
          </div>

          <p style="text-align:center;margin-top:1.25rem;color:var(--text-muted);font-size:0.9rem;">
            Don't have an account? <a href="register.php" style="color:var(--primary);font-weight:600;">Register here</a>
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
</body>
</html>
