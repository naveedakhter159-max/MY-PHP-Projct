<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'payroll_management');

function getDBConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die('<div style="font-family:sans-serif;text-align:center;padding:60px">
                <h2 style="color:#c62828">Database Connection Failed</h2>
                <p>Error: ' . htmlspecialchars($conn->connect_error) . '</p>
                <p>Please import <code>sql/payroll.sql</code> and check your credentials in <code>config/database.php</code></p>
            </div>');
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

function esc($str) {
    return htmlspecialchars(trim((string)$str), ENT_QUOTES, 'UTF-8');
}

function getSetting($key) {
    $conn = getDBConnection();
    $k = $conn->real_escape_string($key);
    $r = $conn->query("SELECT key_value FROM settings WHERE key_name='$k' LIMIT 1");
    return ($r && $r->num_rows) ? $r->fetch_assoc()['key_value'] : '';
}

function currency($amount) {
    $sym = getSetting('currency_symbol') ?: '$';
    return $sym . ' ' . number_format((float)$amount, 2);
}

function fmtDate($date) {
    if (!$date || $date === '0000-00-00') return '-';
    return date('M d, Y', strtotime($date));
}

function monthName($m) { return date('F', mktime(0,0,0,$m,1)); }

function redirect($url) { header("Location: $url"); exit(); }

function setFlash($type, $msg) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function letterColor($str) {
    $colors = ['color-0','color-1','color-2','color-3','color-4','color-5','color-6','color-7'];
    return $colors[ord(strtoupper($str[0])) % count($colors)];
}

function companyIdGen() {
    $conn = getDBConnection();
    $r = $conn->query("SELECT company_id FROM companies ORDER BY id DESC LIMIT 1")->fetch_assoc();
    if ($r) {
        preg_match('/(\d+)$/', $r['company_id'], $m);
        $next = ($m[1] ?? 0) + 1;
    } else { $next = 1; }
    return 'Comp-' . str_pad($next, 3, '0', STR_PAD_LEFT);
}

function employeeIdGen() {
    $conn = getDBConnection();
    $r = $conn->query("SELECT employee_id FROM employees ORDER BY id DESC LIMIT 1")->fetch_assoc();
    if ($r) {
        preg_match('/(\d+)$/', $r['employee_id'], $m);
        $next = ($m[1] ?? 0) + 1;
    } else { $next = 1; }
    return 'EMP-' . str_pad($next, 3, '0', STR_PAD_LEFT);
}
