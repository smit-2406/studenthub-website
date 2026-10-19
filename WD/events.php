<?php
/**
 * CHARUSAT - ITUE203: Web Development Frameworks
 * StudentHub Portal - Campus Events Dynamic Catalog
 * Integrates with MySQL database and displays posters uploaded in Practical 12
 */

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

global $conn;

$selectedCategory = trim((string)($_GET['category'] ?? 'all'));
$search = trim((string)($_GET['search'] ?? ''));

$events = [];
$categories = [];

// Try fetching from MySQL database
try {
    $catQuery = $conn->query("SELECT id, name, slug FROM event_categories ORDER BY name ASC");
    if ($catQuery) {
        while ($c = $catQuery->fetch_assoc()) {
            $categories[] = $c;
        }
    }

    $sql = "SELECT e.*, c.name AS category_name, c.slug AS category_slug FROM events e JOIN event_categories c ON e.category_id = c.id WHERE e.status != 'Draft'";
    $binds = [];
    $types = '';

    if ($selectedCategory !== 'all') {
        $sql .= " AND c.slug = ?";
        $types .= 's';
        $binds[] = $selectedCategory;
    }

    if ($search !== '') {
        $sql .= " AND (e.title LIKE ? OR e.venue LIKE ? OR e.description LIKE ?)";
        $like = "%{$search}%";
        $types .= 'sss';
        $binds[] = $like;
        $binds[] = $like;
        $binds[] = $like;
    }

    $sql .= " ORDER BY e.event_date ASC";

    $stmt = $conn->prepare($sql);
    if (!empty($binds)) {
        $stmt->bind_param($types, ...$binds);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $events[] = $row;
    }
    $stmt->close();
} catch (Throwable $e) {
    // Graceful fallback to static JSON
    $jsonFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'events.json';
    if (file_exists($jsonFile)) {
        $events = json_decode((string)file_get_contents($jsonFile), true) ?: [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Discover campus events, technical hackathons, cultural festivals and sports at CHARUSAT." />
  <title>Campus Events &amp; Activities &mdash; CHARUSAT StudentHub</title>
  <link rel="stylesheet" href="css/styles.css" />
  <style>
    .events-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:1.75rem; margin-top:2rem; }
    .event-card { background:var(--bg-card); border-radius:12px; border:1px solid var(--border); overflow:hidden; display:flex; flex-direction:column; box-shadow:var(--shadow-sm); transition:transform 0.2s, box-shadow 0.2s; }
    .event-card:hover { transform:translateY(-4px); box-shadow:var(--shadow-md); }
    .event-card-poster { height:180px; width:100%; object-fit:cover; background:linear-gradient(135deg, var(--primary), var(--secondary)); display:flex; align-items:center; justify-content:center; color:#fff; font-size:2.5rem; }
    .event-card-body { padding:1.25rem; flex:1; display:flex; flex-direction:column; }
    .event-meta { display:flex; flex-wrap:wrap; gap:0.75rem; font-size:0.85rem; color:var(--text-muted); margin:0.75rem 0; }
    .cat-pill { display:inline-block; padding:0.4rem 0.9rem; border-radius:9999px; font-size:0.85rem; font-weight:600; text-decoration:none; background:var(--bg-alt); color:var(--text); border:1px solid var(--border); transition:all 0.2s; }
    .cat-pill.active { background:var(--primary); color:#fff; border-color:var(--primary); }
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
            <li><a href="index.html" class="nav-link">Home</a></li>
            <li><a href="about.html" class="nav-link">About</a></li>
            <li><a href="courses.html" class="nav-link">Courses</a></li>
            <li><a href="events.php" class="nav-link" aria-current="page">Events</a></li>
            <li><a href="explorer.html" class="nav-link">Data Explorer</a></li>
            <li><a href="students.php" class="nav-link">Students</a></li>
            <li><a href="ajax-students.php" class="nav-link">AJAX CRUD</a></li>
            <li><a href="submissions.php" class="nav-link">Submissions</a></li>
            <li><a href="contact.html" class="nav-link">Contact</a></li>
          </ul>
        </nav>
        <div class="nav-actions">
          <button id="theme-toggle-btn" class="btn btn-outline btn-sm" aria-label="Toggle dark mode">🌙 Dark</button>
          <?php if (isLoggedIn()): ?>
            <a href="<?= isAdmin() ? 'admin.php' : 'dashboard.php' ?>" class="btn btn-outline btn-sm">👤 <?= htmlspecialchars($_SESSION['username']) ?></a>
            <a href="logout.php" class="btn btn-outline btn-sm" style="color:#dc2626;border-color:#fca5a5;">Logout</a>
          <?php else: ?>
            <a href="login.php" class="btn btn-outline btn-sm">Login</a>
            <a href="register.php" class="btn btn-primary btn-sm">Register</a>
          <?php endif; ?>
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
        <li class="breadcrumb-item" aria-current="page">Campus Events</li>
      </ol>
    </div>
  </nav>

  <!-- Main Content -->
  <main id="main-content" class="main-content">
    <div class="container">

      <!-- Hero -->
      <section class="page-header" style="text-align:center;">
        <h1 class="page-title">Campus Events &amp; Activities</h1>
        <p class="page-subtitle">Discover technical hackathons, cultural festivals, academic symposia, and athletic tournaments at CHARUSAT.</p>
      </section>

      <!-- Category Filter Pills & Search -->
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
        <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
          <a href="events.php" class="cat-pill <?= $selectedCategory === 'all' ? 'active' : '' ?>">All Categories</a>
          <?php foreach ($categories as $cat): ?>
            <a href="events.php?category=<?= urlencode($cat['slug']) ?>" class="cat-pill <?= $selectedCategory === $cat['slug'] ? 'active' : '' ?>">
              <?= htmlspecialchars($cat['name']) ?>
            </a>
          <?php endforeach; ?>
        </div>

        <form method="get" action="events.php" style="display:flex;gap:0.5rem;">
          <?php if ($selectedCategory !== 'all'): ?>
            <input type="hidden" name="category" value="<?= htmlspecialchars($selectedCategory) ?>" />
          <?php endif; ?>
          <input type="text" name="search" class="form-control" placeholder="Search events..." value="<?= htmlspecialchars($search) ?>" style="width:200px;" />
          <button type="submit" class="btn btn-primary btn-sm">Search</button>
        </form>
      </div>

      <!-- Events Grid -->
      <?php if (empty($events)): ?>
        <div style="text-align:center;padding:4rem 1rem;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;">
          <div style="font-size:3rem;margin-bottom:1rem;">📅</div>
          <h3>No events found</h3>
          <p class="text-muted">No campus events match your selected category or search keyword.</p>
          <a href="events.php" class="btn btn-primary btn-sm" style="margin-top:1rem;">Clear Filters</a>
        </div>
      <?php else: ?>
        <div class="events-grid">
          <?php foreach ($events as $ev): ?>
            <article class="event-card">
              <?php if (!empty($ev['poster_path']) && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $ev['poster_path'])): ?>
                <img src="<?= htmlspecialchars($ev['poster_path']) ?>" alt="<?= htmlspecialchars($ev['title']) ?>" class="event-card-poster" />
              <?php else: ?>
                <div class="event-card-poster">
                  <span>🎓</span>
                </div>
              <?php endif; ?>

              <div class="event-card-body">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                  <span class="badge badge-primary" style="font-size:0.75rem;"><?= htmlspecialchars($ev['category_name'] ?? ($ev['category'] ?? 'Event')) ?></span>
                  <span style="font-size:0.8rem;color:var(--text-muted);font-weight:600;"><?= htmlspecialchars($ev['status'] ?? 'Upcoming') ?></span>
                </div>

                <h3 style="margin:0.75rem 0 0.25rem;font-size:1.25rem;"><?= htmlspecialchars($ev['title']) ?></h3>
                <p class="text-muted" style="font-size:0.9rem;margin:0;line-height:1.5;">
                  <?= htmlspecialchars(substr($ev['description'], 0, 120)) ?>...
                </p>

                <div class="event-meta">
                  <span>📅 <?= htmlspecialchars($ev['event_date'] ?? $ev['date'] ?? '') ?></span>
                  <span>📍 <?= htmlspecialchars($ev['venue'] ?? $ev['location'] ?? 'Campus') ?></span>
                  <span>👥 <?= (int)($ev['max_participants'] ?? $ev['capacity'] ?? 100) ?> Seats</span>
                </div>

                <div style="margin-top:auto;padding-top:1rem;display:flex;gap:0.5rem;">
                  <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php" class="btn btn-primary btn-sm btn-block">Enrolled / View in Dashboard</a>
                  <?php else: ?>
                    <a href="login.php" class="btn btn-primary btn-sm btn-block">Login to Register &rarr;</a>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

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
