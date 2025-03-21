<?php
// Start session
session_start();

// Include database connection
require_once '../includes/db_connect.php';

// Check if user is logged in as a customer
if (!isset($_SESSION['customer_id']) || !isset($_SESSION['customer_name'])) {
  // Redirect to login page if not logged in
  header("Location: login.php");
  exit();
}

// Get customer data
$customer_id = $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT c.first_name, c.last_name, c.email, c.phone, c.branch, c.fitness_goal, b.name as branch_name, b.location 
                      FROM customers c 
                      LEFT JOIN branches b ON c.branch = b.name 
                      WHERE c.id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  // Customer not found in database
  session_destroy();
  header("Location: login.php?error=invalid_account");
  exit();
}

$customer = $result->fetch_assoc();

// Verify branch matches if it was specified during login
if (isset($_SESSION['customer_branch']) && $customer['branch'] !== $_SESSION['customer_branch']) {
  // Branch mismatch, log out and redirect
  session_destroy();
  header("Location: login.php?error=branch_mismatch");
  exit();
}

// If branch name is not found in the join, use the branch value directly
$branch_name = $customer['branch_name'] ?? $customer['branch'];
$branch_location = $customer['location'] ?? 'Location not available';

// Get upcoming classes for this branch
$stmt = $conn->prepare("SELECT * FROM classes WHERE branch = ? AND class_date >= CURDATE() ORDER BY class_date, start_time LIMIT 5");
$stmt->bind_param("s", $branch_name);
$stmt->execute();
$upcoming_classes = $stmt->get_result();

// Check if classes table exists, if not create it for demo purposes
if ($upcoming_classes === false) {
    $sql = "CREATE TABLE IF NOT EXISTS classes (
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
    
    // Insert sample classes for demo
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
    
    // Retry getting upcoming classes
    $stmt = $conn->prepare("SELECT * FROM classes WHERE branch = ? AND class_date >= CURDATE() ORDER BY class_date, start_time LIMIT 5");
    $stmt->bind_param("s", $branch_name);
    $stmt->execute();
    $upcoming_classes = $stmt->get_result();
}

// Get membership info (create a memberships table if it doesn't exist)
$membership_status = "Active";
$membership_type = "Premium";
$membership_expiry = "2023-12-31";

// Check if memberships table exists, if not create it for demo purposes
$result = $conn->query("SHOW TABLES LIKE 'memberships'");
if ($result->num_rows == 0) {
    $sql = "CREATE TABLE IF NOT EXISTS memberships (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        membership_type VARCHAR(50) NOT NULL,
        status VARCHAR(20) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id)
    )";
    $conn->query($sql);
    
    // Insert sample membership for current customer
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+1 year'));
    
    $stmt = $conn->prepare("INSERT INTO memberships (customer_id, membership_type, status, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $customer_id, $membership_type, $membership_status, $start_date, $end_date);
    $stmt->execute();
    
    $membership_expiry = $end_date;
} else {
    // Get membership info for this customer
    $stmt = $conn->prepare("SELECT membership_type, status, end_date FROM memberships WHERE customer_id = ? ORDER BY end_date DESC LIMIT 1");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $membership = $result->fetch_assoc();
        $membership_type = $membership['membership_type'];
        $membership_status = $membership['status'];
        $membership_expiry = $membership['end_date'];
    } else {
        // Create a membership for this customer if none exists
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+1 year'));
        
        $stmt = $conn->prepare("INSERT INTO memberships (customer_id, membership_type, status, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $customer_id, $membership_type, $membership_status, $start_date, $end_date);
        $stmt->execute();
        
        $membership_expiry = $end_date;
    }
}

