<?php
/**
 * CHARUSAT - ITUE203: Web Development Frameworks
 * StudentHub Portal - PHP JSON RESTful API for Students (Practical 13)
 * 
 * Endpoints:
 * - GET    /api-students.php                 -> List students (search, filter, pagination)
 * - GET    /api-students.php?id={id}          -> Fetch single student record
 * - POST   /api-students.php                 -> Create student record (returns 201)
 * - PUT    /api-students.php (or POST _method=PUT) -> Update student record (returns 200)
 * - DELETE /api-students.php (or POST _method=DELETE) -> Soft/Hard delete student record (returns 200)
 * 
 * Complies with Practical 13 Requirements:
 * 1. Valid JSON responses with HTTP status codes (200, 201, 400, 404, 405, 500)
 * 2. GET and POST/PUT/DELETE request handling
 * 3. Centralized error handling & validation
 * 4. Audit logging on mutations
 */

declare(strict_types=1);

// Set JSON headers and CORS for API access
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle pre-flight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'db.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

global $conn;

/**
 * Standard JSON Response Sender (Advanced Extension: Centralized error handling & status codes)
 */
function sendApiResponse(int $statusCode, bool $success, string $message, $data = null, array $errors = [], array $meta = []): void
{
    http_response_code($statusCode);
    $response = [
        'success'    => $success,
        'statusCode' => $statusCode,
        'message'    => $message,
        'timestamp'  => date('c'),
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }
    if ($errors !== []) {
        $response['errors'] = $errors;
    }
    if ($meta !== []) {
        $response['meta'] = $meta;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Parses request payload from either JSON or URL-encoded body
 */
function getParsedInput(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $json = json_decode((string)$raw, true);
        return is_array($json) ? $json : [];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return $_POST;
    }

    // For PUT / DELETE with form-urlencoded
    $raw = file_get_contents('php://input');
    parse_str((string)$raw, $parsed);
    return is_array($parsed) ? $parsed : [];
}

/**
 * Log action to audit_logs
 */
function logApiAudit(string $action, string $details): void
{
    global $conn;
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1; // Default to admin/system if not logged in
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $userId, $action, $details, $ip);
        $stmt->execute();
        $stmt->close();
    }
}

// Determine HTTP Method (Support Method Override via ?_method= or header)
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    $input = getParsedInput();
    if (isset($input['_method'])) {
        $method = strtoupper((string)$input['_method']);
    } elseif (isset($_GET['_method'])) {
        $method = strtoupper((string)$_GET['_method']);
    }
}

// ============================================================================
// ROUTING BASED ON HTTP METHOD
// ============================================================================

