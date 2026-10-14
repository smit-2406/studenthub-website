<?php
/**
 * CHARUSAT - ITUE203: Web Development Frameworks
 * StudentHub Portal - Student Dashboard (Practical 10)
 * Protected Route: Requires active student or user session
 */

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

requireLogin();

$currentUser = getCurrentUser();
$userId = (int)$_SESSION['user_id'];

global $conn;

// Fetch Student Profile
$stmt = $conn->prepare("SELECT s.*, u.username, u.email as account_email, u.last_login FROM users u LEFT JOIN students s ON s.user_id = u.id WHERE u.id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

$studentId = (int)($profile['id'] ?? 0);

// Fetch Enrolled Events
$registeredEvents = [];
if ($studentId > 0) {
    $eStmt = $conn->prepare("SELECT e.id, e.title, e.event_date, e.event_time, e.venue, r.attendance_status, r.registration_date FROM registrations r JOIN events e ON r.event_id = e.id WHERE r.student_id = ? ORDER BY e.event_date ASC");
    if ($eStmt) {
        $eStmt->bind_param("i", $studentId);
        $eStmt->execute();
        $res = $eStmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $registeredEvents[] = $row;
        }
        $eStmt->close();
    }
}

// Fetch Upcoming Events Count
$totalEventsQuery = $conn->query("SELECT COUNT(*) AS total FROM events WHERE status = 'Upcoming'");
$totalUpcoming = $totalEventsQuery ? (int)$totalEventsQuery->fetch_assoc()['total'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="CHARUSAT StudentHub – Personalized Student Dashboard" />
  <title>Student Dashboard &mdash; CHARUSAT StudentHub</title>
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
            <li><a href="courses.html" class="nav-link">Courses</a></li>
            <li><a href="events.php" class="nav-link">Events</a></li>
            <li><a href="explorer.html" class="nav-link">Data Explorer</a></li>
            <li><a href="submissions.php" class="nav-link">Submissions</a></li>
            <li><a href="dashboard.php" class="nav-link" aria-current="page">Dashboard</a></li>
          </ul>
        </nav>
        <div class="nav-actions">
          <button id="theme-toggle-btn" class="btn btn-outline btn-sm" aria-label="Toggle dark mode">🌙 Dark</button>
          <span style="font-size:0.85rem;font-weight:600;padding:0.35rem 0.65rem;background:var(--bg-alt);border-radius:6px;border:1px solid var(--border);">
            👤 <?= htmlspecialchars($_SESSION['username']) ?>
          </span>
          <a href="logout.php" class="btn btn-outline btn-sm" style="color:#dc2626;border-color:#fca5a5;">Logout</a>
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
        <li class="breadcrumb-item" aria-current="page">Dashboard</li>
      </ol>
    </div>
  </nav>

  <!-- Main Content -->
  <main id="main-content" class="main-content">
    <div class="container">

      <!-- Student Welcome Hero -->
      <section style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:1.75rem 2rem;margin-bottom:2rem;box-shadow:var(--shadow-sm);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1.5rem;">
        <div>
          <span class="badge badge-primary" style="margin-bottom:0.5rem;display:inline-block;">Authenticated Student Session</span>
          <h1 style="margin:0.25rem 0 0.5rem;font-size:1.85rem;">Welcome back, <?= htmlspecialchars($profile['full_name'] ?? $_SESSION['full_name']) ?>! 👋</h1>
          <p class="text-muted" style="margin:0;">
            Student ID: <strong><?= htmlspecialchars($profile['student_id'] ?? 'N/A') ?></strong> |
            Course: <strong><?= htmlspecialchars($profile['course'] ?? 'Computer Engineering') ?></strong> |
            Sem: <strong><?= htmlspecialchars((string)($profile['semester'] ?? '3')) ?></strong>
          </p>
        </div>
        <div style="display:flex;gap:0.75rem;">
          <a href="events.php" class="btn btn-primary btn-sm">Explore Campus Events &rarr;</a>
          <a href="profile.html" class="btn btn-outline btn-sm">Profile Details</a>
        </div>
      </section>

      <!-- Key Academic & Portal Metrics -->
      <section style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:1.25rem;margin-bottom:2rem;">
        <div style="background:var(--bg-card);padding:1.25rem;border-radius:10px;border:1px solid var(--border);">
          <div style="color:var(--text-muted);font-size:0.875rem;">Current CGPA</div>
          <div style="font-size:1.85rem;font-weight:700;color:var(--primary);margin-top:0.35rem;"><?= htmlspecialchars((string)($profile['cgpa'] ?? '9.45')) ?> / 10</div>
          <div style="font-size:0.8rem;color:#16a34a;margin-top:0.25rem;">Top 5% in Department</div>
        </div>

        <div style="background:var(--bg-card);padding:1.25rem;border-radius:10px;border:1px solid var(--border);">
          <div style="color:var(--text-muted);font-size:0.875rem;">Registered Events</div>
          <div style="font-size:1.85rem;font-weight:700;color:var(--text);margin-top:0.35rem;"><?= count($registeredEvents) ?></div>
          <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.25rem;">Active campus participations</div>
        </div>

        <div style="background:var(--bg-card);padding:1.25rem;border-radius:10px;border:1px solid var(--border);">
          <div style="color:var(--text-muted);font-size:0.875rem;">Upcoming Events</div>
          <div style="font-size:1.85rem;font-weight:700;color:#2563eb;margin-top:0.35rem;"><?= $totalUpcoming ?></div>
          <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.25rem;">Open for registration</div>
        </div>

        <div style="background:var(--bg-card);padding:1.25rem;border-radius:10px;border:1px solid var(--border);">
          <div style="color:var(--text-muted);font-size:0.875rem;">Account Status</div>
          <div style="font-size:1.35rem;font-weight:700;color:#16a34a;margin-top:0.75rem;">
            <span style="display:inline-block;width:10px;height:10px;background:#16a34a;border-radius:50%;margin-right:6px;"></span>
            Active Student
          </div>
          <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.25rem;">Last Login: <?= htmlspecialchars($profile['last_login'] ?? 'Just now') ?></div>
        </div>
      </section>

      <!-- Registered Events Table -->
      <section style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:1.75rem;margin-bottom:2rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
          <h2 style="margin:0;font-size:1.35rem;">My Event Registrations</h2>
          <a href="events.php" class="btn btn-outline btn-sm">Browse All Events</a>
        </div>

        <?php if (empty($registeredEvents)): ?>
          <p class="text-muted" style="text-align:center;padding:2rem 0;">You have not registered for any campus events yet. <a href="events.php" style="color:var(--primary);">Discover upcoming events &rarr;</a></p>
        <?php else: ?>
          <div style="overflow-x:auto;">
            <table class="data-table" style="width:100%;border-collapse:collapse;font-size:0.95rem;">
              <thead>
                <tr style="border-bottom:2px solid var(--border);text-align:left;">
                  <th style="padding:0.75rem;">Event Title</th>
                  <th style="padding:0.75rem;">Date &amp; Time</th>
                  <th style="padding:0.75rem;">Venue</th>
                  <th style="padding:0.75rem;">Attendance Status</th>
                  <th style="padding:0.75rem;">Registration Date</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($registeredEvents as $reg): ?>
                  <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:0.75rem;font-weight:600;"><?= htmlspecialchars($reg['title']) ?></td>
                    <td style="padding:0.75rem;"><?= htmlspecialchars($reg['event_date']) ?> at <?= htmlspecialchars(substr($reg['event_time'], 0, 5)) ?></td>
                    <td style="padding:0.75rem;"><?= htmlspecialchars($reg['venue']) ?></td>
                    <td style="padding:0.75rem;">
                      <span class="badge" style="background:#dbeafe;color:#1e40af;padding:0.25rem 0.65rem;border-radius:9999px;font-size:0.8rem;font-weight:600;">
                        <?= htmlspecialchars($reg['attendance_status']) ?>
                      </span>
                    </td>
                    <td style="padding:0.75rem;color:var(--text-muted);font-size:0.85rem;"><?= htmlspecialchars(substr($reg['registration_date'], 0, 10)) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

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
