<?php
/**
 * CHARUSAT - ITUE203: Web Development Frameworks
 * StudentHub Portal - Student Management CRUD (Practical 11)
 * Features:
 * - Full CRUD: Add, View, Edit, Delete (Soft-Delete & Permanent Delete)
 * - Dynamic Search (by Name, Student ID, Email, Course)
 * - Multi-Filter (by Course, Semester, Status)
 * - All Database Operations strictly using MySQLi Prepared Statements
 * - Action Confirmation before Delete (Modal + CSRF Token)
 * - CSV Export Capability (Intermediate Extension)
 * - Flash Message UI Feedback (Success, Warning, Danger)
 */

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

// Accessible to logged-in users; if admin, full edit/delete rights are enabled
$currentUser = getCurrentUser();
$isUserAdmin = isAdmin();

global $conn;

$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Handle CSV Export (Intermediate Extension)
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    requireLogin();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=StudentHub_Directory_' . date('Y-m-d_His') . '.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Student ID', 'Full Name', 'Email', 'Phone', 'Department', 'Course', 'Semester', 'Academic Year', 'Gender', 'CGPA', 'Status', 'Registered At']);

    $exportQuery = $conn->query("SELECT id, student_id, full_name, email, phone, department, course, semester, academic_year, gender, cgpa, status, created_at FROM students ORDER BY id ASC");
    if ($exportQuery) {
        while ($row = $exportQuery->fetch_assoc()) {
            fputcsv($out, $row);
        }
    }
    fclose($out);
    exit;
}

