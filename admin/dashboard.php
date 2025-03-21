<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
  // Redirect to login page if not logged in
  header("Location: login.php");
  exit();
}

// Get the base path from session or set a default
$base_path = isset($_SESSION['base_path']) ? $_SESSION['base_path'] : '';

// Redirect based on user role
if ($_SESSION['user_role'] === 'super_admin') {
  header("Location: {$base_path}/admin/super-admin/dashboard.php");
  exit();
} elseif ($_SESSION['user_role'] === 'branch_admin') {
  // Check if branch admin has a branch assigned
  require_once '../includes/db_connect.php';
  $admin_id = $_SESSION['user_id'];
  
  $stmt = $conn->prepare("SELECT b.id, b.name FROM branches b WHERE b.admin_id = ?");
  $stmt->bind_param("i", $admin_id);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows > 0) {
    // Branch admin has a branch assigned, redirect to branch admin dashboard
    $branch = $result->fetch_assoc();
    $_SESSION['admin_branch_id'] = $branch['id'];
    $_SESSION['admin_branch_name'] = $branch['name'];
    header("Location: {$base_path}/admin/branch-admin/dashboard.php");
    exit();
  } else {
    // Branch admin doesn't have a branch assigned
    $_SESSION['login_errors'] = ["You don't have a branch assigned. Please contact the super admin."];
    header("Location: login.php");
    exit();
  }
} else {
  // Invalid role
  $_SESSION['login_errors'] = ["Invalid role. Please contact the administrator."];
  header("Location: login.php");
  exit();
}
?>

