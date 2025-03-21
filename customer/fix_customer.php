<?php
// Enable error reporting for troubleshooting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once '../includes/db_connect.php';

echo "<h1>Customer Account Troubleshooting</h1>";

// Create a new hash for "customer123" using PHP's password_hash function
$password = 'customer123';
$new_hash = password_hash($password, PASSWORD_DEFAULT);

// Test the hash to make sure it works
$verify_test = password_verify($password, $new_hash);

echo "<h2>Password Hash Test</h2>";
echo "<p>New Hash: " . $new_hash . "</p>";
echo "<p>Verification Test: " . ($verify_test ? "<span style='color:green'>SUCCESS</span>" : "<span style='color:red'>FAILED</span>") . "</p>";

if ($verify_test) {
    // Check if customers table exists
    $result = $conn->query("SHOW TABLES LIKE 'customers'");
    if ($result->num_rows == 0) {
        // Create customers table
        $sql = "CREATE TABLE customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            phone VARCHAR(20) NOT NULL,
            password VARCHAR(255) NOT NULL,
            branch VARCHAR(50) NOT NULL,
            fitness_goal VARCHAR(50),
            remember_token VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->query($sql);
        echo "<p>Customers table created: <span style='color:green'>SUCCESS</span></p>";
    } else {
        echo "<p>Customers table exists: <span style='color:green'>SUCCESS</span></p>";
    }
    
    // Check if branches table exists
    $result = $conn->query("SHOW TABLES LIKE 'branches'");
    if ($result->num_rows == 0) {
        // Create branches table
        $sql = "CREATE TABLE branches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            location VARCHAR(255) NOT NULL,
            admin_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->query($sql);
        
        // Insert sample branches
        $sample_branches = [
            ['Downtown Fitness', '123 Main St, Downtown'],
            ['Uptown Gym', '456 High St, Uptown'],
            ['Westside Health Club', '789 West Blvd, Westside'],
            ['East End Fitness Center', '321 East Ave, East End']
        ];
        
        $stmt = $conn->prepare("INSERT INTO branches (name, location) VALUES (?, ?)");
        
        foreach ($sample_branches as $branch) {
            $stmt->bind_param("ss", $branch[0], $branch[1]);
            $stmt->execute();
        }
        
        echo "<p>Branches table created with sample data: <span style='color:green'>SUCCESS</span></p>";
    } else {
        echo "<p>Branches table exists: <span style='color:green'>SUCCESS</span></p>";
    }
    
    // Check if classes table exists
    $result = $conn->query("SHOW TABLES LIKE 'classes'");
    if ($result->num_rows == 0) {
        // Create classes table
        $sql = "CREATE TABLE classes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            branch VARCHAR(100) NOT NULL,
            class_name VARCHAR(100) NOT NULL,
            class_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            instructor VARCHAR(100) NOT NULL,
            max_capacity INT NOT NULL,
            current_capacity INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->query($sql);
        
        // Insert sample classes
        $sample_classes = [
            ['Downtown Fitness', 'Yoga Basics', '2023-06-15', '09:00:00', '10:00:00', 'John Smith', 20, 8],
            ['Downtown Fitness', 'HIIT Workout', '2023-06-16', '18:00:00', '19:00:00', 'Sarah Johnson', 15, 12],
            ['Downtown Fitness', 'Spin Class', '2023-06-17', '10:00:00', '11:00:00', 'Mike Brown', 12, 5],
            ['Uptown Gym', 'Pilates', '2023-06-15', '17:00:00', '18:00:00', 'Lisa Davis', 15, 7],
            ['Uptown Gym', 'Zumba', '2023-06-16', '19:00:00', '20:00:00', 'Carlos Rodriguez', 25, 18]
        ];
        
        $stmt = $conn->prepare("INSERT INTO classes (branch, class_name, class_date, start_time, end_time, instructor, max_capacity, current_capacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($sample_classes as $class) {
            $stmt->bind_param("ssssssii", $class[0], $class[1], $class[2], $class[3], $class[4], $class[5], $class[6], $class[7]);
            $stmt->execute();
        }
        
        echo "<p>Classes table created with sample data: <span style='color:green'>SUCCESS</span></p>";
    } else {
        echo "<p>Classes table exists: <span style='color:green'>SUCCESS</span></p>";
    }
    
    // Check if memberships table exists
    $result = $conn->query("SHOW TABLES LIKE 'memberships'");
    if ($result->num_rows == 0) {
        // Create memberships table
        $sql = "CREATE TABLE memberships (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            membership_type VARCHAR(50) NOT NULL,
            status VARCHAR(20) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->query($sql);
        echo "<p>Memberships table created: <span style='color:green'>SUCCESS</span></p>";
    } else {
        echo "<p>Memberships table exists: <span style='color:green'>SUCCESS</span></p>";
    }
    
    // Check if sample customers exist
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM customers");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // Create sample customers
        $sample_customers = [
            ['John', 'Doe', 'john@example.com', '1234567890', 'Downtown Fitness', 'weight_loss'],
            ['Jane', 'Smith', 'jane@example.com', '0987654321', 'Uptown Gym', 'muscle_gain'],
            ['Mike', 'Johnson', 'mike@example.com', '5551234567', 'Westside Health Club', 'endurance']
        ];
        
        $stmt = $conn->prepare("INSERT INTO customers (first_name, last_name, email, phone, password, branch, fitness_goal) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($sample_customers as $customer) {
            $stmt->bind_param("sssssss", $customer[0], $customer[1], $customer[2], $customer[3], $new_hash, $customer[4], $customer[5]);
            $stmt->execute();
            
            // Create membership for this customer
            $customer_id = $conn->insert_id;
            $membership_type = "Premium";
            $status = "Active";
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime('+1 year'));
            
            $stmt2 = $conn->prepare("INSERT INTO memberships (customer_id, membership_type, status, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("issss", $customer_id, $membership_type, $status, $start_date, $end_date);
            $stmt2->execute();
        }
        
        echo "<h3>Sample customers created successfully!</h3>";
        echo "<p>You can now login with the following credentials:</p>";
        echo "<ul>";
        foreach ($sample_customers as $customer) {
            echo "<li>Email: " . $customer[2] . "<br>Password: " . $password . "<br>Branch: " . $customer[4] . "</li>";
        }
        echo "</ul>";
    } else {
        // Update existing customer passwords
        $stmt = $conn->prepare("UPDATE customers SET password = ?");
        $stmt->bind_param("s", $new_hash);
        $result = $stmt->execute();
        
        if ($result) {
            echo "<h3>All customer passwords have been updated to: " . $password . "</h3>";
            
            // List all customers
            $stmt = $conn->prepare("SELECT id, email, branch FROM customers");
            $stmt->execute();
            $result = $stmt->get_result();
            
            echo "<p>You can now login with any of these accounts:</p>";
            echo "<ul>";
            while ($customer = $result->fetch_assoc()) {
                echo "<li>Email: " . $customer['email'] . "<br>Password: " . $password . "<br>Branch: " . $customer['branch'] . "</li>";
                
                // Check if this customer has a membership
                $stmt2 = $conn->prepare("SELECT COUNT(*) as count FROM memberships WHERE customer_id = ?");
                $stmt2->bind_param("i", $customer['id']);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                $row2 = $result2->fetch_assoc();
                
                if ($row2['count'] == 0) {
                    // Create membership for this customer
                    $membership_type = "Premium";
                    $status = "Active";
                    $start_date = date('Y-m-d');
                    $end_date = date('Y-m-d', strtotime('+1 year'));
                    
                    $stmt3 = $conn->prepare("INSERT INTO memberships (customer_id, membership_type, status, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
                    $stmt3->bind_param("issss", $customer['id'], $membership_type, $status, $start_date, $end_date);
                    $stmt3->execute();
                }
            }
            echo "</ul>";
        } else {
            echo "<h3>Error updating customer passwords: " . $conn->error . "</h3>";
        }
    }
} else {
    echo "<h2>ERROR: Password hash verification test failed. Please contact support.</h2>";
}

// Get base path
$script_name = $_SERVER['SCRIPT_NAME'];
$script_path = dirname($script_name);
$customer_pos = strpos($script_path, '/customer');
$base_path = $customer_pos !== false ? substr($script_path, 0, $customer_pos) : '';

echo "<p><a href='{$base_path}/customer/login.php' style='display: inline-block; background-color: #FF6B45; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>Go to Customer Login Page</a></p>";

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
ul {
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
}
li {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}
li:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}
</style>";
?>

