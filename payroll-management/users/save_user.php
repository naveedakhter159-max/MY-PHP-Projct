<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
$conn = getDBConnection();

$action = $_POST['action'] ?? '';
$back   = 'index.php?tab=users';

if ($action === 'add') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');
    $role     = trim($_POST['role'] ?? 'hr');
    $status   = (int)($_POST['status'] ?? 1);

    if (!$fullName || !$username || !$email || !$password) {
        setFlash('error', 'All required fields must be filled.');
        redirect($back);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Invalid email address.');
        redirect($back);
    }
    if (strlen($password) < 6) {
        setFlash('error', 'Password must be at least 6 characters.');
        redirect($back);
    }
    if ($password !== $confirm) {
        setFlash('error', 'Passwords do not match.');
        redirect($back);
    }

    $uE = $conn->real_escape_string($username);
    $eE = $conn->real_escape_string($email);
    if ($conn->query("SELECT id FROM users WHERE username='$uE' OR email='$eE' LIMIT 1")->num_rows > 0) {
        setFlash('error', 'Username or email already exists.');
        redirect($back);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $fnE  = $conn->real_escape_string($fullName);
    $hE   = $conn->real_escape_string($hash);
    $rE   = $conn->real_escape_string($role);

    $conn->query("INSERT INTO users (full_name,username,email,password,role,status)
                  VALUES ('$fnE','$uE','$eE','$hE','$rE',$status)");

    if ($conn->affected_rows > 0) {
        setFlash('success', "User '$fullName' created successfully!");
    } else {
        setFlash('error', 'Failed to create user: ' . $conn->error);
    }
    redirect($back);
}

if ($action === 'edit') {
    $id       = (int)($_POST['id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = trim($_POST['role'] ?? '');
    $status   = (int)($_POST['status'] ?? 1);

    if (!$id || !$fullName || !$username || !$email) {
        setFlash('error', 'Required fields missing.');
        redirect($back);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Invalid email address.');
        redirect($back);
    }
    if ($password && strlen($password) < 6) {
        setFlash('error', 'New password must be at least 6 characters.');
        redirect($back);
    }

    $fnE = $conn->real_escape_string($fullName);
    $uE  = $conn->real_escape_string($username);
    $eE  = $conn->real_escape_string($email);
    $rE  = $conn->real_escape_string($role);

    // Check duplicate username/email for OTHER users
    $dup = $conn->query("SELECT id FROM users WHERE (username='$uE' OR email='$eE') AND id!=$id LIMIT 1");
    if ($dup->num_rows > 0) {
        setFlash('error', 'Username or email already used by another user.');
        redirect($back);
    }

    $passwordSql = '';
    if ($password) {
        $hash = $conn->real_escape_string(password_hash($password, PASSWORD_DEFAULT));
        $passwordSql = ", password='$hash'";
    }

    $conn->query("UPDATE users SET
        full_name='$fnE', username='$uE', email='$eE',
        role='$rE', status=$status $passwordSql
        WHERE id=$id");

    if ($conn->affected_rows >= 0) {
        setFlash('success', "User '$fullName' updated successfully!");
    } else {
        setFlash('error', 'No changes made or error: ' . $conn->error);
    }
    redirect($back);
}

setFlash('error', 'Invalid action.');
redirect($back);
