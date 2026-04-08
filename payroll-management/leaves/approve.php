<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();
$conn = getDBConnection();
$id     = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';
if (!$id || !in_array($action, ['approve','reject'])) { setFlash('error','Invalid action.'); redirect('index.php'); }
$status = $action === 'approve' ? 'Approved' : 'Rejected';
$userId = (int)getCurrentUser()['id'];
$conn->query("UPDATE leaves SET status='$status', approved_by=$userId WHERE id=$id");
setFlash('success', "Leave $status successfully.");
redirect('index.php');
