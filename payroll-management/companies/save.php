<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
$conn = getDBConnection();
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $id   = companyIdGen();
    $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $ind  = $conn->real_escape_string(trim($_POST['industry'] ?? ''));
    $email= $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $phone= $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $city = $conn->real_escape_string(trim($_POST['city'] ?? ''));
    $state= $conn->real_escape_string(trim($_POST['state'] ?? ''));
    $addr = $conn->real_escape_string(trim($_POST['address'] ?? ''));
    $web  = $conn->real_escape_string(trim($_POST['website'] ?? ''));
    $stat = $conn->real_escape_string(trim($_POST['status'] ?? 'Active'));
    if (empty($name)) { setFlash('error','Company name required.'); redirect('index.php'); }
    $conn->query("INSERT INTO companies (company_id,name,industry,email,phone,city,state,address,website,status) VALUES ('$id','$name','$ind','$email','$phone','$city','$state','$addr','$web','$stat')");
    setFlash($conn->affected_rows>0?'success':'error', $conn->affected_rows>0?'Company added!':'Failed: '.$conn->error);
} elseif ($action === 'edit') {
    $cid  = (int)($_POST['id'] ?? 0);
    $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $ind  = $conn->real_escape_string(trim($_POST['industry'] ?? ''));
    $email= $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $phone= $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $city = $conn->real_escape_string(trim($_POST['city'] ?? ''));
    $state= $conn->real_escape_string(trim($_POST['state'] ?? ''));
    $addr = $conn->real_escape_string(trim($_POST['address'] ?? ''));
    $web  = $conn->real_escape_string(trim($_POST['website'] ?? ''));
    $stat = $conn->real_escape_string(trim($_POST['status'] ?? 'Active'));
    $conn->query("UPDATE companies SET name='$name',industry='$ind',email='$email',phone='$phone',city='$city',state='$state',address='$addr',website='$web',status='$stat' WHERE id=$cid");
    setFlash('success','Company updated!');
}
redirect('index.php');
