<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin(1);
if (!canDo('Employees','edit')) { setFlash('error','Permission denied.'); redirect('index.php'); }
$conn = getDBConnection();
$action = $_POST['action'] ?? '';

function empField($conn, $key, $default='') {
    return $conn->real_escape_string(trim($_POST[$key] ?? $default));
}

if ($action === 'add') {
    $eid    = employeeIdGen();
    $fn     = empField($conn,'first_name');
    $ln     = empField($conn,'last_name');
    $email  = empField($conn,'email');
    $phone  = empField($conn,'phone');
    $cid    = (int)($_POST['company_id'] ?? 0);
    $dept   = empField($conn,'department');
    $pos    = empField($conn,'position');
    $sal    = (float)($_POST['salary'] ?? 0);
    $start  = empField($conn,'start_date') ?: 'NULL';
    $emp_t  = empField($conn,'employment_type','Full-Time');
    $status = empField($conn,'status','Active');
    $gender = empField($conn,'gender','Male');

    if (empty($fn)||empty($email)) { setFlash('error','Name and email required.'); redirect('index.php'); }

    $startVal = $start !== 'NULL' ? "'$start'" : 'NULL';
    $conn->query("INSERT INTO employees (employee_id,first_name,last_name,email,phone,company_id,department,position,salary,start_date,employment_type,status,gender)
        VALUES ('$eid','$fn','$ln','$email','$phone'," . ($cid?$cid:'NULL') . ",'$dept','$pos',$sal,$startVal,'$emp_t','$status','$gender')");
    setFlash($conn->affected_rows>0?'success':'error', $conn->affected_rows>0?'Employee added!':'Failed: '.$conn->error);

} elseif ($action === 'edit') {
    $id     = (int)($_POST['id'] ?? 0);
    $fn     = empField($conn,'first_name');
    $ln     = empField($conn,'last_name');
    $email  = empField($conn,'email');
    $phone  = empField($conn,'phone');
    $cid    = (int)($_POST['company_id'] ?? 0);
    $dept   = empField($conn,'department');
    $pos    = empField($conn,'position');
    $sal    = (float)($_POST['salary'] ?? 0);
    $start  = empField($conn,'start_date');
    $emp_t  = empField($conn,'employment_type','Full-Time');
    $status = empField($conn,'status','Active');
    $startVal = $start ? "'$start'" : 'NULL';

    $conn->query("UPDATE employees SET first_name='$fn',last_name='$ln',email='$email',phone='$phone',
        company_id=" . ($cid?$cid:'NULL') . ",department='$dept',position='$pos',salary=$sal,start_date=$startVal,
        employment_type='$emp_t',status='$status' WHERE id=$id");
    setFlash('success','Employee updated!');
}
redirect('index.php');
