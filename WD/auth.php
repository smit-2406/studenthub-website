<?php
/**
 * CHARUSAT - ITUE203: Web Development Frameworks
 * StudentHub Authentication & RBAC Helper (Practical 10)
 * Features:
 * - Secure Sessions with Session ID regeneration
 * - Role-Based Access Control (RBAC: Admin vs Student)
 * - Session Inactivity Timeout (15 minutes)
 * - Secure Remember-Me Token using random bytes + SHA256 DB hash
 * - CSRF Protection Helpers
 */

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'db.php';

// Session Timeout Threshold (15 minutes in seconds)
define('SESSION_TIMEOUT_SECONDS', 900);

/**
 * Configure and start session with secure cookie flags
 */
function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_samesite', 'Lax');

        session_start();
    }
}

startSecureSession();

/**
 * Generate or retrieve CSRF token
 */
function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token securely
 */
function verifyCsrfToken(?string $token): bool
{
    if (!$token || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check and enforce session inactivity timeout
 */
function checkSessionTimeout(): void
{
    if (isset($_SESSION['user_id'], $_SESSION['last_activity'])) {
        if ((time() - $_SESSION['last_activity']) > SESSION_TIMEOUT_SECONDS) {
            // Inactivity timeout reached
            logoutUser();
            header('Location: login.php?msg=timeout');
            exit;
        }
    }
    if (isset($_SESSION['user_id'])) {
        $_SESSION['last_activity'] = time();
    }
}

checkSessionTimeout();

/**
 * Check if current visitor has a valid remember-me cookie
 */
function checkRememberMeCookie(): void
{
    if (isset($_SESSION['user_id']) || empty($_COOKIE['studenthub_remember'])) {
        return;
    }

    $cookieData = explode(':', $_COOKIE['studenthub_remember'], 2);
    if (count($cookieData) !== 2) {
        return;
    }

    [$userId, $rawToken] = $cookieData;
    $userId = (int)$userId;
    if ($userId <= 0 || strlen($rawToken) < 32) {
        return;
    }

    global $conn;
    $stmt = $conn->prepare("SELECT id, username, email, full_name, role, is_active, remember_token, token_expiry FROM users WHERE id = ? AND is_active = 1 LIMIT 1");
    if (!$stmt) {
        return;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && !empty($user['remember_token']) && !empty($user['token_expiry'])) {
        if (strtotime($user['token_expiry']) > time()) {
            $hashedCookieToken = hash('sha256', $rawToken);
            if (hash_equals($user['remember_token'], $hashedCookieToken)) {
                // Token valid: Authenticate user & regenerate session
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
            }
        }
    }
}

checkRememberMeCookie();

/**
 * Check if a user is currently logged in
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
}

/**
 * Check if current user has admin privileges
 */
function isAdmin(): bool
{
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Get logged-in user data
 */
function getCurrentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'],
        'username'  => $_SESSION['username'] ?? '',
        'email'     => $_SESSION['email'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'role'      => $_SESSION['role'] ?? 'student',
    ];
}

/**
 * Route protection: Requires visitor to be logged in
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        $currentUri = $_SERVER['REQUEST_URI'] ?? 'dashboard.php';
        $_SESSION['redirect_after_login'] = $currentUri;
        header('Location: login.php?msg=login_required');
        exit;
    }
}

/**
 * Route protection: Requires user to have a specific role (e.g. 'admin')
 */
function requireRole(string $requiredRole): void
{
    requireLogin();
    if ($_SESSION['role'] !== $requiredRole) {
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8">
          <title>403 Forbidden &mdash; StudentHub</title>
          <link rel="stylesheet" href="css/styles.css">
        </head>
        <body style="background:var(--bg-main);padding:3rem 1rem;">
          <div style="max-width:550px;margin:2rem auto;background:#fff;padding:2.5rem;border-radius:12px;border:1px solid #e2e8f0;text-align:center;">
            <div style="font-size:3.5rem;">🛡️</div>
            <h1 style="color:#dc2626;margin:0.5rem 0;">403 - Access Denied</h1>
            <p style="color:#64748b;font-size:1.05rem;">You do not have the required <strong><?= htmlspecialchars(strtoupper($requiredRole)) ?></strong> privileges to access this administrative portal.</p>
            <div style="margin-top:1.5rem;display:flex;gap:0.75rem;justify-content:center;">
              <a href="dashboard.php" class="btn btn-primary btn-sm">Go to Student Dashboard</a>
              <a href="logout.php" class="btn btn-outline btn-sm">Switch Account</a>
            </div>
          </div>
        </body>
        </html>
        <?php
        exit;
    }
}

/**
 * Log out user: Clear session, destroy session cookie, invalidate DB remember token
 */
function logoutUser(): void
{
    global $conn;
    if (isset($_SESSION['user_id'])) {
        $userId = (int)$_SESSION['user_id'];
        $stmt = $conn->prepare("UPDATE users SET remember_token = NULL, token_expiry = NULL WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Unset all session variables
    $_SESSION = [];

    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Delete remember-me cookie
    if (isset($_COOKIE['studenthub_remember'])) {
        setcookie('studenthub_remember', '', time() - 3600, '/');
    }

    session_destroy();
}
