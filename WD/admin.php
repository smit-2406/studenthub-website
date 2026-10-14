<?php
/**
 * CHARUSAT - ITUE203: Web Development Frameworks
 * StudentHub Portal - Admin Dashboard & Moderation (Practical 10)
 * Protected Route: Requires active Admin session
 */

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

requireRole('admin');

global $conn;

// Aggregate Statistics from MySQL Database
$studentCount = 0;
$res = $conn->query("SELECT COUNT(*) AS total FROM students");
if ($res) $studentCount = (int)$res->fetch_assoc()['total'];

$eventCount = 0;
$res = $conn->query("SELECT COUNT(*) AS total FROM events");
if ($res) $eventCount = (int)$res->fetch_assoc()['total'];

$userCount = 0;
$res = $conn->query("SELECT COUNT(*) AS total FROM users");
if ($res) $userCount = (int)$res->fetch_assoc()['total'];

$regCount = 0;
$res = $conn->query("SELECT COUNT(*) AS total FROM registrations");
if ($res) $regCount = (int)$res->fetch_assoc()['total'];

// Recent Audit Logs
$auditLogs = [];
$logRes = $conn->query("SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.id DESC LIMIT 8");
if ($logRes) {
    while ($row = $logRes->fetch_assoc()) {
        $auditLogs[] = $row;
    }
}

