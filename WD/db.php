<?php
/**
 * CHARUSAT - ITUE203: Web Development Frameworks
 * StudentHub Portal - Database Connection & Configuration
 * Supports: MySQLi & PDO with Prepared Statements (Practical 8)
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration Constants
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'studenthub_db');
define('DB_CHARSET', 'utf8mb4');

/**
 * Determine active database port (checks 3307 first for local XAMPP setup, falls back to 3306)
 */
function getDbPort(): int
{
    static $detectedPort = null;
    if ($detectedPort !== null) {
        return $detectedPort;
    }

    if ($envPort = getenv('DB_PORT')) {
        $detectedPort = (int)$envPort;
        return $detectedPort;
    }

    // Test port 3307 (common XAMPP alternative port)
    $fp = @fsockopen(DB_HOST, 3307, $errno, $errstr, 0.2);
    if ($fp) {
        fclose($fp);
        $detectedPort = 3307;
        return $detectedPort;
    }

    // Default standard MySQL port
    $detectedPort = 3306;
    return $detectedPort;
}

/**
 * Returns a shared MySQLi connection instance
 */
function getDbConnection(): mysqli
{
    static $mysqli = null;
    if ($mysqli instanceof mysqli && @$mysqli->ping()) {
        return $mysqli;
    }

    $port = getDbPort();
    mysqli_report(MYSQLI_REPORT_OFF);

    // Initial attempt to connect with DB name
    $mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, $port);

    if ($mysqli->connect_errno) {
        // If database does not exist yet, connect to server and create it
        $rootConn = @new mysqli(DB_HOST, DB_USER, DB_PASS, '', $port);
        if (!$rootConn->connect_errno) {
            $rootConn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $rootConn->close();

            // Reconnect to newly created DB
            $mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, $port);

            // Execute schema if database.sql is present
            $sqlFile = __DIR__ . DIRECTORY_SEPARATOR . 'database.sql';
            if (is_readable($sqlFile)) {
                $sqlContent = file_get_contents($sqlFile);
                if ($sqlContent) {
                    $mysqli->multi_query($sqlContent);
                    while ($mysqli->more_results() && $mysqli->next_result()) {
                        // flush multi-query buffers
                    }
                }
            }
        }
    }

    if ($mysqli->connect_errno) {
        die('<div style="font-family:sans-serif;padding:2rem;background:#fee2e2;border:1px solid #ef4444;color:#991b1b;border-radius:8px;max-width:600px;margin:2rem auto;">
            <h3 style="margin-top:0;">Database Connection Error</h3>
            <p>Could not connect to MySQL server at <strong>' . htmlspecialchars(DB_HOST) . ':' . $port . '</strong>.</p>
            <p><strong>Error:</strong> ' . htmlspecialchars($mysqli->connect_error) . '</p>
            <p>Please ensure XAMPP MySQL/MariaDB service is running and credentials are correct.</p>
        </div>');
    }

    $mysqli->set_charset(DB_CHARSET);
    return $mysqli;
}

/**
 * Returns a shared PDO connection instance (Practical 8 Intermediate Extension)
 */
function getPdoConnection(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $port = getDbPort();
    $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Attempt to auto-create DB via root PDO
        try {
            $rootPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . $port, DB_USER, DB_PASS);
            $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $inner) {
            die('<div style="font-family:sans-serif;padding:2rem;background:#fee2e2;color:#991b1b;border-radius:8px;max-width:600px;margin:2rem auto;">
                <h3>PDO Connection Failed</h3>
                <p>' . htmlspecialchars($e->getMessage()) . '</p>
            </div>');
        }
    }

    return $pdo;
}

/**
 * Global $conn instance for direct procedural scripts
 */
$conn = getDbConnection();

/**
 * Optional browser-accessible diagnostics check for Practical 8 viva/submission
 */
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $mysqli = getDbConnection();
    $pdo = getPdoConnection();
    $tablesResult = $mysqli->query("SHOW TABLES");
    $tables = [];
    if ($tablesResult) {
        while ($row = $tablesResult->fetch_row()) {
            $tables[] = $row[0];
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Database Status &mdash; StudentHub</title>
      <link rel="stylesheet" href="css/styles.css">
      <style>
        .db-card { max-width: 650px; margin: 3rem auto; background: var(--bg-card, #fff); padding: 2rem; border-radius: 12px; border: 1px solid var(--border, #e2e8f0); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .status-badge { display: inline-block; padding: 0.35rem 0.85rem; border-radius: 9999px; font-weight: 600; font-size: 0.875rem; background: #dcfce7; color: #166534; }
        .table-list { list-style: none; padding: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-top: 1rem; }
        .table-item { background: #f8fafc; padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid #e2e8f0; font-family: monospace; font-size: 0.9rem; }
      </style>
    </head>
    <body style="background: #f1f5f9; padding: 1rem;">
      <div class="db-card">
        <h2 style="margin-top:0;">StudentHub Database Connectivity</h2>
        <p><span class="status-badge">CONNECTED (MySQLi &amp; PDO Active)</span></p>
        <p><strong>Host:</strong> <?= htmlspecialchars(DB_HOST) ?> | <strong>Port:</strong> <?= getDbPort() ?> | <strong>Database:</strong> <?= htmlspecialchars(DB_NAME) ?></p>
        <p><strong>Server Version:</strong> <?= htmlspecialchars($mysqli->server_info) ?></p>
        <hr style="border:0;border-top:1px solid #e2e8f0;margin:1.5rem 0;">
        <h3>Normalized Tables Detected (<?= count($tables) ?>):</h3>
        <ul class="table-list">
          <?php foreach ($tables as $t): ?>
            <li class="table-item"><?= htmlspecialchars($t) ?></li>
          <?php endforeach; ?>
        </ul>
        <div style="margin-top: 1.5rem;">
          <a href="index.html" class="btn btn-outline btn-sm">&larr; Back to StudentHub</a>
          <a href="login.php" class="btn btn-primary btn-sm" style="margin-left:0.5rem;">Proceed to Login &rarr;</a>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
}
