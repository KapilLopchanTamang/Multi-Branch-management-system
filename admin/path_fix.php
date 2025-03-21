<?php
// Enable error reporting for troubleshooting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Admin Path Troubleshooting</h1>";

// Start session
session_start();
echo "<h2>1. Session Check</h2>";
echo "Session status: " . (session_status() === PHP_SESSION_ACTIVE ? "Active" : "Inactive") . "<br>";
echo "Session ID: " . session_id() . "<br>";

// Check current directory structure
echo "<h2>2. Directory Structure Check</h2>";
$current_dir = dirname(__FILE__);
echo "Current directory: " . $current_dir . "<br>";
$parent_dir = dirname($current_dir);
echo "Parent directory: " . $parent_dir . "<br>";

// Detect base path
echo "<h2>3. Base Path Detection</h2>";
$script_name = $_SERVER['SCRIPT_NAME'];
$script_path = dirname($script_name);
$admin_pos = strpos($script_path, '/admin');
$base_path = $admin_pos !== false ? substr($script_path, 0, $admin_pos) : '';

echo "Script name: " . $script_name . "<br>";
echo "Script path: " . $script_path . "<br>";
echo "Admin position: " . ($admin_pos !== false ? $admin_pos : "Not found") . "<br>";
echo "Detected base path: " . ($base_path ? $base_path : "None") . "<br>";

// Set base path in session
$_SESSION['base_path'] = $base_path;
echo "Base path set in session: " . $_SESSION['base_path'] . "<br>";

// Check if dashboard files exist
echo "<h2>4. Dashboard Files Check</h2>";
$super_admin_dashboard = $parent_dir . '/admin/super-admin/dashboard.php';
$branch_admin_dashboard = $parent_dir . '/admin/branch-admin/dashboard.php';

echo "Super admin dashboard path: " . $super_admin_dashboard . "<br>";
echo "File exists: " . (file_exists($super_admin_dashboard) ? "Yes" : "No") . "<br>";

echo "Branch admin dashboard path: " . $branch_admin_dashboard . "<br>";
echo "File exists: " . (file_exists($branch_admin_dashboard) ? "Yes" : "No") . "<br>";

// Check user role
echo "<h2>5. User Role Check</h2>";
if (isset($_SESSION['user_role'])) {
    echo "User role: " . $_SESSION['user_role'] . "<br>";
    
    // Generate correct redirect URLs
    $super_admin_url = $base_path . '/admin/super-admin/dashboard.php';
    $branch_admin_url = $base_path . '/admin/branch-admin/dashboard.php';
    
    echo "Super admin redirect URL: " . $super_admin_url . "<br>";
    echo "Branch admin redirect URL: " . $branch_admin_url . "<br>";
    
    // Provide direct links
    if ($_SESSION['user_role'] === 'super_admin') {
        echo "<p>You are a super admin. Click <a href='{$super_admin_url}'>here</a> to go to your dashboard.</p>";
    } else {
        echo "<p>You are a branch admin. Click <a href='{$branch_admin_url}'>here</a> to go to your dashboard.</p>";
    }
} else {
    echo "User role: Not set (not logged in)<br>";
    echo "<p>You need to <a href='{$base_path}/admin/login.php'>login</a> first.</p>";
}

// Final summary
echo "<h2>6. Summary</h2>";
echo "<p>The base path has been detected as: <strong>{$base_path}</strong></p>";
echo "<p>This has been stored in your session as \$_SESSION['base_path']</p>";
echo "<p>If you're still having issues, try these direct links:</p>";
echo "<ul>";
echo "<li><a href='{$base_path}/admin/login.php'>Admin Login</a></li>";
echo "<li><a href='{$base_path}/admin/dashboard.php'>Admin Dashboard (will redirect based on role)</a></li>";
echo "<li><a href='{$base_path}/admin/super-admin/dashboard.php'>Super Admin Dashboard (direct)</a></li>";
echo "<li><a href='{$base_path}/admin/branch-admin/dashboard.php'>Branch Admin Dashboard (direct)</a></li>";
echo "</ul>";

// Add some basic styling
echo "<style>
body {
    font-family: Arial, sans-serif;
    line-height: 1.6;
    margin: 20px;
    color: #333;
}
h1 {
    color: #ff6b45;
    border-bottom: 2px solid #ff6b45;
    padding-bottom: 10px;
}
h2 {
    color: #333;
    margin-top: 30px;
    border-left: 4px solid #ff6b45;
    padding-left: 10px;
}
a {
    color: #ff6b45;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style>";
?>