// Recent Registered Students
$recentStudents = [];
$stuRes = $conn->query("SELECT id, student_id, full_name, email, course, status, created_at FROM students ORDER BY id DESC LIMIT 5");
if ($stuRes) {
    while ($row = $stuRes->fetch_assoc()) {
        $recentStudents[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="CHARUSAT StudentHub Admin Portal Dashboard and System Metrics." />
  <title>Admin Portal Dashboard &mdash; CHARUSAT StudentHub</title>
  <link rel="stylesheet" href="css/styles.css" />
  <style>
    .stat-card { background:var(--bg-card); padding:1.5rem; border-radius:12px; border:1px solid var(--border); box-shadow:var(--shadow-sm); }
    .stat-number { font-size:2.25rem; font-weight:800; margin:0.35rem 0; color:var(--primary); }
    .quick-action-card { background:var(--bg-card); padding:1.25rem; border-radius:10px; border:1px solid var(--border); display:flex; align-items:center; gap:1rem; text-decoration:none; color:var(--text); transition:all 0.2s; }
    .quick-action-card:hover { transform:translateY(-2px); border-color:var(--primary); box-shadow:var(--shadow-md); }
  </style>
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
            <li><a href="admin.php" class="nav-link" aria-current="page">Admin Dashboard</a></li>
            <li><a href="students.php" class="nav-link">Students CRUD</a></li>
            <li><a href="manage-events.php" class="nav-link">Manage Events</a></li>
            <li><a href="submissions.php" class="nav-link">Submissions</a></li>
            <li><a href="explorer.html" class="nav-link">Data Explorer</a></li>
          </ul>
        </nav>
        <div class="nav-actions">
          <button id="theme-toggle-btn" class="btn btn-outline btn-sm" aria-label="Toggle dark mode">🌙 Dark</button>
          <span style="font-size:0.85rem;font-weight:600;padding:0.35rem 0.65rem;background:#fee2e2;color:#991b1b;border-radius:6px;border:1px solid #fca5a5;">
            🛡️ Admin: <?= htmlspecialchars($_SESSION['username']) ?>
          </span>
          <a href="logout.php" class="btn btn-outline btn-sm" style="color:#dc2626;border-color:#fca5a5;">Logout</a>
        </div>
      </div>
    </div>
  </header>

  <!-- Breadcrumb -->
  <nav class="breadcrumb-wrapper" aria-label="Breadcrumb">
    <div class="container">
      <ol class="breadcrumb-list" role="list">
        <li class="breadcrumb-item"><a href="index.html">Home</a></li>
        <li class="breadcrumb-separator" aria-hidden="true">›</li>
        <li class="breadcrumb-item" aria-current="page">Admin Portal</li>
      </ol>
    </div>
  </nav>

  <!-- Main Content -->
  <main id="main-content" class="main-content">
    <div class="container">

      <!-- Hero Banner -->
      <section style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:1.75rem 2rem;margin-bottom:2rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1.5rem;">
        <div>
          <span class="badge" style="background:#fee2e2;color:#991b1b;padding:0.25rem 0.65rem;border-radius:9999px;font-weight:700;font-size:0.8rem;">Role: System Administrator</span>
          <h1 style="margin:0.35rem 0 0.5rem;font-size:1.85rem;">Admin Control Center</h1>
          <p class="text-muted" style="margin:0;">Manage student directories, campus event posters, and system audit logs.</p>
        </div>
        <div style="display:flex;gap:0.75rem;">
          <a href="students.php" class="btn btn-primary btn-sm">👥 Manage Students</a>
          <a href="manage-events.php" class="btn btn-outline btn-sm">📅 Manage Events</a>
        </div>
      </section>

      <!-- Aggregated Metric Cards -->
      <section style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:1.25rem;margin-bottom:2rem;">
        <div class="stat-card">
          <div style="font-size:0.875rem;color:var(--text-muted);">Enrolled Students</div>
          <div class="stat-number"><?= $studentCount ?></div>
          <a href="students.php" style="font-size:0.8rem;color:var(--primary);font-weight:600;">View Directory &rarr;</a>
        </div>

        <div class="stat-card">
          <div style="font-size:0.875rem;color:var(--text-muted);">Campus Events</div>
          <div class="stat-number" style="color:#2563eb;"><?= $eventCount ?></div>
          <a href="manage-events.php" style="font-size:0.8rem;color:#2563eb;font-weight:600;">Manage Events &rarr;</a>
        </div>

        <div class="stat-card">
          <div style="font-size:0.875rem;color:var(--text-muted);">Registered Users</div>
          <div class="stat-number" style="color:#16a34a;"><?= $userCount ?></div>
          <div style="font-size:0.8rem;color:var(--text-muted);">Accounts in DB</div>
        </div>

        <div class="stat-card">
          <div style="font-size:0.875rem;color:var(--text-muted);">Event Participations</div>
          <div class="stat-number" style="color:#d97706;"><?= $regCount ?></div>
          <div style="font-size:0.8rem;color:var(--text-muted);">Active registrations</div>
        </div>
      </section>

      <!-- Quick Action Modules -->
      <section style="margin-bottom:2rem;">
        <h2 style="font-size:1.35rem;margin-bottom:1rem;">Quick Action Modules</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:1rem;">
          <a href="students.php" class="quick-action-card">
            <span style="font-size:2rem;">👥</span>
            <div>
              <strong>Student CRUD</strong>
              <div style="font-size:0.8rem;color:var(--text-muted);">Add, edit, search, and CSV export</div>
            </div>
          </a>

          <a href="manage-events.php" class="quick-action-card">
            <span style="font-size:2rem;">📅</span>
            <div>
              <strong>Event Management</strong>
              <div style="font-size:0.8rem;color:var(--text-muted);">CRUD with poster upload validation</div>
            </div>
          </a>

          <a href="submissions.php" class="quick-action-card">
            <span style="font-size:2rem;">📂</span>
            <div>
              <strong>Offline Submissions</strong>
              <div style="font-size:0.8rem;color:var(--text-muted);">CSV &amp; JSON file storage viewer</div>
            </div>
          </a>

          <a href="explorer.html" class="quick-action-card">
            <span style="font-size:2rem;">📡</span>
            <div>
              <strong>Data Explorer</strong>
              <div style="font-size:0.8rem;color:var(--text-muted);">Fetch API live JSON browser</div>
            </div>
          </a>
        </div>
      </section>

      <!-- Recent Students & Audit Logs Grid -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;">
        <!-- Recent Students -->
        <section style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:1.5rem;">
          <h2 style="font-size:1.25rem;margin:0 0 1rem;">Recent Students Registered</h2>
          <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
              <thead>
                <tr style="border-bottom:1px solid var(--border);text-align:left;">
                  <th style="padding:0.5rem;">Roll</th>
                  <th style="padding:0.5rem;">Name</th>
                  <th style="padding:0.5rem;">Course</th>
                  <th style="padding:0.5rem;">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentStudents as $s): ?>
                  <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:0.5rem;font-family:monospace;font-weight:600;"><?= htmlspecialchars($s['student_id']) ?></td>
                    <td style="padding:0.5rem;"><?= htmlspecialchars($s['full_name']) ?></td>
                    <td style="padding:0.5rem;"><?= htmlspecialchars($s['course']) ?></td>
                    <td style="padding:0.5rem;">
                      <span class="badge" style="background:#dcfce7;color:#166534;font-size:0.75rem;">
                        <?= htmlspecialchars($s['status']) ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Audit Logs -->
        <section style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:1.5rem;">
          <h2 style="font-size:1.25rem;margin:0 0 1rem;">Recent System Audit Logs</h2>
          <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
              <thead>
                <tr style="border-bottom:1px solid var(--border);text-align:left;">
                  <th style="padding:0.5rem;">Timestamp</th>
                  <th style="padding:0.5rem;">Action</th>
                  <th style="padding:0.5rem;">Details</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($auditLogs as $l): ?>
                  <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:0.5rem;color:var(--text-muted);white-space:nowrap;"><?= htmlspecialchars(substr($l['created_at'], 5, 11)) ?></td>
                    <td style="padding:0.5rem;"><code style="font-size:0.8rem;"><?= htmlspecialchars($l['action']) ?></code></td>
                    <td style="padding:0.5rem;"><?= htmlspecialchars(substr($l['details'] ?? '', 0, 40)) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
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
