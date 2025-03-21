<?php
// Include database connection
require_once '../includes/db_connect.php';

// Create a new hash for "admin123" using PHP's password_hash function
$password = 'admin123';
$new_hash = password_hash($password, PASSWORD_DEFAULT);

// Test the hash to make sure it works
$verify_test = password_verify($password, $new_hash);

echo "<h2>Password Hash Test</h2>";
echo "<p>New Hash: " . $new_hash . "</p>";
echo "<p>Verification Test: " . ($verify_test ? "SUCCESS" : "FAILED") . "</p>";

if ($verify_test) {
    // Update all admin passwords with the new hash
    $stmt = $conn->prepare("UPDATE admins SET password = ?");
    $stmt->bind_param("s", $new_hash);
    $result = $stmt->execute();
    
    if ($result) {
        echo "<h2>Admin passwords have been fixed successfully!</h2>";
        echo "<p>You can now login with the following credentials:</p>";
        echo "<h3>Super Admin:</h3>";
        echo "<p>Email: admin@gymnetwork.com<br>Password: admin123</p>";
        echo "<h3>Branch Admin:</h3>";
        echo "<p>Email: downtown@gymnetwork.com<br>Password: admin123</p>";
        echo "<p>Email: uptown@gymnetwork.com<br>Password: admin123</p>";
    } else {
        echo "<h2>Error updating passwords: " . $conn->error . "</h2>";
    }
    
    // Check if admins exist, if not create them
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admins");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // No admins exist, create them
        $super_admin_name = 'Super Admin';
        $super_admin_email = 'admin@gymnetwork.com';
        $super_admin_role = 'super_admin';
        
        $branch_admin1_name = 'Downtown Manager';
        $branch_admin1_email = 'downtown@gymnetwork.com';
        $branch_admin1_role = 'branch_admin';
        
        $branch_admin2_name = 'Uptown Manager';
        $branch_admin2_email = 'uptown@gymnetwork.com';
        $branch_admin2_role = 'branch_admin';
        
        // Insert super admin
        $stmt = $conn->prepare("INSERT INTO admins (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $super_admin_name, $super_admin_email, $new_hash, $super_admin_role);
        $stmt->execute();
        
        // Insert branch admin 1
        $stmt = $conn->prepare("INSERT INTO admins (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $branch_admin1_name, $branch_admin1_email, $new_hash, $branch_admin1_role);
        $stmt->execute();
        $branch_admin1_id = $conn->insert_id;
        
        // Insert branch admin 2
        $stmt = $conn->prepare("INSERT INTO admins (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $branch_admin2_name, $branch_admin2_email, $new_hash, $branch_admin2_role);
        $stmt->execute();
        $branch_admin2_id = $conn->insert_id;
        
        // Create branches and assign admins
        $branch1_name = 'Downtown Fitness';
        $branch1_location = '123 Main St, Downtown';
        
        $branch2_name = 'Uptown Gym';
        $branch2_location = '456 High St, Uptown';
        
        // Insert branch 1
        $stmt = $conn->prepare("INSERT INTO branches (name, location, admin_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $branch1_name, $branch1_location, $branch_admin1_id);
        $stmt->execute();
        
        // Insert branch 2
        $stmt = $conn->prepare("INSERT INTO branches (name, location, admin_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $branch2_name, $branch2_location, $branch_admin2_id);
        $stmt->execute();
        
        echo "<h3>Admin accounts created successfully!</h3>";
    }
} else {
    echo "<h2>ERROR: Password hash verification test failed. Please contact support.</h2>";
}

echo "<p><a href='login.php'>Go to Login Page</a></p>";
?>

