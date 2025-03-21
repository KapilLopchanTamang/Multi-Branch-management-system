<?php
// Start session
session_start();

// Include database connection
require_once '../../includes/db_connect.php';

// Check if user is logged in and is a branch admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'branch_admin') {
    // Redirect to login page if not logged in or not a branch admin
    header("Location: ../login.php");
    exit();
}

// Get admin data
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT a.name, a.email, b.name as branch_name, b.location 
                        FROM admins a 
                        LEFT JOIN branches b ON a.id = b.admin_id 
                        WHERE a.id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Get customer count for this branch
$branch_name = $admin['branch_name'];
$stmt = $conn->prepare("SELECT COUNT(*) as customer_count FROM customers WHERE branch = ?");
$stmt->bind_param("s", $branch_name);
$stmt->execute();
$result = $stmt->get_result();
$customer_data = $result->fetch_assoc();
$customer_count = $customer_data['customer_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Admin Dashboard - Gym Network</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h2>Gym Network</h2>
            </div>
            <div class="menu">
                <ul>
                    <li class="active"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="customers.php"><i class="fas fa-user-friends"></i> Customers</a></li>
                    <li><a href="attendance.php"><i class="fas fa-clipboard-check"></i> Attendance</a></li>
                    <li><a href="#"><i class="fas fa-calendar-alt"></i> Classes</a></li>
                    <li><a href="#"><i class="fas fa-dumbbell"></i> Equipment</a></li>
                    <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="<?php echo isset($_SESSION['base_path']) ? $_SESSION['base_path'] : ''; ?>/admin/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="page-title">
                    <h1>Branch Admin Dashboard</h1>
                </div>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($admin['name']); ?></span>
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
                        <h2>Welcome to <?php echo htmlspecialchars($admin['branch_name']); ?> Dashboard</h2>
                        <p>You are managing the <?php echo htmlspecialchars($admin['branch_name']); ?> located at <?php echo htmlspecialchars($admin['location']); ?>.</p>
                        <p>Here you can manage your branch customers, classes, equipment, and more.</p>
                        <button class="action-button">View Branch Details</button>
                    </div>
                    <div class="welcome-image">
                        <img src="../../assets/images/branch-admin-welcome.png" alt="Welcome">
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-cards">
                    <div class="card">
                        <div class="card-icon">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <div class="card-info">
                            <h3><?php echo $customer_count; ?></h3>
                            <p>Total Customers</p>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="card-info">
                            <?php
                            // Get active memberships count
                            $stmt = $conn->prepare("SELECT COUNT(*) as active_count FROM memberships m 
                                  JOIN customers c ON m.customer_id = c.id 
                                  WHERE c.branch = ? AND m.status = 'Active' AND m.end_date >= CURDATE()");
                            $stmt->bind_param("s", $branch_name);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $active_data = $result->fetch_assoc();
                            $active_count = $active_data['active_count'] ?? 0;
                            ?>
                            <h3><?php echo $active_count; ?></h3>
                            <p>Active Memberships</p>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="card-info">
                            <?php
                            // Get today's attendance count
                            $stmt = $conn->prepare("SELECT COUNT(*) as today_count FROM attendance 
                                  WHERE branch = ? AND DATE(check_in) = CURDATE()");
                            $stmt->bind_param("s", $branch_name);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $attendance_data = $result->fetch_assoc();
                            $today_attendance = $attendance_data['today_count'] ?? 0;
                            ?>
                            <h3><?php echo $today_attendance; ?></h3>
                            <p>Today's Check-ins</p>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="card-info">
                            <?php
                            // Get expiring memberships count (next 30 days)
                            $stmt = $conn->prepare("SELECT COUNT(*) as expiring_count FROM memberships m 
                                  JOIN customers c ON m.customer_id = c.id 
                                  WHERE c.branch = ? AND m.status = 'Active' 
                                  AND m.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
                            $stmt->bind_param("s", $branch_name);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $expiring_data = $result->fetch_assoc();
                            $expiring_count = $expiring_data['expiring_count'] ?? 0;
                            ?>
                            <h3><?php echo $expiring_count; ?></h3>
                            <p>Expiring Memberships</p>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h2>Quick Actions</h2>
                    <div class="action-buttons">
                        <a href="customers.php" class="action-btn">
                            <i class="fas fa-user-friends"></i>
                            <span>Manage Customers</span>
                        </a>
                        <a href="#" onclick="goToAddCustomer(); return false;" class="action-btn">
                            <i class="fas fa-user-plus"></i>
                            <span>Add Customer</span>
                        </a>
                        <a href="attendance.php" class="action-btn">
                            <i class="fas fa-clipboard-check"></i>
                            <span>Attendance</span>
                        </a>
                        <a href="attendance_report.php" class="action-btn">
                            <i class="fas fa-chart-bar"></i>
                            <span>Attendance Reports</span>
                        </a>
                    </div>
                </div>
                
                <!-- QR Code Scanner Card -->
                <div class="section" style="margin-bottom: 30px;">
                    <div class="section-header">
                        <h2><i class="fas fa-qrcode"></i> Quick Check-In</h2>
                        <a href="attendance.php" class="add-btn">
                            <i class="fas fa-clipboard-check"></i> Full Attendance
                        </a>
                    </div>
                    
                    <div style="background-color: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); text-align: center;">
                        <p style="margin-bottom: 15px;">Scan customer QR codes for instant check-in</p>
                        <a href="attendance.php" class="action-button" style="display: inline-block;">
                            <i class="fas fa-camera"></i> Open Scanner
                        </a>
                    </div>
                </div>
                
                <!-- Customer Overview section -->
                <div class="section">
                    <div class="section-header">
                        <h2>Customer Overview</h2>
                        <a href="customers.php" class="add-btn">
                            <i class="fas fa-eye"></i> View All Customers
                        </a>
                    </div>
                    
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Subscription</th>
                                    <th>Expiry Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Get recent customers
                                $stmt = $conn->prepare("SELECT c.id, c.first_name, c.last_name, c.email, c.subscription_type, 
                                                      m.end_date FROM customers c 
                                                      LEFT JOIN (SELECT customer_id, MAX(end_date) as end_date 
                                                                FROM memberships GROUP BY customer_id) m 
                                                      ON c.id = m.customer_id 
                                                      WHERE c.branch = ? 
                                                      ORDER BY c.created_at DESC LIMIT 5");
                                $stmt->bind_param("s", $branch_name);
                                $stmt->execute();
                                $recent_customers = $stmt->get_result();
                                
                                if ($recent_customers->num_rows > 0):
                                    while ($customer = $recent_customers->fetch_assoc()):
                                        $subscription_types = [
                                            'monthly' => 'Monthly',
                                            'six_months' => '6 Months',
                                            'yearly' => 'Yearly'
                                        ];
                                        $subscription_text = $subscription_types[$customer['subscription_type'] ?? 'monthly'];
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                        <td><?php echo $subscription_text; ?></td>
                                        <td><?php echo $customer['end_date'] ? date('M d, Y', strtotime($customer['end_date'])) : 'N/A'; ?></td>
                                        <td>
                                            <a href="customer_details.php?id=<?php echo $customer['id']; ?>" class="action-btn view">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="#" onclick="goToEditCustomer(<?php echo $customer['id']; ?>); return false;" class="action-btn edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="5" class="no-data">No customers found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Recent Activity Section -->
                <div class="section">
                    <div class="section-header">
                        <h2>Recent Activity</h2>
                    </div>
                    
                    <div class="activity-list">
                        <?php
                        // Get recent customer activities
                        $stmt = $conn->prepare("SELECT c.first_name, c.last_name, c.created_at, 'new_registration' as activity_type 
                                              FROM customers c 
                                              WHERE c.branch = ? 
                                              UNION ALL 
                                              SELECT c.first_name, c.last_name, m.created_at, 'new_membership' as activity_type 
                                              FROM memberships m 
                                              JOIN customers c ON m.customer_id = c.id 
                                              WHERE c.branch = ? 
                                              ORDER BY created_at DESC LIMIT 4");
                        $stmt->bind_param("ss", $branch_name, $branch_name);
                        $stmt->execute();
                        $activities = $stmt->get_result();
                        
                        if ($activities->num_rows > 0):
                            while ($activity = $activities->fetch_assoc()):
                                $icon_class = $activity['activity_type'] == 'new_registration' ? 'fas fa-user-plus' : 'fas fa-calendar-check';
                                $activity_text = $activity['activity_type'] == 'new_registration' 
                                    ? "New customer registration: " . htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name'])
                                    : "Membership renewal: " . htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']);
                        ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="<?php echo $icon_class; ?>"></i>
                                </div>
                                <div class="activity-details">
                                    <p class="activity-text"><?php echo $activity_text; ?></p>
                                    <p class="activity-time"><?php echo date('F j, Y, g:i a', strtotime($activity['created_at'])); ?></p>
                                </div>
                            </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="activity-details">
                                    <p class="activity-text">No recent activity</p>
                                    <p class="activity-time">Start adding customers to see activity here</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Recent Attendance Section -->
                <div class="section">
                    <div class="section-header">
                        <h2>Today's Attendance</h2>
                        <a href="attendance.php" class="add-btn">
                            <i class="fas fa-clipboard-check"></i> Manage Attendance
                        </a>
                    </div>
                    
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Check-In Time</th>
                                    <th>Check-Out Time</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Get today's attendance records
                                $stmt = $conn->prepare("SELECT a.id, a.customer_id, a.check_in, a.check_out, 
                                                      c.first_name, c.last_name 
                                                      FROM attendance a 
                                                      JOIN customers c ON a.customer_id = c.id 
                                                      WHERE a.branch = ? AND DATE(a.check_in) = CURDATE() 
                                                      ORDER BY a.check_in DESC LIMIT 5");
                                $stmt->bind_param("s", $branch_name);
                                $stmt->execute();
                                $today_attendance = $stmt->get_result();
                                
                                if ($today_attendance->num_rows > 0):
                                    while ($record = $today_attendance->fetch_assoc()):
                                        $check_in_time = new DateTime($record['check_in']);
                                        $check_out_time = $record['check_out'] ? new DateTime($record['check_out']) : null;
                                        
                                        // Calculate duration
                                        if ($check_out_time) {
                                            $duration = $check_in_time->diff($check_out_time);
                                            $duration_text = sprintf('%02d:%02d', $duration->h, $duration->i);
                                        } else {
                                            $now = new DateTime();
                                            $duration = $check_in_time->diff($now);
                                            $duration_text = sprintf('%02d:%02d (ongoing)', $duration->h, $duration->i);
                                        }
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                        <td><?php echo $check_in_time->format('h:i A'); ?></td>
                                        <td><?php echo $check_out_time ? $check_out_time->format('h:i A') : '-'; ?></td>
                                        <td><?php echo $duration_text; ?></td>
                                        <td>
                                            <?php if ($check_out_time): ?>
                                                <span style="background-color: #e3f2fd; color: #1976d2; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">Checked Out</span>
                                            <?php else: ?>
                                                <span style="background-color: #e8f5e9; color: #388e3c; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">Checked In</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="5" class="no-data">No attendance records for today</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Add this script at the end of the file, before the closing </body> tag -->
    <script>
        // Function to redirect to customers page and show add customer modal
        function goToAddCustomer() {
            window.location.href = 'customers.php?action=add';
        }
        
        // Function to redirect to customers page and show edit customer modal
        function goToEditCustomer(id) {
            window.location.href = 'customers.php?action=edit&id=' + id;
        }
        
        // Check for URL parameters on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Get URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const action = urlParams.get('action');
            const id = urlParams.get('id');
            
            // If action is 'add', show add customer modal
            if (action === 'add') {
                // Redirect to customers page with action parameter
                window.location.href = 'customers.php?action=add';
            }
            
            // If action is 'edit' and id is provided, show edit customer modal
            if (action === 'edit' && id) {
                // Redirect to customers page with action and id parameters
                window.location.href = 'customers.php?action=edit&id=' + id;
            }
        });
    </script>
</body>
</html>

