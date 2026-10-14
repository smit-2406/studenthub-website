<?php
/**
 * CHARUSAT - ITUE203: Web Development Frameworks
 * StudentHub Portal - Event Management CRUD with Poster Upload (Practical 12)
 * Features:
 * - Admin-Only Access Control (RBAC)
 * - Full CRUD: Create, View, Update, Delete Events
 * - Secure Poster Upload with File Validation (MIME type, extension, size <= 2MB)
 * - Cryptographic File Renaming & Directory Protection
 * - Automatic Cleanup of Old Images on Update/Delete
 * - Category and Status Filtering (Intermediate Extension)
 * - Audit Log Recording for Event Modifications (Advanced Extension)
 */

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

// Practical 12 Requirement: Event Management functionality for admin users
requireRole('admin');

global $conn;

$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Uploads configuration
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'events' . DIRECTORY_SEPARATOR;
$maxFileSize = 2 * 1024 * 1024; // 2 MB
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

/**
 * Validates and processes event poster upload
 * @return array{success: bool, filename: ?string, error: ?string}
 */
function handlePosterUpload(array $file, string $targetDir, int $maxSize, array $allowedMimes, array $allowedExts): array
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'filename' => null, 'error' => 'Upload error code: ' . $file['error']];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'filename' => null, 'error' => 'File size exceeds maximum limit of 2MB.'];
    }

    // Inspect real MIME type using PHP Fileinfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? finfo_file($finfo, $file['tmp_name']) : mime_content_type($file['tmp_name']);
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!in_array($mimeType, $allowedMimes, true)) {
        return ['success' => false, 'filename' => null, 'error' => 'Invalid file format. Allowed types: JPG, PNG, WEBP (Detected: ' . htmlspecialchars((string)$mimeType) . ').'];
    }

    // Check extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExts, true)) {
        return ['success' => false, 'filename' => null, 'error' => 'Invalid file extension. Only .jpg, .jpeg, .png, and .webp are accepted.'];
    }

    // Cryptographically secure unique filename (Practical 12 Advanced requirement)
    $uniqueName = 'event_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
    $destination = $targetDir . $uniqueName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'filename' => null, 'error' => 'Failed to save uploaded poster file.'];
    }

    return ['success' => true, 'filename' => 'uploads/events/' . $uniqueName, 'error' => null];
}

// Fetch categories for forms and filters
$categoryRows = [];
$catQuery = $conn->query("SELECT id, name, slug FROM event_categories ORDER BY name ASC");
if ($catQuery) {
    while ($c = $catQuery->fetch_assoc()) {
        $categoryRows[] = $c;
    }
}