switch ($method) {

    // ────────────────────────────────────────────────────────────────────────
    // GET: Fetch Single Student OR List Students with Search, Filter & Pagination
    // ────────────────────────────────────────────────────────────────────────
    case 'GET':
        // 1. Single Student by ID
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $conn->prepare("SELECT id, student_id, full_name, email, phone, department, course, semester, academic_year, gender, cgpa, status, created_at, updated_at FROM students WHERE id = ? LIMIT 1");
            if (!$stmt) {
                sendApiResponse(500, false, 'Database query preparation failed.');
            }
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $student = $res->fetch_assoc();
            $stmt->close();

            if (!$student) {
                sendApiResponse(404, false, "Student with ID #{$id} not found.");
            }

            sendApiResponse(200, true, "Student record retrieved successfully.", $student);
        }

        // 2. List Students (Search with debounce, Course Filter, Status Filter, Pagination)
        $search = trim((string)($_GET['search'] ?? $_GET['q'] ?? ''));
        $course = trim((string)($_GET['course'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $where = ["1=1"];
        $params = [];
        $types = "";

        if ($search !== '') {
            $where[] = "(student_id LIKE ? OR full_name LIKE ? OR email LIKE ? OR department LIKE ? OR course LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
            $types .= "sssss";
        }

        if ($course !== '' && $course !== 'All') {
            $where[] = "course = ?";
            $params[] = $course;
            $types .= "s";
        }

        if ($status !== '' && $status !== 'All') {
            $where[] = "status = ?";
            $params[] = $status;
            $types .= "s";
        }

        $whereClause = implode(" AND ", $where);

        // Count Total Matches for Pagination
        $countSql = "SELECT COUNT(*) AS total FROM students WHERE {$whereClause}";
        $countStmt = $conn->prepare($countSql);
        if ($types !== "") {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $totalRows = (int)$countStmt->get_result()->fetch_assoc()['total'];
        $countStmt->close();

        // Fetch Paginated Records
        $querySql = "SELECT id, student_id, full_name, email, phone, department, course, semester, academic_year, gender, cgpa, status, created_at FROM students WHERE {$whereClause} ORDER BY id DESC LIMIT ? OFFSET ?";
        $queryStmt = $conn->prepare($querySql);

        $paramsWithLimit = $params;
        $paramsWithLimit[] = $limit;
        $paramsWithLimit[] = $offset;
        $typesWithLimit = $types . "ii";

        $queryStmt->bind_param($typesWithLimit, ...$paramsWithLimit);
        $queryStmt->execute();
        $result = $queryStmt->get_result();

        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        $queryStmt->close();

        $totalPages = (int)ceil($totalRows / $limit);

        sendApiResponse(
            200,
            true,
            "Students retrieved successfully.",
            $students,
            [],
            [
                'pagination' => [
                    'currentPage'  => $page,
                    'limit'        => $limit,
                    'totalRecords' => $totalRows,
                    'totalPages'   => $totalPages,
                    'hasNextPage'  => $page < $totalPages,
                    'hasPrevPage'  => $page > 1
                ],
                'filters' => [
                    'search' => $search,
                    'course' => $course,
                    'status' => $status
                ]
            ]
        );
        break;

    // ────────────────────────────────────────────────────────────────────────
    // POST: Create New Student
    // ────────────────────────────────────────────────────────────────────────
    case 'POST':
        $data = getParsedInput();
        $errors = [];

        $studentId = strtoupper(trim((string)($data['student_id'] ?? '')));
        $fullName  = trim((string)($data['full_name'] ?? ''));
        $email     = strtolower(trim((string)($data['email'] ?? '')));
        $phone     = trim((string)($data['phone'] ?? ''));
        $course    = trim((string)($data['course'] ?? ''));
        $semester  = (int)($data['semester'] ?? 3);
        $academicYear = trim((string)($data['academic_year'] ?? '2nd Year'));
        $gender    = trim((string)($data['gender'] ?? 'Male'));
        $cgpa      = (float)($data['cgpa'] ?? 8.00);
        $status    = trim((string)($data['status'] ?? 'Active'));

        // Derive department
        $dept = (strpos($course, 'IT') !== false) ? 'Information Technology' :
                ((strpos($course, 'Electrical') !== false) ? 'Electrical Engineering' :
                ((strpos($course, 'Mechanical') !== false) ? 'Mechanical Engineering' :
                ((strpos($course, 'Civil') !== false) ? 'Civil Engineering' : 'Computer Engineering')));

        // Validations
        if ($studentId === '') {
            $errors['student_id'] = 'Student ID (Roll No) is required.';
        } elseif (!preg_match('/^[A-Za-z0-9]{5,15}$/', $studentId)) {
            $errors['student_id'] = 'Student ID must be 5-15 alphanumeric characters (e.g. D26DCE173).';
        }

        if ($fullName === '') {
            $errors['full_name'] = 'Full Name is required.';
        } elseif (strlen($fullName) < 3) {
            $errors['full_name'] = 'Full Name must be at least 3 characters.';
        }

        if ($email === '') {
            $errors['email'] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format.';
        }

        if ($phone === '') {
            $errors['phone'] = 'Phone number is required.';
        } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
            $errors['phone'] = 'Phone number must contain 10-15 digits.';
        }

        if ($course === '') {
            $errors['course'] = 'Degree course selection is required.';
        }

        if ($cgpa < 0.0 || $cgpa > 10.0) {
            $errors['cgpa'] = 'CGPA must be between 0.00 and 10.00.';
        }

        // Check Duplicates in DB
        if ($errors === []) {
            $dupStmt = $conn->prepare("SELECT id FROM students WHERE student_id = ? OR email = ? LIMIT 1");
            $dupStmt->bind_param("ss", $studentId, $email);
            $dupStmt->execute();
            if ($dupStmt->get_result()->fetch_assoc()) {
                $errors['general'] = "A student with Student ID '{$studentId}' or Email '{$email}' already exists.";
            }
            $dupStmt->close();
        }

        if ($errors !== []) {
            sendApiResponse(400, false, 'Validation failed. Please correct the highlighted errors.', null, $errors);
        }

        // Insert Record
        $insertStmt = $conn->prepare("INSERT INTO students (student_id, full_name, email, phone, department, course, semester, academic_year, gender, cgpa, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$insertStmt) {
            sendApiResponse(500, false, 'Failed to prepare database insert statement: ' . $conn->error);
        }

        $insertStmt->bind_param("ssssssisdds", $studentId, $fullName, $email, $phone, $dept, $course, $semester, $academicYear, $gender, $cgpa, $status);
        if ($insertStmt->execute()) {
            $newId = $conn->insert_id;
            $insertStmt->close();

            logApiAudit('STUDENT_API_CREATED', "Created student #{$newId} ({$studentId} - {$fullName}) via JSON API");

            // Fetch newly inserted object
            $fetchStmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
            $fetchStmt->bind_param("i", $newId);
            $fetchStmt->execute();
            $createdStudent = $fetchStmt->get_result()->fetch_assoc();
            $fetchStmt->close();

            sendApiResponse(201, true, "Student '{$fullName}' added successfully via API.", $createdStudent);
        } else {
            sendApiResponse(500, false, 'Database execution error: ' . $insertStmt->error);
        }
        break;

    // ────────────────────────────────────────────────────────────────────────
    // PUT: Update Existing Student
    // ────────────────────────────────────────────────────────────────────────
    case 'PUT':
        $data = getParsedInput();
        $errors = [];

        $id = (int)($data['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            sendApiResponse(400, false, 'A valid numeric Student ID is required for updating.');
        }

        // Verify Student Exists
        $checkStmt = $conn->prepare("SELECT id FROM students WHERE id = ? LIMIT 1");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        if (!$checkStmt->get_result()->fetch_assoc()) {
            $checkStmt->close();
            sendApiResponse(404, false, "Student with ID #{$id} does not exist.");
        }
        $checkStmt->close();

        $studentId = strtoupper(trim((string)($data['student_id'] ?? '')));
        $fullName  = trim((string)($data['full_name'] ?? ''));
        $email     = strtolower(trim((string)($data['email'] ?? '')));
        $phone     = trim((string)($data['phone'] ?? ''));
        $course    = trim((string)($data['course'] ?? ''));
        $semester  = (int)($data['semester'] ?? 3);
        $academicYear = trim((string)($data['academic_year'] ?? '2nd Year'));
        $gender    = trim((string)($data['gender'] ?? 'Male'));
        $cgpa      = (float)($data['cgpa'] ?? 8.00);
        $status    = trim((string)($data['status'] ?? 'Active'));

        $dept = (strpos($course, 'IT') !== false) ? 'Information Technology' :
                ((strpos($course, 'Electrical') !== false) ? 'Electrical Engineering' :
                ((strpos($course, 'Mechanical') !== false) ? 'Mechanical Engineering' :
                ((strpos($course, 'Civil') !== false) ? 'Civil Engineering' : 'Computer Engineering')));

        if ($studentId === '') {
            $errors['student_id'] = 'Student ID cannot be empty.';
        }
        if ($fullName === '') {
            $errors['full_name'] = 'Full Name cannot be empty.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required.';
        }
        if ($phone === '') {
            $errors['phone'] = 'Phone number is required.';
        }

        // Check duplicate excluding self
        if ($errors === []) {
            $dupStmt = $conn->prepare("SELECT id FROM students WHERE (student_id = ? OR email = ?) AND id != ? LIMIT 1");
            $dupStmt->bind_param("ssi", $studentId, $email, $id);
            $dupStmt->execute();
            if ($dupStmt->get_result()->fetch_assoc()) {
                $errors['general'] = "Another student with Student ID '{$studentId}' or Email '{$email}' already exists.";
            }
            $dupStmt->close();
        }

        if ($errors !== []) {
            sendApiResponse(400, false, 'Validation failed for update.', null, $errors);
        }

        $updateStmt = $conn->prepare("UPDATE students SET student_id = ?, full_name = ?, email = ?, phone = ?, department = ?, course = ?, semester = ?, academic_year = ?, gender = ?, cgpa = ?, status = ? WHERE id = ?");
        if (!$updateStmt) {
            sendApiResponse(500, false, 'Failed to prepare update query: ' . $conn->error);
        }

        $updateStmt->bind_param("ssssssisddsi", $studentId, $fullName, $email, $phone, $dept, $course, $semester, $academicYear, $gender, $cgpa, $status, $id);
        if ($updateStmt->execute()) {
            $updateStmt->close();

            logApiAudit('STUDENT_API_UPDATED', "Updated student #{$id} ({$studentId} - {$fullName}) via JSON API");

            // Return fresh record
            $fetchStmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
            $fetchStmt->bind_param("i", $id);
            $fetchStmt->execute();
            $updatedStudent = $fetchStmt->get_result()->fetch_assoc();
            $fetchStmt->close();

            sendApiResponse(200, true, "Student record #{$id} updated successfully.", $updatedStudent);
        } else {
            sendApiResponse(500, false, 'Database update failed: ' . $updateStmt->error);
        }
        break;

    // ────────────────────────────────────────────────────────────────────────
    // DELETE: Delete or Soft-Delete Student Record
    // ────────────────────────────────────────────────────────────────────────
    case 'DELETE':
        $data = getParsedInput();
        $id = (int)($data['id'] ?? $_GET['id'] ?? 0);
        $mode = (string)($data['mode'] ?? $_GET['mode'] ?? 'soft'); // 'soft' or 'hard'

        if ($id <= 0) {
            sendApiResponse(400, false, 'Valid numeric student ID required for deletion.');
        }

        // Check if student exists
        $chk = $conn->prepare("SELECT student_id, full_name FROM students WHERE id = ? LIMIT 1");
        $chk->bind_param("i", $id);
        $chk->execute();
        $st = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$st) {
            sendApiResponse(404, false, "Student with ID #{$id} not found.");
        }

        $studentName = $st['full_name'];
        $studentRoll = $st['student_id'];

        if ($mode === 'hard') {
            // Permanent hard delete
            $delStmt = $conn->prepare("DELETE FROM students WHERE id = ?");
            $delStmt->bind_param("i", $id);
            $delStmt->execute();
            $delStmt->close();

            logApiAudit('STUDENT_API_PERMANENT_DELETED', "Permanently deleted student #{$id} ({$studentRoll} - {$studentName})");
            sendApiResponse(200, true, "Student '{$studentName}' (#{$id}) was permanently deleted from the database.");
        } else {
            // Safe soft delete: sets status = 'Inactive'
            $softStmt = $conn->prepare("UPDATE students SET status = 'Inactive' WHERE id = ?");
            $softStmt->bind_param("i", $id);
            $softStmt->execute();
            $softStmt->close();

            logApiAudit('STUDENT_API_SOFT_DELETED', "Soft deleted (deactivated) student #{$id} ({$studentRoll} - {$studentName})");
            sendApiResponse(200, true, "Student '{$studentName}' (#{$id}) marked as Inactive (soft delete).");
        }
        break;

    default:
        sendApiResponse(405, false, "HTTP Method '{$method}' is not allowed on this endpoint. Allowed methods: GET, POST, PUT, DELETE.");
        break;
}
