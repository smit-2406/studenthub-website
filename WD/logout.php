<?php
/**
 * CHARUSAT - ITUE203: Web Development Frameworks
 * StudentHub Portal - Secure Logout Handler (Practical 10)
 */

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

logoutUser();

header('Location: login.php?msg=logged_out');
exit;