// Get base path from session or detect it
$script_name = $_SERVER['SCRIPT_NAME'];
$script_path = dirname($script_name);
$customer_pos = strpos($script_path, '/customer');
$base_path = $customer_pos !== false ? substr($script_path, 0, $customer_pos) : '';
$_SESSION['base_path'] = $base_path;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Gym Network</title>
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Additional styles for customer dashboard */
        .low-availability {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .book-btn {
            background-color: #FF6B45;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .book-btn:hover {
            background-color: #e55a35;
        }
        
        .fitness-progress {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .progress-chart {
            flex: 2;
            min-width: 300px;
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
        }
        
        .progress-chart h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .chart-placeholder {
            height: 200px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #777;
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
        }
        
        .start-btn {
            background-color: #FF6B45;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 15px;
            font-size: 14px;
        }
        
        .progress-stats {
            flex: 1;
            min-width: 250px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .stat-item {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background-color: #f0f8ff;
            color: #FF6B45;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-right: 15px;
        }
        
        .stat-info h4 {
            font-size: 18px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            color: #777;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .progress-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        
        <div class="sidebar">
            <div class="logo">
                <h2>Gym Network</h2>
            </div>
            <div class="menu">
                <ul>
                    <li class="active"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="#"><i class="fas fa-calendar-alt"></i> Classes</a></li>
                    <li><a href="#"><i class="fas fa-dumbbell"></i> Workouts</a></li>
                    <li><a href="#"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="#"><i class="fas fa-credit-card"></i> Membership</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="page-title">
                    <h1>Customer Dashboard</h1>
                </div>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['customer_name']); ?></span>
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <div class="welcome-content">
                        <h2>Welcome to <?php echo htmlspecialchars($branch_name); ?></h2>
                        <p>Thank you for being a valued member of our fitness community at <?php echo htmlspecialchars($branch_name); ?> located at <?php echo htmlspecialchars($branch_location); ?>.</p>
                        <p>Your fitness goal: <strong><?php echo ucfirst(str_replace('_', ' ', $customer['fitness_goal'] ?? 'Not specified')); ?></strong></p>
                        <button class="action-button">View Branch Details</button>
                    </div>
                    <div class="welcome-image">
                        <img src="<?php echo $base_path; ?>/assets/images/customer-dashboard.png" alt="Welcome">
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-cards">
                    <div class="card">
                        <div class="card-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="card-info">
                            <h3><?php echo $membership_status; ?></h3>
                            <p>Membership Status</p>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="card-info">
                            <h3><?php echo $membership_type; ?></h3>
                            <p>Membership Type</p>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="card-info">
                            <h3><?php echo date('M d, Y', strtotime($membership_expiry)); ?></h3>
                            <p>Membership Expiry</p>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="card-info">
                            <h3>0</h3>
                            <p>Workouts This Month</p>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h2>Quick Actions</h2>
                    <div class="action-buttons">
                        <button class="action-btn">
                            <i class="fas fa-calendar-plus"></i>
                            <span>Book a Class</span>
                        </button>
                        <button class="action-btn">
                            <i class="fas fa-dumbbell"></i>
                            <span>Start Workout</span>
                        </button>
                        <button class="action-btn">
                            <i class="fas fa-user-edit"></i>
                            <span>Update Profile</span>
                        </button>
                        <button class="action-btn">
                            <i class="fas fa-credit-card"></i>
                            <span>Renew Membership</span>
                        </button>
                    </div>
                </div>
                
                <!-- Upcoming Classes Section -->
                <div class="section">
                    <div class="section-header">
                        <h2>Upcoming Classes at <?php echo htmlspecialchars($branch_name); ?></h2>
                        <button class="add-btn">
                            <i class="fas fa-calendar-alt"></i> View All Classes
                        </button>
                    </div>
                    
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Instructor</th>
                                    <th>Availability</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($upcoming_classes && $upcoming_classes->num_rows > 0): ?>
                                    <?php while ($class = $upcoming_classes->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($class['class_date'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($class['start_time'])) . ' - ' . date('h:i A', strtotime($class['end_time'])); ?></td>
                                            <td><?php echo htmlspecialchars($class['instructor']); ?></td>
                                            <td>
                                                <?php 
                                                $availability = $class['max_capacity'] - $class['current_capacity'];
                                                $availability_class = $availability <= 3 ? 'low-availability' : '';
                                                echo "<span class='$availability_class'>$availability spots left</span>"; 
                                                ?>
                                            </td>
                                            <td>
                                                <button class="book-btn">Book Now</button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="no-data">No upcoming classes found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Fitness Progress Section -->
                <div class="section">
                    <div class="section-header">
                        <h2>Your Fitness Progress</h2>
                    </div>
                    
                    <div class="fitness-progress">
                        <div class="progress-chart">
                            <h3>Monthly Activity</h3>
                            <div class="chart-placeholder">
                                <p>Your activity chart will appear here once you start logging workouts.</p>
                                <button class="start-btn">Start Tracking</button>
                            </div>
                        </div>
                        
                        <div class="progress-stats">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-running"></i>
                                </div>
                                <div class="stat-info">
                                    <h4>0</h4>
                                    <p>Workouts</p>
                                </div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-fire-alt"></i>
                                </div>
                                <div class="stat-info">
                                    <h4>0</h4>
                                    <p>Calories Burned</p>
                                </div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-info">
                                    <h4>0h</h4>
                                    <p>Total Time</p>
                                </div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-dumbbell"></i>
                                </div>
                                <div class="stat-info">
                                    <h4>0</h4>
                                    <p>Strength Training</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