// Process POST CRUD actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($submittedCsrf)) {
        $_SESSION['flash_error'] = 'Security session expired. Please try again.';
        header('Location: manage-events.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // 1. ADD NEW EVENT
    if ($action === 'create_event') {
        $title       = trim((string)($_POST['title'] ?? ''));
        $categoryId  = (int)($_POST['category_id'] ?? 0);
        $description = trim((string)($_POST['description'] ?? ''));
        $eventDate   = trim((string)($_POST['event_date'] ?? ''));
        $eventTime   = trim((string)($_POST['event_time'] ?? ''));
        $venue       = trim((string)($_POST['venue'] ?? ''));
        $maxSeats    = (int)($_POST['max_participants'] ?? 100);
        $organizer   = trim((string)($_POST['organizer'] ?? 'CHARUSAT'));
        $status      = trim((string)($_POST['status'] ?? 'Upcoming'));

        if ($title === '' || $categoryId <= 0 || $eventDate === '' || $eventTime === '' || $venue === '') {
            $_SESSION['flash_error'] = 'Please fill in all required event details.';
        } else {
            // Process file upload
            $uploadResult = handlePosterUpload($_FILES['poster'] ?? [], $uploadDir, $maxFileSize, $allowedMimeTypes, $allowedExtensions);

            if (!$uploadResult['success']) {
                $_SESSION['flash_error'] = $uploadResult['error'];
            } else {
                $posterPath = $uploadResult['filename'];
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));

                $stmt = $conn->prepare("INSERT INTO events (category_id, title, slug, description, event_date, event_time, venue, max_participants, organizer, poster_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssssisss", $categoryId, $title, $slug, $description, $eventDate, $eventTime, $venue, $maxSeats, $organizer, $posterPath, $status);

                if ($stmt->execute()) {
                    $newEventId = $conn->insert_id;
                    $_SESSION['flash_success'] = "Campus event '{$title}' was added successfully with poster validation!";

                    // Audit Log
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    $adminId = (int)$_SESSION['user_id'];
                    $auditStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'EVENT_CREATED', ?, ?)");
                    $detailMsg = "Event #{$newEventId} '{$title}' created";
                    $auditStmt->bind_param("iss", $adminId, $detailMsg, $ip);
                    $auditStmt->execute();
                    $auditStmt->close();
                } else {
                    $_SESSION['flash_error'] = 'Failed to create event: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
        header('Location: manage-events.php');
        exit;
    }

    // 2. UPDATE EXISTING EVENT
    if ($action === 'update_event') {
        $id          = (int)($_POST['event_id'] ?? 0);
        $title       = trim((string)($_POST['title'] ?? ''));
        $categoryId  = (int)($_POST['category_id'] ?? 0);
        $description = trim((string)($_POST['description'] ?? ''));
        $eventDate   = trim((string)($_POST['event_date'] ?? ''));
        $eventTime   = trim((string)($_POST['event_time'] ?? ''));
        $venue       = trim((string)($_POST['venue'] ?? ''));
        $maxSeats    = (int)($_POST['max_participants'] ?? 100);
        $organizer   = trim((string)($_POST['organizer'] ?? 'CHARUSAT'));
        $status      = trim((string)($_POST['status'] ?? 'Upcoming'));

        if ($id <= 0 || $title === '' || $categoryId <= 0) {
            $_SESSION['flash_error'] = 'Invalid event record or missing required fields.';
        } else {
            // Check current poster
            $currStmt = $conn->prepare("SELECT poster_path FROM events WHERE id = ? LIMIT 1");
            $currStmt->bind_param("i", $id);
            $currStmt->execute();
            $currEvent = $currStmt->get_result()->fetch_assoc();
            $currStmt->close();

            $existingPoster = $currEvent['poster_path'] ?? null;
            $posterPath = $existingPoster;

            // Check if new poster uploaded
            if (!empty($_FILES['poster']['name'])) {
                $uploadResult = handlePosterUpload($_FILES['poster'], $uploadDir, $maxFileSize, $allowedMimeTypes, $allowedExtensions);
                if (!$uploadResult['success']) {
                    $_SESSION['flash_error'] = $uploadResult['error'];
                    header('Location: manage-events.php');
                    exit;
                }
                $posterPath = $uploadResult['filename'];

                // Remove old poster from disk if replaced
                if ($existingPoster && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $existingPoster)) {
                    @unlink(__DIR__ . DIRECTORY_SEPARATOR . $existingPoster);
                }
            }

            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
            $stmt = $conn->prepare("UPDATE events SET category_id = ?, title = ?, slug = ?, description = ?, event_date = ?, event_time = ?, venue = ?, max_participants = ?, organizer = ?, poster_path = ?, status = ? WHERE id = ?");
            $stmt->bind_param("issssssisssi", $categoryId, $title, $slug, $description, $eventDate, $eventTime, $venue, $maxSeats, $organizer, $posterPath, $status, $id);

            if ($stmt->execute()) {
                $_SESSION['flash_success'] = "Event '{$title}' updated successfully!";

                // Audit Log
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $adminId = (int)$_SESSION['user_id'];
                $auditStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'EVENT_UPDATED', ?, ?)");
                $detailMsg = "Event #{$id} updated";
                $auditStmt->bind_param("iss", $adminId, $detailMsg, $ip);
                $auditStmt->execute();
                $auditStmt->close();
            } else {
                $_SESSION['flash_error'] = 'Update failed: ' . $stmt->error;
            }
            $stmt->close();
        }
        header('Location: manage-events.php');
        exit;
    }

    // 3. DELETE EVENT
    if ($action === 'delete_event') {
        $id = (int)($_POST['event_id'] ?? 0);
        if ($id > 0) {
            // Find poster to unlink
            $pStmt = $conn->prepare("SELECT title, poster_path FROM events WHERE id = ? LIMIT 1");
            $pStmt->bind_param("i", $id);
            $pStmt->execute();
            $ev = $pStmt->get_result()->fetch_assoc();
            $pStmt->close();

            if ($ev) {
                if (!empty($ev['poster_path']) && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $ev['poster_path'])) {
                    @unlink(__DIR__ . DIRECTORY_SEPARATOR . $ev['poster_path']);
                }

                $delStmt = $conn->prepare("DELETE FROM events WHERE id = ?");
                $delStmt->bind_param("i", $id);
                if ($delStmt->execute()) {
                    $_SESSION['flash_success'] = "Event '{$ev['title']}' was deleted successfully!";

                    // Audit Log
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    $adminId = (int)$_SESSION['user_id'];
                    $auditStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'EVENT_DELETED', ?, ?)");
                    $detailMsg = "Event #{$id} '{$ev['title']}' deleted";
                    $auditStmt->bind_param("iss", $adminId, $detailMsg, $ip);
                    $auditStmt->execute();
                    $auditStmt->close();
                } else {
                    $_SESSION['flash_error'] = 'Delete failed: ' . $delStmt->error;
                }
                $delStmt->close();
            }
        }
        header('Location: manage-events.php');
        exit;
    }
}

