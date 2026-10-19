<?php
/**
 * CHARUSAT - ITUE203: Web Development Frameworks
 * StudentHub Portal - AJAX & Fetch-Based Student CRUD (Practical 13)
 * Asynchronous Data Management without Full Page Reload
 */

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

// Accessible to authenticated users and guests (with role-based controls)
$currentUser = getCurrentUser();
$isUserAdmin = isAdmin();
$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="CHARUSAT StudentHub - Asynchronous AJAX and Fetch-API based CRUD without page reloads." />
  <title>Live AJAX Student CRUD &mdash; CHARUSAT StudentHub</title>
  <link rel="stylesheet" href="css/styles.css" />
  <style>
    /* Practical 13 Specialized Asynchronous UI Styling */
    .ajax-toolbar {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      align-items: center;
      justify-content: space-between;
      background: var(--bg-surface);
      border: 1px solid var(--border);
      padding: 1.25rem;
      border-radius: var(--radius-lg);
      margin-bottom: 1.5rem;
      box-shadow: var(--shadow-sm);
    }
    .ajax-search-box {
      position: relative;
      flex: 1 1 280px;
    }
    .ajax-search-box input {
      width: 100%;
      padding: 0.65rem 1rem 0.65rem 2.4rem;
      border-radius: var(--radius-md);
      border: 1px solid var(--border);
      background: var(--bg-main);
      color: var(--text-main);
      font-size: 0.95rem;
    }
    .ajax-search-box .search-icon {
      position: absolute;
      left: 0.85rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      pointer-events: none;
    }
    .ajax-filters {
      display: flex;
      gap: 0.75rem;
      flex-wrap: wrap;
      align-items: center;
    }
    .ajax-filters select {
      padding: 0.6rem 0.9rem;
      border-radius: var(--radius-md);
      border: 1px solid var(--border);
      background: var(--bg-main);
      color: var(--text-main);
      font-size: 0.9rem;
    }

    /* Table & Status Badges */
    .ajax-table-container {
      background: var(--bg-surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      overflow-x: auto;
      box-shadow: var(--shadow-sm);
      position: relative;
      min-height: 250px;
    }
    .ajax-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.9rem;
      text-align: left;
    }
    .ajax-table th {
      background: var(--bg-alt);
      color: var(--text-muted);
      font-weight: 700;
      padding: 0.85rem 1rem;
      border-bottom: 1px solid var(--border);
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.05em;
    }
    .ajax-table td {
      padding: 0.9rem 1rem;
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
    }
    .ajax-table tr:hover {
      background: rgba(79, 70, 229, 0.03);
    }
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.25rem 0.65rem;
      border-radius: var(--radius-full);
      font-size: 0.75rem;
      font-weight: 700;
    }
    .status-badge.active { background: #dcfce7; color: #15803d; }
    .status-badge.inactive { background: #fee2e2; color: #b91c1c; }

    /* Floating Toast Alerts */
    .toast-container {
      position: fixed;
      top: 1.5rem;
      right: 1.5rem;
      z-index: 99999;
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      max-width: 380px;
      pointer-events: none;
    }
    .toast {
      pointer-events: auto;
      background: var(--bg-surface);
      color: var(--text-main);
      border-left: 5px solid var(--primary);
      box-shadow: 0 10px 25px rgba(0,0,0,0.15);
      border-radius: var(--radius-md);
      padding: 1rem 1.25rem;
      font-size: 0.9rem;
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 0.75rem;
      animation: slideInRight 0.3s ease-out;
      border: 1px solid var(--border);
    }
    .toast.success { border-left-color: var(--success); }
    .toast.error { border-left-color: var(--danger); }
    @keyframes slideInRight {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }

    /* Modal Styling */
    .ajax-modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(4px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 10000;
      padding: 1rem;
    }
    .ajax-modal-backdrop.open {
      display: flex;
    }
    .ajax-modal {
      background: var(--bg-surface);
      width: 100%;
      max-width: 580px;
      max-height: 90vh;
      overflow-y: auto;
      border-radius: var(--radius-lg);
      border: 1px solid var(--border);
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
      animation: modalScale 0.2s ease-out;
    }
    @keyframes modalScale {
      from { transform: scale(0.95); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }
    .ajax-modal-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid var(--border);
    }
    .ajax-modal-body {
      padding: 1.5rem;
    }
    .ajax-modal-footer {
      padding: 1rem 1.5rem;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: flex-end;
      gap: 0.75rem;
      background: var(--bg-alt);
    }

    /* Loading Overlay */
    .loading-overlay {
      position: absolute;
      inset: 0;
      background: rgba(255, 255, 255, 0.75);
      backdrop-filter: blur(2px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 10;
      border-radius: var(--radius-lg);
    }
    [data-theme="dark"] .loading-overlay {
      background: rgba(15, 23, 42, 0.75);
    }
    .loading-overlay.active {
      display: flex;
    }
    .spinner {
      width: 40px;
      height: 40px;
      border: 4px solid var(--border);
      border-top-color: var(--primary);
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* Network Inspector Panel */
    .inspector-panel {
      margin-top: 2rem;
      background: #0f172a;
      color: #e2e8f0;
      border-radius: var(--radius-lg);
      border: 1px solid #1e293b;
      overflow: hidden;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      font-size: 0.825rem;
    }
    .inspector-header {
      background: #1e293b;
      padding: 0.75rem 1.25rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #334155;
    }
    .inspector-content {
      padding: 1.25rem;
      overflow-x: auto;
      max-height: 250px;
    }
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
            <li><a href="events.php" class="nav-link">Events</a></li>
            <li><a href="students.php" class="nav-link">Students CRUD</a></li>
            <li><a href="ajax-students.php" class="nav-link active" aria-current="page">AJAX API CRUD</a></li>
            <li><a href="explorer.html" class="nav-link">Data Explorer</a></li>
            <?php if ($isUserAdmin): ?>
              <li><a href="admin.php" class="nav-link">Admin Portal</a></li>
            <?php elseif ($currentUser): ?>
              <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
            <?php endif; ?>
          </ul>
        </nav>
        <div class="nav-actions">
          <button id="theme-toggle-btn" class="btn btn-outline btn-sm" aria-label="Toggle dark mode">🌙 Dark</button>
          <?php if ($currentUser): ?>
            <span style="font-size:0.85rem;font-weight:600;padding:0.35rem 0.65rem;background:var(--primary-light);color:var(--primary);border-radius:6px;">
              👤 <?= htmlspecialchars($currentUser['username']) ?>
            </span>
            <a href="logout.php" class="btn btn-outline btn-sm" style="color:#dc2626;border-color:#fca5a5;">Logout</a>
          <?php else: ?>
            <a href="login.php" class="btn btn-outline btn-sm">Sign In</a>
            <a href="register.php" class="btn btn-primary btn-sm">Register</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <!-- Breadcrumbs -->
  <nav class="breadcrumb-wrapper" aria-label="Breadcrumb">
    <div class="container">
      <ol class="breadcrumb-list" role="list">
        <li class="breadcrumb-item"><a href="index.html">Home</a></li>
        <li class="breadcrumb-separator" aria-hidden="true">›</li>
        <li class="breadcrumb-item"><a href="students.php">Students</a></li>
        <li class="breadcrumb-separator" aria-hidden="true">›</li>
        <li class="breadcrumb-item" aria-current="page">AJAX Fetch API CRUD</li>
      </ol>
    </div>
  </nav>

  <!-- Floating Toast Notifications -->
  <div class="toast-container" id="toast-container" aria-live="polite"></div>

  <!-- Main Content -->
  <main id="main-content" class="main-content">
    <div class="container">

      <!-- Hero Header -->
      <section style="margin-bottom:2rem;background:var(--bg-surface);padding:1.75rem 2rem;border-radius:var(--radius-lg);border:1px solid var(--border);">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
          <div>
            <span class="badge" style="background:#e0e7ff;color:#4338ca;padding:0.25rem 0.75rem;border-radius:9999px;font-weight:700;font-size:0.8rem;">
              Practical 13: Asynchronous REST API
            </span>
            <h1 style="margin:0.5rem 0 0.35rem;font-size:1.85rem;">AJAX &amp; Fetch-Based Student CRUD</h1>
            <p class="text-muted" style="margin:0;">Manage campus student directory asynchronously using modern JavaScript Fetch API and PHP JSON backend without page reload.</p>
          </div>
          <div style="display:flex;gap:0.75rem;">
            <button type="button" class="btn btn-primary" onclick="openAddModal()">
              ➕ Add New Student
            </button>
            <button type="button" class="btn btn-outline" onclick="fetchStudents()">
              🔄 Reload Data
            </button>
          </div>
        </div>
      </section>

      <!-- Live Search & Filtering Toolbar -->
      <section class="ajax-toolbar" aria-label="Data controls">
        <div class="ajax-search-box">
          <span class="search-icon" aria-hidden="true">🔍</span>
          <input type="text" id="api-search-input" placeholder="Live search by name, roll no, email, or department (Debounced)..." aria-label="Live search" />
        </div>

        <div class="ajax-filters">
          <label for="filter-course" class="sr-only">Filter by Course</label>
          <select id="filter-course" aria-label="Filter by course">
            <option value="All">All Courses</option>
            <option value="B.Tech Computer Engineering">B.Tech Computer Engineering</option>
            <option value="B.Tech IT Engineering">B.Tech IT Engineering</option>
            <option value="B.Tech Electrical">B.Tech Electrical</option>
            <option value="B.Tech Mechanical">B.Tech Mechanical</option>
            <option value="B.Tech Civil">B.Tech Civil</option>
          </select>

          <label for="filter-status" class="sr-only">Filter by Status</label>
          <select id="filter-status" aria-label="Filter by status">
            <option value="All">All Statuses</option>
            <option value="Active">Active Only</option>
            <option value="Inactive">Inactive (Soft-Deleted)</option>
          </select>

          <span id="record-count-badge" style="font-size:0.85rem;color:var(--text-muted);font-weight:600;padding:0.4rem 0.75rem;background:var(--bg-alt);border-radius:var(--radius-md);">
            Loading records...
          </span>
        </div>
      </section>

      <!-- Asynchronous Table Container -->
      <section class="ajax-table-container">
        <!-- Spinner Overlay -->
        <div class="loading-overlay" id="loading-overlay">
          <div style="text-align:center;">
            <div class="spinner"></div>
            <p style="margin-top:0.75rem;font-weight:600;color:var(--text-muted);font-size:0.875rem;">Communicating with PHP JSON API...</p>
          </div>
        </div>

        <table class="ajax-table" id="students-table">
          <thead>
            <tr>
              <th>Roll No</th>
              <th>Full Name</th>
              <th>Course &amp; Semester</th>
              <th>Contact</th>
              <th>CGPA</th>
              <th>Status</th>
              <th style="text-align:right;">Actions</th>
            </tr>
          </thead>
          <tbody id="students-tbody">
            <!-- Dynamically populated via Fetch API -->
          </tbody>
        </table>
      </section>

      <!-- Pagination Toolbar -->
      <div id="pagination-toolbar" style="display:flex;justify-content:space-between;align-items:center;margin-top:1.25rem;flex-wrap:wrap;gap:1rem;">
        <div style="font-size:0.875rem;color:var(--text-muted);" id="pagination-info">
          Page 1 of 1
        </div>
        <div style="display:flex;gap:0.5rem;" id="pagination-buttons">
          <button class="btn btn-outline btn-sm" id="btn-prev" onclick="changePage(-1)" disabled>&larr; Previous</button>
          <button class="btn btn-outline btn-sm" id="btn-next" onclick="changePage(1)" disabled>Next &rarr;</button>
        </div>
      </div>

      <!-- Real-Time Network Tab / API Inspector (Practical 13 Requirement & Evaluation Proof) -->
      <section class="inspector-panel" aria-label="API Network Inspector">
        <div class="inspector-header">
          <div style="display:flex;align-items:center;gap:0.75rem;">
            <span style="color:#38bdf8;font-weight:700;">📡 Live REST API Inspector</span>
            <span style="background:#0284c7;color:#fff;padding:0.15rem 0.5rem;border-radius:4px;font-size:0.7rem;" id="net-method">GET</span>
            <span style="color:#94a3b8;" id="net-status-code">HTTP 200 OK</span>
          </div>
          <div style="font-size:0.75rem;color:#94a3b8;">
            Latency: <strong style="color:#34d399;" id="net-latency">0 ms</strong>
          </div>
        </div>
        <div class="inspector-content">
          <div style="margin-bottom:0.5rem;color:#cbd5e1;">
            Endpoint: <span id="net-url" style="color:#f59e0b;">/WD/api-students.php</span>
          </div>
          <pre id="net-response" style="margin:0;color:#a7f3d0;font-size:0.78rem;overflow-x:auto;">{ "status": "Waiting for API calls..." }</pre>
        </div>
      </section>

    </div>
  </main>

  <!-- ══════════════════════════════════════════════════════════════════════
       MODALS: ADD / EDIT / DELETE CONFIRMATION
       ══════════════════════════════════════════════════════════════════════ -->

  <!-- Add / Edit Student Modal -->
  <div class="ajax-modal-backdrop" id="student-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="ajax-modal">
      <div class="ajax-modal-header">
        <h3 id="modal-title" style="margin:0;font-size:1.25rem;">Add New Student</h3>
        <button type="button" class="btn btn-outline btn-sm" onclick="closeStudentModal()" aria-label="Close modal">&times;</button>
      </div>
      <form id="student-form" onsubmit="handleFormSubmit(event)">
        <div class="ajax-modal-body">
          <input type="hidden" id="student-form-id" value="" />

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
            <div class="form-group">
              <label for="form-student-id">Student ID (Roll No) <span style="color:var(--danger)">*</span></label>
              <input type="text" id="form-student-id" required placeholder="e.g. D26DCE173" style="width:100%;padding:0.6rem;border-radius:6px;border:1px solid var(--border);" />
            </div>

            <div class="form-group">
              <label for="form-full-name">Full Name <span style="color:var(--danger)">*</span></label>
              <input type="text" id="form-full-name" required placeholder="Full Name" style="width:100%;padding:0.6rem;border-radius:6px;border:1px solid var(--border);" />
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
            <div class="form-group">
              <label for="form-email">Email Address <span style="color:var(--danger)">*</span></label>
              <input type="email" id="form-email" required placeholder="name@charusat.edu.in" style="width:100%;padding:0.6rem;border-radius:6px;border:1px solid var(--border);" />
            </div>

            <div class="form-group">
              <label for="form-phone">Mobile Phone <span style="color:var(--danger)">*</span></label>
              <input type="tel" id="form-phone" required placeholder="10-digit mobile" style="width:100%;padding:0.6rem;border-radius:6px;border:1px solid var(--border);" />
            </div>
          </div>

          <div style="display:grid;grid-template-columns:2fr 1fr;gap:1rem;margin-bottom:1rem;">
            <div class="form-group">
              <label for="form-course">Academic Course <span style="color:var(--danger)">*</span></label>
              <select id="form-course" required style="width:100%;padding:0.6rem;border-radius:6px;border:1px solid var(--border);">
                <option value="B.Tech Computer Engineering">B.Tech Computer Engineering</option>
                <option value="B.Tech IT Engineering">B.Tech IT Engineering</option>
                <option value="B.Tech Electrical">B.Tech Electrical</option>
                <option value="B.Tech Mechanical">B.Tech Mechanical</option>
                <option value="B.Tech Civil">B.Tech Civil</option>
              </select>
            </div>

            <div class="form-group">
              <label for="form-semester">Semester</label>
              <select id="form-semester" style="width:100%;padding:0.6rem;border-radius:6px;border:1px solid var(--border);">
                <option value="1">Sem 1</option>
                <option value="2">Sem 2</option>
                <option value="3" selected>Sem 3</option>
                <option value="4">Sem 4</option>
                <option value="5">Sem 5</option>
                <option value="6">Sem 6</option>
                <option value="7">Sem 7</option>
                <option value="8">Sem 8</option>
              </select>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
            <div class="form-group">
              <label for="form-gender">Gender</label>
              <select id="form-gender" style="width:100%;padding:0.6rem;border-radius:6px;border:1px solid var(--border);">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div class="form-group">
              <label for="form-cgpa">CGPA</label>
              <input type="number" step="0.01" min="0" max="10" id="form-cgpa" value="8.50" style="width:100%;padding:0.6rem;border-radius:6px;border:1px solid var(--border);" />
            </div>

            <div class="form-group">
              <label for="form-status">Status</label>
              <select id="form-status" style="width:100%;padding:0.6rem;border-radius:6px;border:1px solid var(--border);">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>
          </div>

        </div>
        <div class="ajax-modal-footer">
          <button type="button" class="btn btn-outline btn-sm" onclick="closeStudentModal()">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm" id="form-submit-btn">Save Student &rarr;</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="ajax-modal-backdrop" id="delete-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
    <div class="ajax-modal" style="max-width:440px;">
      <div class="ajax-modal-header">
        <h3 id="delete-modal-title" style="margin:0;color:var(--danger);font-size:1.25rem;">⚠️ Confirm Delete</h3>
        <button type="button" class="btn btn-outline btn-sm" onclick="closeDeleteModal()">&times;</button>
      </div>
      <div class="ajax-modal-body">
        <p>Are you sure you want to delete student <strong id="delete-student-name"></strong> (<code id="delete-student-roll"></code>)?</p>
        <div style="background:var(--bg-alt);padding:0.75rem;border-radius:6px;margin-top:1rem;font-size:0.85rem;">
          <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
            <input type="checkbox" id="delete-hard-checkbox" />
            <span>Permanent Hard Delete (Remove permanently from database)</span>
          </label>
          <small class="text-muted" style="display:block;margin-top:0.35rem;">Leave unchecked for safe soft-delete (status marked Inactive).</small>
        </div>
      </div>
      <div class="ajax-modal-footer">
        <button type="button" class="btn btn-outline btn-sm" onclick="closeDeleteModal()">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" style="background:#dc2626;border-color:#dc2626;" onclick="executeDelete()">Confirm Delete</button>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="site-footer" role="contentinfo" style="margin-top:4rem;">
    <div class="container">
      <div class="footer-bottom">
        <p>&copy; 2026 StudentHub &mdash; CHARUSAT ITUE203 Web Development Frameworks</p>
        <p>Faculty of Technology &amp; Engineering (FTE)</p>
      </div>
    </div>
  </footer>

  <!-- Core JavaScript & Practical 13 AJAX Logic -->
  <script src="js/main.js"></script>
  <script>
    // State Management
    let state = {
      search: '',
      course: 'All',
      status: 'All',
      page: 1,
      limit: 10,
      totalPages: 1,
      totalRecords: 0,
      activeDeleteId: null
    };

    const API_URL = 'api-students.php';

    // Elements
    const searchInput = document.getElementById('api-search-input');
    const courseFilter = document.getElementById('filter-course');
    const statusFilter = document.getElementById('filter-status');
    const tbody = document.getElementById('students-tbody');
    const loader = document.getElementById('loading-overlay');
    const recordCountBadge = document.getElementById('record-count-badge');
    const paginationInfo = document.getElementById('pagination-info');
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');

    // Network Inspector Elements
    const netMethod = document.getElementById('net-method');
    const netStatusCode = document.getElementById('net-status-code');
    const netLatency = document.getElementById('net-latency');
    const netUrl = document.getElementById('net-url');
    const netResponse = document.getElementById('net-response');

    // Debounce Utility for Live Search (Practical 13 Intermediate Extension)
    function debounce(func, wait = 300) {
      let timeout;
      return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
      };
    }

    // Toast Notification System
    function showToast(message, type = 'success') {
      const container = document.getElementById('toast-container');
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      const icon = type === 'success' ? '✅' : '❌';
      toast.innerHTML = `
        <div><strong>${icon} ${type === 'success' ? 'Success' : 'Error'}</strong><div style="margin-top:0.25rem;">${escapeHtml(message)}</div></div>
        <button type="button" style="background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:1.1rem;" onclick="this.parentElement.remove()">&times;</button>
      `;
      container.appendChild(toast);
      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.4s';
        setTimeout(() => toast.remove(), 400);
      }, 4000);
    }

    function escapeHtml(str) {
      if (str === null || str === undefined) return '';
      return String(str).replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[m]);
    }

    // Update Network Inspector Display
    function updateInspector(method, url, status, duration, json) {
      netMethod.textContent = method;
      netStatusCode.textContent = `HTTP ${status}`;
      netStatusCode.style.color = (status >= 200 && status < 300) ? '#34d399' : '#f87171';
      netLatency.textContent = `${duration} ms`;
      netUrl.textContent = url;
      netResponse.textContent = JSON.stringify(json, null, 2);
    }

    // ────────────────────────────────────────────────────────────────────────
    // FETCH STUDENTS (GET REQUEST)
    // ────────────────────────────────────────────────────────────────────────
    async function fetchStudents() {
      loader.classList.add('active');
      const startTime = performance.now();

      const params = new URLSearchParams({
        page: state.page,
        limit: state.limit,
        search: state.search,
        course: state.course,
        status: state.status
      });

      const requestUrl = `${API_URL}?${params.toString()}`;

      try {
        const response = await fetch(requestUrl);
        const duration = Math.round(performance.now() - startTime);
        const json = await response.json();

        updateInspector('GET', requestUrl, response.status, duration, json);

        if (json.success) {
          renderTable(json.data);
          if (json.meta && json.meta.pagination) {
            state.totalPages = json.meta.pagination.totalPages;
            state.totalRecords = json.meta.pagination.totalRecords;
            updatePagination();
          }
        } else {
          showToast(json.message || 'Failed to fetch students', 'error');
        }
      } catch (err) {
        showToast('Network error: Unable to reach PHP API backend.', 'error');
        console.error(err);
      } finally {
        loader.classList.remove('active');
      }
    }

    function renderTable(students) {
      tbody.innerHTML = '';

      if (!students || students.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="7" style="text-align:center;padding:3rem 1rem;color:var(--text-muted);">
              <div style="font-size:2.5rem;margin-bottom:0.5rem;">📂</div>
              <strong>No matching students found</strong>
              <p style="margin-top:0.25rem;">Try adjusting your search query or filters.</p>
            </td>
          </tr>
        `;
        recordCountBadge.textContent = '0 Records Found';
        return;
      }

      recordCountBadge.textContent = `Showing ${students.length} of ${state.totalRecords} Records`;

      students.forEach(s => {
        const tr = document.createElement('tr');
        const isInactive = s.status === 'Inactive';
        tr.style.opacity = isInactive ? '0.65' : '1';

        tr.innerHTML = `
          <td><code style="font-weight:700;font-size:0.875rem;">${escapeHtml(s.student_id)}</code></td>
          <td>
            <strong>${escapeHtml(s.full_name)}</strong>
            <div style="font-size:0.75rem;color:var(--text-muted);">${escapeHtml(s.department)}</div>
          </td>
          <td>
            <div>${escapeHtml(s.course)}</div>
            <small class="text-muted">Sem ${escapeHtml(s.semester)} &bull; ${escapeHtml(s.academic_year)}</small>
          </td>
          <td>
            <div>${escapeHtml(s.email)}</div>
            <small class="text-muted">${escapeHtml(s.phone)}</small>
          </td>
          <td><strong style="color:var(--primary);">${parseFloat(s.cgpa).toFixed(2)}</strong></td>
          <td>
            <span class="status-badge ${s.status === 'Active' ? 'active' : 'inactive'}">
              ${s.status === 'Active' ? '● Active' : '○ Inactive'}
            </span>
          </td>
          <td style="text-align:right;white-space:nowrap;">
            <button class="btn btn-outline btn-sm" onclick="openEditModal(${s.id})" title="Edit Student">✏️ Edit</button>
            <button class="btn btn-outline btn-sm" style="color:var(--danger);border-color:#fca5a5;" onclick="openDeleteModal(${s.id}, '${escapeHtml(s.full_name)}', '${escapeHtml(s.student_id)}')" title="Delete Student">🗑️ Delete</button>
          </td>
        `;
        tbody.appendChild(tr);
      });
    }

    function updatePagination() {
      paginationInfo.textContent = `Page ${state.page} of ${Math.max(1, state.totalPages)} (${state.totalRecords} total entries)`;
      btnPrev.disabled = (state.page <= 1);
      btnNext.disabled = (state.page >= state.totalPages);
    }

    function changePage(direction) {
      const newPage = state.page + direction;
      if (newPage >= 1 && newPage <= state.totalPages) {
        state.page = newPage;
        fetchStudents();
      }
    }

    // ────────────────────────────────────────────────────────────────────────
    // EVENT LISTENERS FOR DEBOUNCED SEARCH & FILTERS
    // ────────────────────────────────────────────────────────────────────────
    searchInput.addEventListener('input', debounce((e) => {
      state.search = e.target.value.trim();
      state.page = 1;
      fetchStudents();
    }, 300));

    courseFilter.addEventListener('change', (e) => {
      state.course = e.target.value;
      state.page = 1;
      fetchStudents();
    });

    statusFilter.addEventListener('change', (e) => {
      state.status = e.target.value;
      state.page = 1;
      fetchStudents();
    });

    // ────────────────────────────────────────────────────────────────────────
    // MODAL & FORM HANDLING (POST / PUT)
    // ────────────────────────────────────────────────────────────────────────
    const modalBackdrop = document.getElementById('student-modal-backdrop');
    const modalTitle = document.getElementById('modal-title');
    const formSubmitBtn = document.getElementById('form-submit-btn');

    function openAddModal() {
      document.getElementById('student-form-id').value = '';
      document.getElementById('student-form').reset();
      document.getElementById('form-status').value = 'Active';
      document.getElementById('form-cgpa').value = '8.50';
      modalTitle.textContent = 'Add New Student';
      formSubmitBtn.textContent = 'Save Student →';
      modalBackdrop.classList.add('open');
    }

    async function openEditModal(id) {
      loader.classList.add('active');
      const startTime = performance.now();

      try {
        const response = await fetch(`${API_URL}?id=${id}`);
        const duration = Math.round(performance.now() - startTime);
        const json = await response.json();

        updateInspector('GET', `${API_URL}?id=${id}`, response.status, duration, json);

        if (json.success && json.data) {
          const s = json.data;
          document.getElementById('student-form-id').value = s.id;
          document.getElementById('form-student-id').value = s.student_id;
          document.getElementById('form-full-name').value = s.full_name;
          document.getElementById('form-email').value = s.email;
          document.getElementById('form-phone').value = s.phone;
          document.getElementById('form-course').value = s.course;
          document.getElementById('form-semester').value = s.semester;
          document.getElementById('form-gender').value = s.gender;
          document.getElementById('form-cgpa').value = s.cgpa;
          document.getElementById('form-status').value = s.status;

          modalTitle.textContent = `Edit Student #${s.id} (${s.student_id})`;
          formSubmitBtn.textContent = 'Update Record →';
          modalBackdrop.classList.add('open');
        } else {
          showToast(json.message || 'Student not found', 'error');
        }
      } catch (err) {
        showToast('Error loading student details for editing', 'error');
      } finally {
        loader.classList.remove('active');
      }
    }

    function closeStudentModal() {
      modalBackdrop.classList.remove('open');
    }

    async function handleFormSubmit(e) {
      e.preventDefault();
      const id = document.getElementById('student-form-id').value;
      const isEdit = Boolean(id);

      const payload = {
        id: id ? parseInt(id, 10) : undefined,
        student_id: document.getElementById('form-student-id').value.trim(),
        full_name: document.getElementById('form-full-name').value.trim(),
        email: document.getElementById('form-email').value.trim(),
        phone: document.getElementById('form-phone').value.trim(),
        course: document.getElementById('form-course').value,
        semester: parseInt(document.getElementById('form-semester').value, 10),
        gender: document.getElementById('form-gender').value,
        cgpa: parseFloat(document.getElementById('form-cgpa').value),
        status: document.getElementById('form-status').value
      };

      const method = isEdit ? 'PUT' : 'POST';
      const startTime = performance.now();
      loader.classList.add('active');

      try {
        const response = await fetch(API_URL, {
          method: method,
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(payload)
        });

        const duration = Math.round(performance.now() - startTime);
        const json = await response.json();

        updateInspector(method, API_URL, response.status, duration, json);

        if (json.success) {
          showToast(json.message, 'success');
          closeStudentModal();
          fetchStudents();
        } else {
          const errMsg = json.errors ? Object.values(json.errors).join(' ') : json.message;
          showToast(errMsg, 'error');
        }
      } catch (err) {
        showToast('Network error during form submission', 'error');
      } finally {
        loader.classList.remove('active');
      }
    }

    // ────────────────────────────────────────────────────────────────────────
    // DELETE MODAL & DELETION LOGIC (DELETE REQUEST)
    // ────────────────────────────────────────────────────────────────────────
    const deleteBackdrop = document.getElementById('delete-modal-backdrop');

    function openDeleteModal(id, name, roll) {
      state.activeDeleteId = id;
      document.getElementById('delete-student-name').textContent = name;
      document.getElementById('delete-student-roll').textContent = roll;
      document.getElementById('delete-hard-checkbox').checked = false;
      deleteBackdrop.classList.add('open');
    }

    function closeDeleteModal() {
      deleteBackdrop.classList.remove('open');
      state.activeDeleteId = null;
    }

    async function executeDelete() {
      if (!state.activeDeleteId) return;

      const isHard = document.getElementById('delete-hard-checkbox').checked;
      const mode = isHard ? 'hard' : 'soft';
      const id = state.activeDeleteId;

      const startTime = performance.now();
      loader.classList.add('active');
      closeDeleteModal();

      try {
        const response = await fetch(API_URL, {
          method: 'DELETE',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ id: id, mode: mode })
        });

        const duration = Math.round(performance.now() - startTime);
        const json = await response.json();

        updateInspector('DELETE', API_URL, response.status, duration, json);

        if (json.success) {
          showToast(json.message, 'success');
          fetchStudents();
        } else {
          showToast(json.message || 'Deletion failed', 'error');
        }
      } catch (err) {
        showToast('Network error during deletion', 'error');
      } finally {
        loader.classList.remove('active');
      }
    }

    // Initialize table on load
    document.addEventListener('DOMContentLoaded', () => {
      fetchStudents();
    });
  </script>
</body>
</html>
