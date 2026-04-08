<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'payroll_management');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'PayRoll Pro');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '');

// Create database connection
function getDBConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die('<div style="text-align:center;padding:50px;font-family:Arial;">
                <h2 style="color:#dc3545;">Database Connection Failed</h2>
                <p>Error: ' . htmlspecialchars($conn->connect_error) . '</p>
                <p>Please ensure MySQL is running and the database <strong>' . DB_NAME . '</strong> exists.</p>
                <p>Import the SQL file: <code>payroll-management/sql/payroll.sql</code></p>
            </div>');
        }
        $conn->set_charset(DB_CHARSET);
    }
    return $conn;
}

// Helper: Escape string
function escape($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

// Helper: Get setting
function getSetting($key) {
    $conn = getDBConnection();
    $key = $conn->real_escape_string($key);
    $result = $conn->query("SELECT key_value FROM settings WHERE key_name='$key' LIMIT 1");
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc()['key_value'];
    }
    return '';
}

// Helper: Currency symbol
function currency($amount) {
    $symbol = getSetting('currency_symbol') ?: '$';
    return $symbol . number_format((float)$amount, 2);
}

// Helper: Format date
function formatDate($date) {
    if (!$date || $date === '0000-00-00') return '-';
    return date('d M Y', strtotime($date));
}

// Helper: Month name
function monthName($month) {
    return date('F', mktime(0, 0, 0, $month, 1));
}

// Redirect helper
function redirect($url) {
    header("Location: $url");
    exit();
}

// Flash message
function setFlash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