// Search and Filter logic
$searchQuery    = trim((string)($_GET['q'] ?? ''));
$categoryFilter = (int)($_GET['category'] ?? 0);
$statusFilter   = trim((string)($_GET['status'] ?? 'all'));

$whereParts = [];
$bindTypes = '';
$bindArgs = [];

if ($searchQuery !== '') {
    $whereParts[] = "(e.title LIKE ? OR e.venue LIKE ? OR e.organizer LIKE ?)";
    $like = "%{$searchQuery}%";
    $bindTypes .= 'sss';
    $bindArgs[] = $like;
    $bindArgs[] = $like;
    $bindArgs[] = $like;
}

if ($categoryFilter > 0) {
    $whereParts[] = "e.category_id = ?";
    $bindTypes .= 'i';
    $bindArgs[] = $categoryFilter;
}

if ($statusFilter !== '' && $statusFilter !== 'all') {
    $whereParts[] = "e.status = ?";
    $bindTypes .= 's';
    $bindArgs[] = $statusFilter;
}

$sql = "SELECT e.*, c.name AS category_name FROM events e JOIN event_categories c ON e.category_id = c.id";
if (!empty($whereParts)) {
    $sql .= " WHERE " . implode(" AND ", $whereParts);
}
$sql .= " ORDER BY e.event_date ASC";

