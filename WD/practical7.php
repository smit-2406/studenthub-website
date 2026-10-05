<?php
session_start();

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csvSafeValue(string $value): string
{
    return preg_match('/^[=+@-]/', $value) === 1 ? "'" . $value : $value;
}

if (!isset($_SESSION['practical7_csrf'])) {
    $_SESSION['practical7_csrf'] = bin2hex(random_bytes(32));
}

$storageDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'private';
$csvFile = $storageDirectory . DIRECTORY_SEPARATOR . 'practical7_submissions.csv';
$jsonFile = $storageDirectory . DIRECTORY_SEPARATOR . 'practical7_submissions.json';

$errors = [];
$flashMessage = $_SESSION['practical7_flash'] ?? '';
unset($_SESSION['practical7_flash']);

$formType = $_POST['form_type'] ?? 'contact';
$storageFormat = $_POST['storage_format'] ?? 'csv';

$subjects = ['Technical Issue', 'Account Help', 'Course Inquiry', 'Feedback', 'Other'];
$courses = ['B.Tech Computer Engineering', 'B.Tech IT Engineering', 'B.Tech Electrical', 'B.Tech Mechanical', 'B.Tech Civil'];
$years = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
$genders = ['Male', 'Female', 'Other'];

// Form values initialization
$contactValues = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
$regValues = ['name' => '', 'email' => '', 'mobile' => '', 'course' => '', 'year' => '', 'gender' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['practical7_csrf'], $submittedToken)) {
        $errors[] = 'Your security session expired. Please refresh the page and try again.';
    }

    $formType = $_POST['form_type'] ?? 'contact';
    $storageFormat = $_POST['storage_format'] ?? 'csv';
    if (!in_array($storageFormat, ['csv', 'json', 'both'], true)) {
        $storageFormat = 'csv';
    }

    $entryData = [];

    if ($formType === 'contact') {
        foreach ($contactValues as $field => $unused) {
            $contactValues[$field] = trim((string)($_POST[$field] ?? ''));
        }

        if (!preg_match('/^[\p{L}\p{M} .\'-]{2,80}$/u', $contactValues['name'])) {
            $errors[] = 'Name must be 2 to 80 characters long using valid letters and spaces.';
        }

        if (!filter_var($contactValues['email'], FILTER_VALIDATE_EMAIL) || strlen($contactValues['email']) > 254) {
            $errors[] = 'Enter a valid email address (e.g. name@university.edu).';
        }

        if (!in_array($contactValues['subject'], $subjects, true)) {
            $errors[] = 'Please select a valid subject from the dropdown.';
        }

        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $contactValues['message']) === 1
            || preg_match('/^[\s\S]{10,2000}$/u', $contactValues['message']) !== 1) {
            $errors[] = 'The message must contain between 10 and 2,000 characters.';
        }

        $entryData = [
            'timestamp' => date('c'),
            'type' => 'Contact Inquiry',
            'name' => $contactValues['name'],
            'email' => $contactValues['email'],
            'info_1' => 'Subject: ' . $contactValues['subject'],
            'info_2' => $contactValues['message'],
            'format' => strtoupper($storageFormat)
        ];

    } elseif ($formType === 'registration') {
        foreach ($regValues as $field => $unused) {
            $regValues[$field] = trim((string)($_POST[$field] ?? ''));
        }

        if (!preg_match('/^[\p{L}\p{M} .\'-]{3,80}$/u', $regValues['name'])) {
            $errors[] = 'Full name must contain at least 3 letters and spaces.';
        }

        if (!filter_var($regValues['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid university email address.';
        }

        if (!preg_match('/^[0-9]{10}$/', $regValues['mobile'])) {
            $errors[] = 'Mobile number must be exactly 10 digits.';
        }

        if (!in_array($regValues['course'], $courses, true)) {
            $errors[] = 'Please select a valid course.';
        }

        if (!in_array($regValues['year'], $years, true)) {
            $errors[] = 'Please select a valid year of study.';
        }

        if (!in_array($regValues['gender'], $genders, true)) {
            $errors[] = 'Please select your gender.';
        }

        if (empty($_POST['terms'])) {
            $errors[] = 'You must accept the terms & conditions.';
        }

        $entryData = [
            'timestamp' => date('c'),
            'type' => 'Student Registration',
            'name' => $regValues['name'],
            'email' => $regValues['email'],
            'info_1' => $regValues['course'] . ' (' . $regValues['year'] . ')',
            'info_2' => 'Mobile: ' . $regValues['mobile'] . ' | Gender: ' . $regValues['gender'],
            'format' => strtoupper($storageFormat)
        ];
    }

    if ($errors === []) {
        if (!is_dir($storageDirectory) && !mkdir($storageDirectory, 0750, true) && !is_dir($storageDirectory)) {
            $errors[] = 'Storage directory could not be created. Check server write permissions.';
        } else {
            $writeSuccess = true;

            // 1. Save to CSV if requested
            if ($storageFormat === 'csv' || $storageFormat === 'both') {
                $file = @fopen($csvFile, 'c+');
                if ($file === false || !flock($file, LOCK_EX)) {
                    $writeSuccess = false;
                    $errors[] = 'CSV file write lock failed. Please try again.';
                    if ($file) fclose($file);
                } else {
                    fseek($file, 0, SEEK_END);
                    $isEmpty = ftell($file) === 0;
                    if ($isEmpty) {
                        fputcsv($file, ['Timestamp', 'Type', 'Name', 'Email', 'Details', 'Payload/Message', 'Storage Format'], ',', '"', '');
                    }
                    $row = [
                        $entryData['timestamp'],
                        csvSafeValue($entryData['type']),
                        csvSafeValue($entryData['name']),
                        csvSafeValue($entryData['email']),
                        csvSafeValue($entryData['info_1']),
                        csvSafeValue($entryData['info_2']),
                        $entryData['format']
                    ];
                    fputcsv($file, $row, ',', '"', '');
                    fflush($file);
                    flock($file, LOCK_UN);
                    fclose($file);
                }
            }

            // 2. Save to JSON if requested
            if (($storageFormat === 'json' || $storageFormat === 'both') && $writeSuccess) {
                $jsonLockFile = $storageDirectory . DIRECTORY_SEPARATOR . 'json.lock';
                $lockFp = @fopen($jsonLockFile, 'c+');
                if ($lockFp && flock($lockFp, LOCK_EX)) {
                    $currentData = [];
                    if (file_exists($jsonFile) && is_readable($jsonFile)) {
                        $rawJson = file_get_contents($jsonFile);
                        $decoded = json_decode($rawJson, true);
                        if (is_array($decoded)) {
                            $currentData = $decoded;
                        }
                    }
                    $currentData[] = $entryData;
                    file_put_contents($jsonFile, json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
                    flock($lockFp, LOCK_UN);
                    fclose($lockFp);
                } else {
                    $writeSuccess = false;
                    $errors[] = 'JSON file lock failed. Please try again.';
                    if ($lockFp) fclose($lockFp);
                }
            }

            if ($writeSuccess) {
                $_SESSION['practical7_flash'] = 'Record saved successfully via server-side PHP in ' . strtoupper($storageFormat) . ' format!';
                $_SESSION['practical7_csrf'] = bin2hex(random_bytes(32));
                header('Location: practical7.php#records', true, 303);
                exit;
            }
        }
    }
}

// Read Stored CSV Records
$csvRecords = [];
if (is_readable($csvFile) && ($file = fopen($csvFile, 'r')) !== false) {
    fgetcsv($file, 0, ',', '"', ''); // Skip header
    while (($row = fgetcsv($file, 0, ',', '"', '')) !== false) {
        if (count($row) >= 5) {
            $csvRecords[] = $row;
        }
    }
    fclose($file);
    $csvRecords = array_slice(array_reverse($csvRecords), 0, 50);
}

// Read Stored JSON Records
$jsonRecords = [];
if (is_readable($jsonFile)) {
    $raw = file_get_contents($jsonFile);
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $jsonRecords = array_slice(array_reverse($decoded), 0, 50);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Practical 7: Server-side PHP form validation with CSV and JSON file storage." />
  <title>Practical 7: PHP Form Processing &amp; Storage | StudentHub</title>
  <link rel="stylesheet" href="css/styles.css" />
  <link rel="stylesheet" href="css/practical7.css" />
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

  <header class="site-header" role="banner">
    <div class="container">
      <div class="header-inner">
        <a href="index.html" class="brand-logo"><span class="brand-icon" aria-hidden="true">SH</span>StudentHub</a>
        <button class="mobile-menu-btn" aria-expanded="false" aria-controls="main-nav" aria-label="Toggle navigation">☰</button>
        <nav class="main-nav" id="main-nav" role="navigation" aria-label="Primary navigation">
          <ul>
            <li><a href="index.html" class="nav-link">Home</a></li>
            <li><a href="about.html" class="nav-link">About</a></li>
            <li><a href="courses.html" class="nav-link">Courses</a></li>
            <li><a href="events.html" class="nav-link">Events</a></li>
            <li><a href="faq.html" class="nav-link">FAQ</a></li>
            <li><a href="contact.html" class="nav-link">Contact</a></li>
            <li><a href="practical6.html" class="nav-link">Data Explorer</a></li>
            <li><a href="practical7.php" class="nav-link" aria-current="page">Practical 7</a></li>
          </ul>
        </nav>
        <div class="nav-actions">
          <button id="theme-toggle-btn" class="btn btn-outline btn-sm" aria-label="Toggle dark mode">🌙 Dark</button>
          <a href="login.html" class="btn btn-outline btn-sm">Login</a>
          <a href="register.html" class="btn btn-primary btn-sm">Register</a>
        </div>
      </div>
    </div>
  </header>

  <nav aria-label="Breadcrumb" class="breadcrumb-wrapper">
    <div class="container">
      <ol class="breadcrumb-list" role="list">
        <li class="breadcrumb-item"><a href="index.html">Home</a></li>
        <li class="breadcrumb-separator" aria-hidden="true">›</li>
        <li class="breadcrumb-item" aria-current="page">Practical 7: PHP Form Processing</li>
      </ol>
    </div>
  </nav>

  <section class="page-header" aria-labelledby="page-title">
    <div class="container">
      <h1 id="page-title">Practical 7: PHP Form Processing &amp; Storage</h1>
      <p>Server-side validation, anti-CSRF token, sanitization, and dual CSV / JSON file persistence</p>
    </div>
  </section>

  <main id="main-content" class="section">
    <div class="container" style="max-width: 960px;">

      <!-- Success / Error Status Messages -->
      <?php if ($flashMessage !== ''): ?>
        <div class="p7-message p7-success" role="status">
          <strong>Success:</strong> <?= escapeHtml($flashMessage) ?>
        </div>
      <?php endif; ?>

      <?php if ($errors !== []): ?>
        <div class="p7-message p7-error" role="alert">
          <strong>Validation Errors Detected:</strong>
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?= escapeHtml($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- Form Switcher Tabs -->
      <div class="p7-form-nav">
        <button type="button" class="p7-nav-btn active" id="btn-tab-contact" onclick="switchForm('contact')">
          ✉️ Contact / Support Form
        </button>
        <button type="button" class="p7-nav-btn" id="btn-tab-reg" onclick="switchForm('registration')">
          🎓 Student Registration Form
        </button>
      </div>

      <!-- 1. CONTACT FORM -->
      <section id="form-contact-section" class="form-card" aria-labelledby="contact-heading">
        <h2 id="contact-heading">Contact &amp; Support Ticket Processing</h2>
        <p class="text-muted">Inputs are sanitized and validated on the server with PHP <code>filter_var()</code> and UTF-8 regex.</p>

        <form action="practical7.php" method="post" style="margin-top:1.25rem;">
          <input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['practical7_csrf']) ?>" />
          <input type="hidden" name="form_type" value="contact" />

          <!-- Storage Format Selector -->
          <div class="p7-format-selector">
            <span class="p7-format-label">Select File Storage Format:</span>
            <label><input type="radio" name="storage_format" value="csv" checked /> CSV File</label>
            <label><input type="radio" name="storage_format" value="json" /> JSON File</label>
            <label><input type="radio" name="storage_format" value="both" /> Both (CSV + JSON)</label>
          </div>

          <div class="form-group">
            <label for="contact-name">Full Name <span class="required">*</span></label>
            <input class="form-control" id="contact-name" name="name" type="text" maxlength="80" required value="<?= escapeHtml($contactValues['name']) ?>" placeholder="e.g. Smit Popat" />
          </div>

          <div class="form-group">
            <label for="contact-email">Email Address <span class="required">*</span></label>
            <input class="form-control" id="contact-email" name="email" type="email" maxlength="254" required value="<?= escapeHtml($contactValues['email']) ?>" placeholder="e.g. smit@charusat.edu.in" />
          </div>

          <div class="form-group">
            <label for="contact-subject">Subject <span class="required">*</span></label>
            <select class="form-control" id="contact-subject" name="subject" required>
              <option value="">-- Choose Subject --</option>
              <?php foreach ($subjects as $s): ?>
                <option value="<?= escapeHtml($s) ?>"<?= $contactValues['subject'] === $s ? ' selected' : '' ?>><?= escapeHtml($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="contact-message">Message <span class="required">*</span></label>
            <textarea class="form-control" id="contact-message" name="message" rows="5" maxlength="2000" required placeholder="Type your inquiry or message here (min 10 characters)..."><?= escapeHtml($contactValues['message']) ?></textarea>
          </div>

          <button class="btn btn-primary" type="submit">Submit via Server PHP &rarr;</button>
        </form>
      </section>

      <!-- 2. REGISTRATION FORM -->
      <section id="form-reg-section" class="form-card" style="display:none;" aria-labelledby="reg-heading">
        <h2 id="reg-heading">Student Registration Form (PHP Backend Processing)</h2>
        <p class="text-muted">Server-side validated submission with course and academic year persistence.</p>

        <form action="practical7.php" method="post" style="margin-top:1.25rem;">
          <input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['practical7_csrf']) ?>" />
          <input type="hidden" name="form_type" value="registration" />

          <!-- Storage Format Selector -->
          <div class="p7-format-selector">
            <span class="p7-format-label">Select File Storage Format:</span>
            <label><input type="radio" name="storage_format" value="csv" checked /> CSV File</label>
            <label><input type="radio" name="storage_format" value="json" /> JSON File</label>
            <label><input type="radio" name="storage_format" value="both" /> Both (CSV + JSON)</label>
          </div>

          <div class="form-group">
            <label for="reg-p7-name">Full Name <span class="required">*</span></label>
            <input class="form-control" id="reg-p7-name" name="name" type="text" maxlength="80" required value="<?= escapeHtml($regValues['name']) ?>" placeholder="e.g. Smit Popat" />
          </div>

          <div class="form-group">
            <label for="reg-p7-email">University Email <span class="required">*</span></label>
            <input class="form-control" id="reg-p7-email" name="email" type="email" maxlength="254" required value="<?= escapeHtml($regValues['email']) ?>" placeholder="e.g. smit@charusat.ac.in" />
          </div>

          <div class="form-group">
            <label for="reg-p7-mobile">Mobile Number (10 digits) <span class="required">*</span></label>
            <input class="form-control" id="reg-p7-mobile" name="mobile" type="tel" maxlength="10" required value="<?= escapeHtml($regValues['mobile']) ?>" placeholder="e.g. 9876543210" />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="reg-p7-course">Course Program <span class="required">*</span></label>
              <select class="form-control" id="reg-p7-course" name="course" required>
                <option value="">-- Select Course --</option>
                <?php foreach ($courses as $c): ?>
                  <option value="<?= escapeHtml($c) ?>"<?= $regValues['course'] === $c ? ' selected' : '' ?>><?= escapeHtml($c) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="reg-p7-year">Year of Study <span class="required">*</span></label>
              <select class="form-control" id="reg-p7-year" name="year" required>
                <option value="">-- Select Year --</option>
                <?php foreach ($years as $y): ?>
                  <option value="<?= escapeHtml($y) ?>"<?= $regValues['year'] === $y ? ' selected' : '' ?>><?= escapeHtml($y) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Gender <span class="required">*</span></label>
            <div style="display:flex;gap:1.5rem;margin-top:0.25rem;">
              <?php foreach ($genders as $g): ?>
                <label><input type="radio" name="gender" value="<?= escapeHtml($g) ?>"<?= ($regValues['gender'] === $g || ($g === 'Male' && $regValues['gender'] === '')) ? ' checked' : '' ?> /> <?= escapeHtml($g) ?></label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="form-check" style="margin:1rem 0;">
            <input type="checkbox" id="reg-terms-check" name="terms" value="1" required checked />
            <label for="reg-terms-check">I confirm that all entered details are true and accurate <span class="required">*</span></label>
          </div>

          <button class="btn btn-primary" type="submit">Submit Registration &rarr;</button>
        </form>
      </section>

      <!-- ══════════════════════════════════════════════════════════
           INTERMEDIATE EXTENSION: DISPLAY STORED CSV/JSON RECORDS
           ══════════════════════════════════════════════════════════ -->
      <section class="section" id="records" aria-labelledby="records-title" style="margin-top:2.5rem;">
        <h2 id="records-title">Stored Records Viewer (Intermediate Extension)</h2>
        <p class="text-muted">Displaying server-saved records from protected CSV and JSON files.</p>

        <!-- Records Tab Bar -->
        <div class="p7-form-nav" style="margin-top:1rem;">
          <button type="button" class="p7-nav-btn active" id="btn-view-csv" onclick="switchView('csv')">
            📄 CSV Stored Records (<?= count($csvRecords) ?>)
          </button>
          <button type="button" class="p7-nav-btn" id="btn-view-json" onclick="switchView('json')">
            📦 JSON Stored Records (<?= count($jsonRecords) ?>)
          </button>
        </div>

        <!-- CSV View -->
        <div id="view-csv-panel">
          <?php if ($csvRecords === []): ?>
            <p class="text-muted">No CSV submissions stored yet. Submit the form above to create records.</p>
          <?php else: ?>
            <div class="p7-table-wrap">
              <table class="p7-table">
                <thead>
                  <tr>
                    <th>Timestamp</th>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Details</th>
                    <th>Payload / Message</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($csvRecords as $r): 
                    $hasType = count($r) >= 7;
                    $rTimestamp = $r[0] ?? '';
                    $rType = $hasType ? $r[1] : 'Contact Inquiry';
                    $rName = $hasType ? $r[2] : ($r[1] ?? '');
                    $rEmail = $hasType ? $r[3] : ($r[2] ?? '');
                    $rDetails = $hasType ? $r[4] : ('Subject: ' . ($r[3] ?? ''));
                    $rMsg = $hasType ? $r[5] : ($r[4] ?? '');
                  ?>
                    <tr>
                      <td><small><?= escapeHtml(substr($rTimestamp, 0, 19)) ?></small></td>
                      <td><span class="p7-badge p7-badge-csv"><?= escapeHtml($rType) ?></span></td>
                      <td><strong><?= escapeHtml($rName) ?></strong></td>
                      <td><?= escapeHtml($rEmail) ?></td>
                      <td><small><?= escapeHtml($rDetails) ?></small></td>
                      <td><?= escapeHtml($rMsg) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <!-- JSON View -->
        <div id="view-json-panel" style="display:none;">
          <?php if ($jsonRecords === []): ?>
            <p class="text-muted">No JSON submissions stored yet. Select "JSON File" format above and submit to generate records.</p>
          <?php else: ?>
            <div class="p7-table-wrap">
              <table class="p7-table">
                <thead>
                  <tr>
                    <th>Timestamp</th>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Details</th>
                    <th>Payload / Message</th>
                    <th>Storage</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($jsonRecords as $jr): ?>
                    <tr>
                      <td><small><?= escapeHtml(substr($jr['timestamp'] ?? '', 0, 19)) ?></small></td>
                      <td><span class="p7-badge p7-badge-json"><?= escapeHtml($jr['type'] ?? 'Record') ?></span></td>
                      <td><strong><?= escapeHtml($jr['name'] ?? '') ?></strong></td>
                      <td><?= escapeHtml($jr['email'] ?? '') ?></td>
                      <td><small><?= escapeHtml($jr['info_1'] ?? '') ?></small></td>
                      <td><?= escapeHtml($jr['info_2'] ?? '') ?></td>
                      <td><code><?= escapeHtml($jr['format'] ?? 'JSON') ?></code></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

      </section>

    </div>
  </main>

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
    function switchForm(type) {
      const contactSec = document.getElementById('form-contact-section');
      const regSec = document.getElementById('form-reg-section');
      const btnContact = document.getElementById('btn-tab-contact');
      const btnReg = document.getElementById('btn-tab-reg');

      if (type === 'contact') {
        contactSec.style.display = 'block';
        regSec.style.display = 'none';
        btnContact.classList.add('active');
        btnReg.classList.remove('active');
      } else {
        contactSec.style.display = 'none';
        regSec.style.display = 'block';
        btnContact.classList.remove('active');
        btnReg.classList.add('active');
      }
    }

    function switchView(format) {
      const csvPanel = document.getElementById('view-csv-panel');
      const jsonPanel = document.getElementById('view-json-panel');
      const btnCsv = document.getElementById('btn-view-csv');
      const btnJson = document.getElementById('btn-view-json');

      if (format === 'csv') {
        csvPanel.style.display = 'block';
        jsonPanel.style.display = 'none';
        btnCsv.classList.add('active');
        btnJson.classList.remove('active');
      } else {
        csvPanel.style.display = 'none';
        jsonPanel.style.display = 'block';
        btnCsv.classList.remove('active');
        btnJson.classList.add('active');
      }
    }
  </script>
</body>
</html>