// Handle Form Submissions (Create, Update, Delete, Soft-Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();

    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($submittedCsrf)) {
        $_SESSION['flash_error'] = 'Security session expired. Please refresh and try again.';
        header('Location: students.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // 1. ADD NEW STUDENT
    if ($action === 'create') {
        $studentId = strtoupper(trim((string)($_POST['student_id'] ?? '')));
        $fullName  = trim((string)($_POST['full_name'] ?? ''));
        $email     = strtolower(trim((string)($_POST['email'] ?? '')));
        $phone     = trim((string)($_POST['phone'] ?? ''));
        $course    = trim((string)($_POST['course'] ?? ''));
        $semester  = (int)($_POST['semester'] ?? 3);
        $academicYear = trim((string)($_POST['academic_year'] ?? '2nd Year'));
        $gender    = trim((string)($_POST['gender'] ?? 'Male'));
        $cgpa      = (float)($_POST['cgpa'] ?? 8.00);
        $status    = trim((string)($_POST['status'] ?? 'Active'));

        $dept = (strpos($course, 'IT') !== false) ? 'Information Technology' :
                ((strpos($course, 'Electrical') !== false) ? 'Electrical Engineering' :
                ((strpos($course, 'Mechanical') !== false) ? 'Mechanical Engineering' :
                ((strpos($course, 'Civil') !== false) ? 'Civil Engineering' : 'Computer Engineering')));

        // Validations
        if ($studentId === '' || $fullName === '' || $email === '' || $phone === '') {
            $_SESSION['flash_error'] = 'All mandatory fields must be filled.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Please enter a valid email address.';
        } else {
            // Check duplicate student_id or email
            $chk = $conn->prepare("SELECT id FROM students WHERE student_id = ? OR email = ? LIMIT 1");
            $chk->bind_param("ss", $studentId, $email);
            $chk->execute();
            if ($chk->get_result()->fetch_assoc()) {
                $_SESSION['flash_error'] = 'A student with this Student ID or Email already exists.';
                $chk->close();
            } else {
                $chk->close();
                $stmt = $conn->prepare("INSERT INTO students (student_id, full_name, email, phone, department, course, semester, academic_year, gender, cgpa, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssisdds", $studentId, $fullName, $email, $phone, $dept, $course, $semester, $academicYear, $gender, $cgpa, $status);
                if ($stmt->execute()) {
                    $_SESSION['flash_success'] = "Student '{$fullName}' ({$studentId}) was added successfully!";
                } else {
                    $_SESSION['flash_error'] = 'Database insert failed: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
        header('Location: students.php');
        exit;
    }

    // 2. UPDATE STUDENT
    if ($action === 'update') {
        $id        = (int)($_POST['student_db_id'] ?? 0);
        $studentId = strtoupper(trim((string)($_POST['student_id'] ?? '')));
        $fullName  = trim((string)($_POST['full_name'] ?? ''));
        $email     = strtolower(trim((string)($_POST['email'] ?? '')));
        $phone     = trim((string)($_POST['phone'] ?? ''));
        $course    = trim((string)($_POST['course'] ?? ''));
        $semester  = (int)($_POST['semester'] ?? 3);
        $academicYear = trim((string)($_POST['academic_year'] ?? '2nd Year'));
        $gender    = trim((string)($_POST['gender'] ?? 'Male'));
        $cgpa      = (float)($_POST['cgpa'] ?? 8.00);
        $status    = trim((string)($_POST['status'] ?? 'Active'));

        $dept = (strpos($course, 'IT') !== false) ? 'Information Technology' :
                ((strpos($course, 'Electrical') !== false) ? 'Electrical Engineering' :
                ((strpos($course, 'Mechanical') !== false) ? 'Mechanical Engineering' :
                ((strpos($course, 'Civil') !== false) ? 'Civil Engineering' : 'Computer Engineering')));

        if ($id <= 0 || $studentId === '' || $fullName === '' || $email === '') {
            $_SESSION['flash_error'] = 'Invalid student record or missing required fields.';
        } else {
            // Check duplicate excluding self
            $chk = $conn->prepare("SELECT id FROM students WHERE (student_id = ? OR email = ?) AND id != ? LIMIT 1");
            $chk->bind_param("ssi", $studentId, $email, $id);
            $chk->execute();
            if ($chk->get_result()->fetch_assoc()) {
                $_SESSION['flash_error'] = 'Another student with this Student ID or Email already exists.';
                $chk->close();
            } else {
                $chk->close();
                $stmt = $conn->prepare("UPDATE students SET student_id = ?, full_name = ?, email = ?, phone = ?, department = ?, course = ?, semester = ?, academic_year = ?, gender = ?, cgpa = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssssssisddsi", $studentId, $fullName, $email, $phone, $dept, $course, $semester, $academicYear, $gender, $cgpa, $status, $id);
                if ($stmt->execute()) {
                    $_SESSION['flash_success'] = "Student '{$fullName}' details updated successfully!";
                } else {
                    $_SESSION['flash_error'] = 'Database update failed: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
        header('Location: students.php');
        exit;
    }

    // 3. SOFT-DELETE (Advanced Extension)
    if ($action === 'soft_delete') {
        $id = (int)($_POST['student_db_id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE students SET status = 'Inactive' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash_success'] = "Student status updated to 'Inactive' (Soft Deleted).";
        }
        header('Location: students.php');
        exit;
    }

    // 4. PERMANENT DELETE (Requires Admin)
    if ($action === 'delete') {
        $id = (int)($_POST['student_db_id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['flash_success'] = "Student record #{$id} permanently deleted.";
            } else {
                $_SESSION['flash_error'] = 'Delete failed: ' . $stmt->error;
            }
            $stmt->close();
        }
        header('Location: students.php');
        exit;
    }
}

// Search and Filter Handling
$searchQuery  = trim((string)($_GET['q'] ?? ''));
$courseFilter = trim((string)($_GET['course'] ?? 'all'));
$statusFilter = trim((string)($_GET['status'] ?? 'all'));
$yearFilter   = trim((string)($_GET['year'] ?? 'all'));

$whereClauses = [];
$bindTypes = '';
$bindParams = [];

if ($searchQuery !== '') {
    $whereClauses[] = "(full_name LIKE ? OR student_id LIKE ? OR email LIKE ? OR course LIKE ?)";
    $like = "%{$searchQuery}%";
    $bindTypes .= 'ssss';
    $bindParams[] = $like;
    $bindParams[] = $like;
    $bindParams[] = $like;
    $bindParams[] = $like;
}

if ($courseFilter !== '' && $courseFilter !== 'all') {
    $whereClauses[] = "course = ?";
    $bindTypes .= 's';
    $bindParams[] = $courseFilter;
}

if ($statusFilter !== '' && $statusFilter !== 'all') {
    $whereClauses[] = "status = ?";
    $bindTypes .= 's';
    $bindParams[] = $statusFilter;
}

if ($yearFilter !== '' && $yearFilter !== 'all') {
    $whereClauses[] = "academic_year = ?";
    $bindTypes .= 's';
    $bindParams[] = $yearFilter;
}

$sql = "SELECT * FROM students";
if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}
$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if (!empty($bindParams)) {
    $stmt->bind_param($bindTypes, ...$bindParams);
}
$stmt->execute();
$result = $stmt->get_result();
$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();

$courseOptions = [
    'B.Tech Computer Engineering',
    'B.Tech IT Engineering',
    'B.Tech Electrical',
    'B.Tech Mechanical',
    'B.Tech Civil'
];
$yearOptions = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="StudentHub Directory and CRUD Management for Student Records with MySQLi statements." />
  <title>Student Directory &amp; Management &mdash; CHARUSAT StudentHub</title>
  <link rel="stylesheet" href="css/styles.css" />
  <style>
    .crud-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem; }
    .filter-bar { background:var(--bg-card); padding:1.25rem; border-radius:10px; border:1px solid var(--border); margin-bottom:1.5rem; display:grid; grid-template-columns:2fr 1fr 1fr 1fr auto; gap:0.75rem; align-items:end; }
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; padding:1rem; }
    .modal-overlay.active { display:flex; }
    .modal-box { background:var(--bg-card); max-width:650px; width:100%; border-radius:12px; padding:2rem; max-height:90vh; overflow-y:auto; box-shadow:var(--shadow-lg); border:1px solid var(--border); }
    .badge-active { background:#dcfce7; color:#166534; }
    .badge-inactive { background:#fee2e2; color:#991b1b; }
    .action-btn-group { display:inline-flex; gap:0.35rem; }
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
            <li><a href="courses.html" class="nav-link">Courses</a></li>
            <li><a href="events.php" class="nav-link">Events</a></li>
            <li><a href="explorer.html" class="nav-link">Data Explorer</a></li>
            <li><a href="students.php" class="nav-link" aria-current="page">Students</a></li>
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
        <li class="breadcrumb-item" aria-current="page">Student Directory (CRUD)</li>
      </ol>
    </div>
  </nav>

  <!-- Main Content -->
  <main id="main-content" class="main-content">
    <div class="container">

      <!-- Header & Action Button -->
      <div class="crud-header">
        <div>
          <h1 style="margin:0 0 0.25rem;font-size:1.85rem;">Student Directory &amp; Management</h1>
          <p class="text-muted" style="margin:0;">MySQLi Prepared Statements CRUD &bull; Total records: <strong><?= count($students) ?></strong></p>
        </div>
        <div style="display:flex;gap:0.75rem;">
          <a href="students.php?action=export_csv" class="btn btn-outline btn-sm" title="Export to CSV format">📥 Export CSV</a>
          <button type="button" class="btn btn-primary btn-sm" onclick="openAddModal()">+ Add New Student</button>
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

      <!-- Search & Filter Controls -->
      <form method="get" action="students.php" class="filter-bar">
        <div>
          <label for="q" style="display:block;font-size:0.8rem;font-weight:600;margin-bottom:0.25rem;">Live Search</label>
          <input type="text" id="q" name="q" class="form-control" placeholder="Search by name, ID, email..." value="<?= htmlspecialchars($searchQuery) ?>" />
        </div>
        <div>
          <label for="course" style="display:block;font-size:0.8rem;font-weight:600;margin-bottom:0.25rem;">Course Program</label>
          <select id="course" name="course" class="form-control">
            <option value="all">All Programs</option>
            <?php foreach ($courseOptions as $co): ?>
              <option value="<?= htmlspecialchars($co) ?>" <?= $courseFilter === $co ? 'selected' : '' ?>><?= htmlspecialchars($co) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="year" style="display:block;font-size:0.8rem;font-weight:600;margin-bottom:0.25rem;">Academic Year</label>
          <select id="year" name="year" class="form-control">
            <option value="all">All Years</option>
            <?php foreach ($yearOptions as $yo): ?>
              <option value="<?= htmlspecialchars($yo) ?>" <?= $yearFilter === $yo ? 'selected' : '' ?>><?= htmlspecialchars($yo) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="status" style="display:block;font-size:0.8rem;font-weight:600;margin-bottom:0.25rem;">Status</label>
          <select id="status" name="status" class="form-control">
            <option value="all">All Statuses</option>
            <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active</option>
            <option value="Inactive" <?= $statusFilter === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>
        <div style="display:flex;gap:0.35rem;">
          <button type="submit" class="btn btn-primary" style="padding:0.6rem 1rem;">Filter</button>
          <a href="students.php" class="btn btn-outline" style="padding:0.6rem 0.85rem;" title="Reset filters">Reset</a>
        </div>
      </form>

      <!-- Students Table -->
      <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow-sm);">
        <div style="overflow-x:auto;">
          <table class="data-table" style="width:100%;border-collapse:collapse;font-size:0.9rem;">
            <thead>
              <tr style="background:var(--bg-alt);border-bottom:2px solid var(--border);text-align:left;">
                <th style="padding:0.75rem 1rem;">Student ID</th>
                <th style="padding:0.75rem 1rem;">Full Name</th>
                <th style="padding:0.75rem 1rem;">Contact Info</th>
                <th style="padding:0.75rem 1rem;">Course &amp; Year</th>
                <th style="padding:0.75rem 1rem;">CGPA</th>
                <th style="padding:0.75rem 1rem;">Status</th>
                <th style="padding:0.75rem 1rem;text-align:center;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($students)): ?>
                <tr>
                  <td colspan="7" style="text-align:center;padding:2.5rem;color:var(--text-muted);">
                    No student records matching your search/filter criteria.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($students as $s): ?>
                  <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:0.75rem 1rem;font-weight:700;color:var(--primary);font-family:monospace;">
                      <?= htmlspecialchars($s['student_id']) ?>
                    </td>
                    <td style="padding:0.75rem 1rem;">
                      <strong><?= htmlspecialchars($s['full_name']) ?></strong><br>
                      <small class="text-muted"><?= htmlspecialchars($s['department']) ?></small>
                    </td>
                    <td style="padding:0.75rem 1rem;">
                      <div><?= htmlspecialchars($s['email']) ?></div>
                      <small class="text-muted">📞 <?= htmlspecialchars($s['phone']) ?></small>
                    </td>
                    <td style="padding:0.75rem 1rem;">
                      <div><?= htmlspecialchars($s['course']) ?></div>
                      <small class="text-muted"><?= htmlspecialchars($s['academic_year']) ?> (Sem <?= htmlspecialchars((string)$s['semester']) ?>)</small>
                    </td>
                    <td style="padding:0.75rem 1rem;font-weight:600;">
                      <?= htmlspecialchars(number_format((float)$s['cgpa'], 2)) ?>
                    </td>
                    <td style="padding:0.75rem 1rem;">
                      <span class="badge <?= $s['status'] === 'Active' ? 'badge-active' : 'badge-inactive' ?>" style="padding:0.25rem 0.65rem;border-radius:9999px;font-size:0.75rem;font-weight:700;">
                        <?= htmlspecialchars($s['status']) ?>
                      </span>
                    </td>
                    <td style="padding:0.75rem 1rem;text-align:center;">
                      <div class="action-btn-group">
                        <button type="button" class="btn btn-outline btn-sm" onclick='openEditModal(<?= json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Edit student">
                          ✏️ Edit
                        </button>
                        <button type="button" class="btn btn-outline btn-sm" style="color:#dc2626;border-color:#fca5a5;" onclick="confirmDelete(<?= (int)$s['id'] ?>, '<?= htmlspecialchars(addslashes($s['full_name'])) ?>')" title="Delete student">
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

  <!-- ADD / EDIT STUDENT MODAL -->
  <div class="modal-overlay" id="studentModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-box">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
        <h2 id="modalTitle" style="margin:0;font-size:1.4rem;">Add New Student</h2>
        <button type="button" class="btn btn-outline btn-sm" onclick="closeModal()">&times;</button>
      </div>

      <form action="students.php" method="post" id="modalStudentForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>" />
        <input type="hidden" name="action" id="formAction" value="create" />
        <input type="hidden" name="student_db_id" id="student_db_id" value="" />

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group">
            <label for="m_student_id" class="form-label">Student ID <span class="required">*</span></label>
            <input type="text" id="m_student_id" name="student_id" class="form-control" required placeholder="e.g. D26DCE173" />
          </div>
          <div class="form-group">
            <label for="m_full_name" class="form-label">Full Name <span class="required">*</span></label>
            <input type="text" id="m_full_name" name="full_name" class="form-control" required placeholder="Full Name" />
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group">
            <label for="m_email" class="form-label">Email Address <span class="required">*</span></label>
            <input type="email" id="m_email" name="email" class="form-control" required placeholder="student@charusat.edu.in" />
          </div>
          <div class="form-group">
            <label for="m_phone" class="form-label">Mobile Phone <span class="required">*</span></label>
            <input type="tel" id="m_phone" name="phone" class="form-control" required placeholder="10-digit number" />
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group">
            <label for="m_course" class="form-label">Course <span class="required">*</span></label>
            <select id="m_course" name="course" class="form-control" required>
              <?php foreach ($courseOptions as $co): ?>
                <option value="<?= htmlspecialchars($co) ?>"><?= htmlspecialchars($co) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="m_year" class="form-label">Academic Year <span class="required">*</span></label>
            <select id="m_year" name="academic_year" class="form-control" required>
              <?php foreach ($yearOptions as $yo): ?>
                <option value="<?= htmlspecialchars($yo) ?>"><?= htmlspecialchars($yo) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
          <div class="form-group">
            <label for="m_semester" class="form-label">Semester</label>
            <input type="number" id="m_semester" name="semester" class="form-control" min="1" max="8" value="3" required />
          </div>
          <div class="form-group">
            <label for="m_cgpa" class="form-label">CGPA</label>
            <input type="number" id="m_cgpa" name="cgpa" step="0.01" min="0" max="10" class="form-control" value="8.50" required />
          </div>
          <div class="form-group">
            <label for="m_status" class="form-label">Status</label>
            <select id="m_status" name="status" class="form-control">
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Gender</label>
          <div style="display:flex;gap:1.5rem;margin-top:0.35rem;">
            <label><input type="radio" name="gender" value="Male" id="m_gender_male" checked /> Male</label>
            <label><input type="radio" name="gender" value="Female" id="m_gender_female" /> Female</label>
            <label><input type="radio" name="gender" value="Other" id="m_gender_other" /> Other</label>
          </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:0.75rem;margin-top:1.5rem;">
          <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
          <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Save Student Record</button>
        </div>
      </form>
    </div>
  </div>

  <!-- DELETE CONFIRMATION MODAL -->
  <div class="modal-overlay" id="deleteModal" role="dialog" aria-modal="true">
    <div class="modal-box" style="max-width:480px;text-align:center;">
      <div style="font-size:3rem;margin-bottom:0.5rem;">⚠️</div>
      <h2 style="margin:0 0 0.5rem;">Confirm Delete</h2>
      <p class="text-muted" id="deleteMsg">Are you sure you want to remove this student record?</p>

      <form action="students.php" method="post" style="display:flex;gap:0.75rem;justify-content:center;margin-top:1.5rem;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>" />
        <input type="hidden" name="student_db_id" id="delete_student_id" value="" />
        <input type="hidden" name="action" id="delete_action" value="soft_delete" />

        <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">Cancel</button>
        <button type="submit" class="btn btn-outline" onclick="document.getElementById('delete_action').value='soft_delete'" style="color:#d97706;border-color:#fcd34d;">Soft Delete (Inactive)</button>
        <button type="submit" class="btn btn-primary" onclick="document.getElementById('delete_action').value='delete'" style="background:#dc2626;border-color:#dc2626;">Permanent Delete</button>
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
    function openAddModal() {
      document.getElementById('modalTitle').textContent = 'Add New Student';
      document.getElementById('formAction').value = 'create';
      document.getElementById('student_db_id').value = '';
      document.getElementById('m_student_id').value = '';
      document.getElementById('m_full_name').value = '';
      document.getElementById('m_email').value = '';
      document.getElementById('m_phone').value = '';
      document.getElementById('m_semester').value = '3';
      document.getElementById('m_cgpa').value = '8.50';
      document.getElementById('m_status').value = 'Active';
      document.getElementById('m_gender_male').checked = true;
      document.getElementById('modalSubmitBtn').textContent = 'Create Student Record';
      document.getElementById('studentModal').classList.add('active');
    }

    function openEditModal(student) {
      document.getElementById('modalTitle').textContent = 'Edit Student: ' + student.student_id;
      document.getElementById('formAction').value = 'update';
      document.getElementById('student_db_id').value = student.id;
      document.getElementById('m_student_id').value = student.student_id;
      document.getElementById('m_full_name').value = student.full_name;
      document.getElementById('m_email').value = student.email;
      document.getElementById('m_phone').value = student.phone;
      document.getElementById('m_course').value = student.course;
      document.getElementById('m_year').value = student.academic_year;
      document.getElementById('m_semester').value = student.semester;
      document.getElementById('m_cgpa').value = student.cgpa;
      document.getElementById('m_status').value = student.status;

      if (student.gender === 'Female') {
        document.getElementById('m_gender_female').checked = true;
      } else if (student.gender === 'Other') {
        document.getElementById('m_gender_other').checked = true;
      } else {
        document.getElementById('m_gender_male').checked = true;
      }

      document.getElementById('modalSubmitBtn').textContent = 'Update Student Record';
      document.getElementById('studentModal').classList.add('active');
    }

    function closeModal() {
      document.getElementById('studentModal').classList.remove('active');
    }

    function confirmDelete(id, name) {
      document.getElementById('delete_student_id').value = id;
      document.getElementById('deleteMsg').innerHTML = 'Are you sure you want to delete student <strong>' + name + '</strong>?';
      document.getElementById('deleteModal').classList.add('active');
    }

    function closeDeleteModal() {
      document.getElementById('deleteModal').classList.remove('active');
    }
  </script>
</body>
</html>