$stmt = $conn->prepare($sql);
if (!empty($bindArgs)) {
    $stmt->bind_param($bindTypes, ...$bindArgs);
}
$stmt->execute();
$eventsResult = $stmt->get_result();
$events = [];
while ($row = $eventsResult->fetch_assoc()) {
    $events[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="CHARUSAT StudentHub – Event Management CRUD with File Upload & Poster Validation" />
  <title>Manage Events &mdash; CHARUSAT StudentHub Admin</title>
  <link rel="stylesheet" href="css/styles.css" />
  <style>
    .crud-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem; }
    .filter-bar { background:var(--bg-card); padding:1.25rem; border-radius:10px; border:1px solid var(--border); margin-bottom:1.5rem; display:grid; grid-template-columns:2fr 1fr 1fr auto; gap:0.75rem; align-items:end; }
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:1000; align-items:center; justify-content:center; padding:1rem; }
    .modal-overlay.active { display:flex; }
    .modal-box { background:var(--bg-card); max-width:700px; width:100%; border-radius:12px; padding:2rem; max-height:90vh; overflow-y:auto; box-shadow:var(--shadow-lg); border:1px solid var(--border); }
    .poster-preview { width:60px; height:40px; object-fit:cover; border-radius:4px; border:1px solid var(--border); }
    .poster-placeholder { width:60px; height:40px; background:#e2e8f0; border-radius:4px; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem; color:#64748b; }
    .status-badge-Upcoming { background:#dbeafe; color:#1e40af; }
    .status-badge-Ongoing { background:#dcfce7; color:#166534; }
    .status-badge-Completed { background:#f1f5f9; color:#475569; }
    .status-badge-Cancelled { background:#fee2e2; color:#991b1b; }
    .status-badge-Draft { background:#fef3c7; color:#92400e; }
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
            <li><a href="admin.php" class="nav-link">Admin Dashboard</a></li>
            <li><a href="students.php" class="nav-link">Students CRUD</a></li>
            <li><a href="manage-events.php" class="nav-link" aria-current="page">Manage Events</a></li>
            <li><a href="events.php" class="nav-link" target="_blank">Public Events &nearr;</a></li>
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
        <li class="breadcrumb-item"><a href="admin.php">Admin Portal</a></li>
        <li class="breadcrumb-separator" aria-hidden="true">›</li>
        <li class="breadcrumb-item" aria-current="page">Event Management (CRUD + File Upload)</li>
      </ol>
    </div>
  </nav>

  <!-- Main Content -->
  <main id="main-content" class="main-content">
    <div class="container">

      <div class="crud-header">
        <div>
          <h1 style="margin:0 0 0.25rem;font-size:1.85rem;">Campus Event Management</h1>
          <p class="text-muted" style="margin:0;">MySQL CRUD with Server-side File Upload Validation &bull; Total Events: <strong><?= count($events) ?></strong></p>
        </div>
        <div style="display:flex;gap:0.75rem;">
          <a href="events.php" class="btn btn-outline btn-sm" target="_blank">👁️ View Public Page</a>
          <button type="button" class="btn btn-primary btn-sm" onclick="openAddEventModal()">+ Create New Event</button>
        </div>
      </div>

      <!-- Flash Messages -->
      <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success" style="background:#dcfce7;border:1px solid #22c55e;color:#15803d;padding:0.9rem 1.25rem;border-radius:8px;margin-bottom:1.5rem;" role="status">
          ✓ <?= htmlspecialchars($flashSuccess) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger" style="background:#fee2e2;border:1px solid #ef4444;color:#991b1b;padding:0.9rem 1.25rem;border-radius:8px;margin-bottom:1.5rem;" role="alert">
          ⚠️ <?= htmlspecialchars($flashError) ?>
        </div>
      <?php endif; ?>

      <!-- Filter Bar -->
      <form method="get" action="manage-events.php" class="filter-bar">
        <div>
          <label for="q" style="display:block;font-size:0.8rem;font-weight:600;margin-bottom:0.25rem;">Live Search</label>
          <input type="text" id="q" name="q" class="form-control" placeholder="Search event title, venue, or organizer..." value="<?= htmlspecialchars($searchQuery) ?>" />
        </div>
        <div>
          <label for="category" style="display:block;font-size:0.8rem;font-weight:600;margin-bottom:0.25rem;">Category</label>
          <select id="category" name="category" class="form-control">
            <option value="0">All Categories</option>
            <?php foreach ($categoryRows as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>" <?= $categoryFilter === (int)$cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="status" style="display:block;font-size:0.8rem;font-weight:600;margin-bottom:0.25rem;">Status</label>
          <select id="status" name="status" class="form-control">
            <option value="all">All Statuses</option>
            <option value="Upcoming" <?= $statusFilter === 'Upcoming' ? 'selected' : '' ?>>Upcoming</option>
            <option value="Ongoing" <?= $statusFilter === 'Ongoing' ? 'selected' : '' ?>>Ongoing</option>
            <option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
            <option value="Cancelled" <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            <option value="Draft" <?= $statusFilter === 'Draft' ? 'selected' : '' ?>>Draft</option>
          </select>
        </div>
        <div style="display:flex;gap:0.35rem;">
          <button type="submit" class="btn btn-primary" style="padding:0.6rem 1rem;">Filter</button>
          <a href="manage-events.php" class="btn btn-outline" style="padding:0.6rem 0.85rem;" title="Reset filters">Reset</a>
        </div>
      </form>

      <!-- Events Table -->
      <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow-sm);">
        <div style="overflow-x:auto;">
          <table class="data-table" style="width:100%;border-collapse:collapse;font-size:0.9rem;">
            <thead>
              <tr style="background:var(--bg-alt);border-bottom:2px solid var(--border);text-align:left;">
                <th style="padding:0.75rem 1rem;">Poster</th>
                <th style="padding:0.75rem 1rem;">Event Title</th>
                <th style="padding:0.75rem 1rem;">Category</th>
                <th style="padding:0.75rem 1rem;">Date &amp; Time</th>
                <th style="padding:0.75rem 1rem;">Venue</th>
                <th style="padding:0.75rem 1rem;">Capacity</th>
                <th style="padding:0.75rem 1rem;">Status</th>
                <th style="padding:0.75rem 1rem;text-align:center;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($events)): ?>
                <tr>
                  <td colspan="8" style="text-align:center;padding:2.5rem;color:var(--text-muted);">
                    No events found matching your criteria.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($events as $ev): ?>
                  <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:0.75rem 1rem;">
                      <?php if (!empty($ev['poster_path']) && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $ev['poster_path'])): ?>
                        <img src="<?= htmlspecialchars($ev['poster_path']) ?>" alt="Poster" class="poster-preview" />
                      <?php else: ?>
                        <div class="poster-placeholder">No Image</div>
                      <?php endif; ?>
                    </td>
                    <td style="padding:0.75rem 1rem;">
                      <strong><?= htmlspecialchars($ev['title']) ?></strong><br>
                      <small class="text-muted">By <?= htmlspecialchars($ev['organizer']) ?></small>
                    </td>
                    <td style="padding:0.75rem 1rem;">
                      <span class="badge badge-primary" style="font-size:0.75rem;"><?= htmlspecialchars($ev['category_name']) ?></span>
                    </td>
                    <td style="padding:0.75rem 1rem;">
                      <div><?= htmlspecialchars($ev['event_date']) ?></div>
                      <small class="text-muted"><?= htmlspecialchars(substr($ev['event_time'], 0, 5)) ?> IST</small>
                    </td>
                    <td style="padding:0.75rem 1rem;"><?= htmlspecialchars($ev['venue']) ?></td>
                    <td style="padding:0.75rem 1rem;font-weight:600;"><?= (int)$ev['max_participants'] ?> seats</td>
                    <td style="padding:0.75rem 1rem;">
                      <span class="badge status-badge-<?= htmlspecialchars($ev['status']) ?>" style="padding:0.25rem 0.65rem;border-radius:9999px;font-size:0.75rem;font-weight:700;">
                        <?= htmlspecialchars($ev['status']) ?>
                      </span>
                    </td>
                    <td style="padding:0.75rem 1rem;text-align:center;">
                      <div style="display:inline-flex;gap:0.35rem;">
                        <button type="button" class="btn btn-outline btn-sm" onclick='openEditEventModal(<?= json_encode($ev, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                          ✏️ Edit
                        </button>
                        <button type="button" class="btn btn-outline btn-sm" style="color:#dc2626;border-color:#fca5a5;" onclick="confirmDeleteEvent(<?= (int)$ev['id'] ?>, '<?= htmlspecialchars(addslashes($ev['title'])) ?>')">
                          🗑️
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>

  <!-- ADD / EDIT EVENT MODAL -->
  <div class="modal-overlay" id="eventModal" role="dialog" aria-modal="true" aria-labelledby="modalEventTitle">
    <div class="modal-box">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
        <h2 id="modalEventTitle" style="margin:0;font-size:1.4rem;">Create New Event</h2>
        <button type="button" class="btn btn-outline btn-sm" onclick="closeEventModal()">&times;</button>
      </div>

      <form action="manage-events.php" method="post" enctype="multipart/form-data" id="modalEventForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>" />
        <input type="hidden" name="action" id="eventFormAction" value="create_event" />
        <input type="hidden" name="event_id" id="event_id" value="" />

        <div class="form-group">
          <label for="ev_title" class="form-label">Event Title <span class="required">*</span></label>
          <input type="text" id="ev_title" name="title" class="form-control" required placeholder="e.g. HackCHARUSAT 2026" />
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group">
            <label for="ev_category_id" class="form-label">Category <span class="required">*</span></label>
            <select id="ev_category_id" name="category_id" class="form-control" required>
              <option value="">-- Choose Category --</option>
              <?php foreach ($categoryRows as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="ev_status" class="form-label">Event Status <span class="required">*</span></label>
            <select id="ev_status" name="status" class="form-control">
              <option value="Upcoming">Upcoming</option>
              <option value="Ongoing">Ongoing</option>
              <option value="Completed">Completed</option>
              <option value="Cancelled">Cancelled</option>
              <option value="Draft">Draft</option>
            </select>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
          <div class="form-group">
            <label for="ev_date" class="form-label">Event Date <span class="required">*</span></label>
            <input type="date" id="ev_date" name="event_date" class="form-control" required />
          </div>
          <div class="form-group">
            <label for="ev_time" class="form-label">Start Time <span class="required">*</span></label>
            <input type="time" id="ev_time" name="event_time" class="form-control" required />
          </div>
          <div class="form-group">
            <label for="ev_seats" class="form-label">Capacity <span class="required">*</span></label>
            <input type="number" id="ev_seats" name="max_participants" class="form-control" min="10" max="5000" value="100" required />
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group">
            <label for="ev_venue" class="form-label">Venue / Location <span class="required">*</span></label>
            <input type="text" id="ev_venue" name="venue" class="form-control" required placeholder="e.g. FTE Auditorium 1" />
          </div>
          <div class="form-group">
            <label for="ev_organizer" class="form-label">Organizer / Department <span class="required">*</span></label>
            <input type="text" id="ev_organizer" name="organizer" class="form-control" required placeholder="e.g. CSPIT CE Department" />
          </div>
        </div>

        <div class="form-group">
          <label for="ev_desc" class="form-label">Event Description <span class="required">*</span></label>
          <textarea id="ev_desc" name="description" class="form-control" rows="3" required placeholder="Detailed overview of event schedule, rules, and benefits..."></textarea>
        </div>

        <!-- Poster Upload with Validation -->
        <div class="form-group" style="background:var(--bg-alt);border:1px dashed var(--border);padding:1rem;border-radius:8px;">
          <label for="ev_poster" class="form-label" style="font-weight:700;">Event Poster Image (File Validation)</label>
          <input type="file" id="ev_poster" name="poster" class="form-control" accept=".jpg,.jpeg,.png,.webp" />
          <small class="text-muted" style="display:block;margin-top:0.35rem;">
            Allowed types: <strong>JPG, JPEG, PNG, WEBP</strong> | Maximum file size: <strong>2 MB</strong>.<br>
            Server securely renames images to prevent path traversal and validates true MIME type.
          </small>
          <div id="currentPosterNotice" style="display:none;margin-top:0.5rem;font-size:0.85rem;color:var(--primary);">
            📷 Existing poster is attached. Uploading a new file will replace it.
          </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:0.75rem;margin-top:1.5rem;">
          <button type="button" class="btn btn-outline" onclick="closeEventModal()">Cancel</button>
          <button type="submit" class="btn btn-primary" id="eventSubmitBtn">Save Event</button>
        </div>
      </form>
    </div>
  </div>

  <!-- DELETE CONFIRMATION MODAL -->
  <div class="modal-overlay" id="deleteEventModal" role="dialog" aria-modal="true">
    <div class="modal-box" style="max-width:480px;text-align:center;">
      <div style="font-size:3rem;margin-bottom:0.5rem;">🗑️</div>
      <h2 style="margin:0 0 0.5rem;">Delete Event</h2>
      <p class="text-muted" id="deleteEventMsg">Are you sure you want to delete this event?</p>
      <form action="manage-events.php" method="post" style="display:flex;gap:0.75rem;justify-content:center;margin-top:1.5rem;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>" />
        <input type="hidden" name="event_id" id="delete_event_id" value="" />
        <input type="hidden" name="action" value="delete_event" />
        <button type="button" class="btn btn-outline" onclick="closeDeleteEventModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" style="background:#dc2626;border-color:#dc2626;">Confirm Delete</button>
      </form>
    </div>
  </div>

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
    function openAddEventModal() {
      document.getElementById('modalEventTitle').textContent = 'Create New Event';
      document.getElementById('eventFormAction').value = 'create_event';
      document.getElementById('event_id').value = '';
      document.getElementById('ev_title').value = '';
      document.getElementById('ev_category_id').value = '';
      document.getElementById('ev_status').value = 'Upcoming';
      document.getElementById('ev_date').value = '';
      document.getElementById('ev_time').value = '09:00';
      document.getElementById('ev_seats').value = '100';
      document.getElementById('ev_venue').value = '';
      document.getElementById('ev_organizer').value = 'CHARUSAT';
      document.getElementById('ev_desc').value = '';
      document.getElementById('ev_poster').value = '';
      document.getElementById('currentPosterNotice').style.display = 'none';
      document.getElementById('eventSubmitBtn').textContent = 'Create Event';
      document.getElementById('eventModal').classList.add('active');
    }

    function openEditEventModal(ev) {
      document.getElementById('modalEventTitle').textContent = 'Edit Event: ' + ev.title;
      document.getElementById('eventFormAction').value = 'update_event';
      document.getElementById('event_id').value = ev.id;
      document.getElementById('ev_title').value = ev.title;
      document.getElementById('ev_category_id').value = ev.category_id;
      document.getElementById('ev_status').value = ev.status;
      document.getElementById('ev_date').value = ev.event_date;
      document.getElementById('ev_time').value = ev.event_time.substring(0, 5);
      document.getElementById('ev_seats').value = ev.max_participants;
      document.getElementById('ev_venue').value = ev.venue;
      document.getElementById('ev_organizer').value = ev.organizer;
      document.getElementById('ev_desc').value = ev.description;
      document.getElementById('ev_poster').value = '';

      if (ev.poster_path) {
        document.getElementById('currentPosterNotice').style.display = 'block';
      } else {
        document.getElementById('currentPosterNotice').style.display = 'none';
      }

      document.getElementById('eventSubmitBtn').textContent = 'Update Event Details';
      document.getElementById('eventModal').classList.add('active');
    }

    function closeEventModal() {
      document.getElementById('eventModal').classList.remove('active');
    }

    function confirmDeleteEvent(id, title) {
      document.getElementById('delete_event_id').value = id;
      document.getElementById('deleteEventMsg').innerHTML = 'Are you sure you want to delete event <strong>' + title + '</strong>? This will also remove the poster image.';
      document.getElementById('deleteEventModal').classList.add('active');
    }

    function closeDeleteEventModal() {
      document.getElementById('deleteEventModal').classList.remove('active');
    }
  </script>
</body>
</html>
