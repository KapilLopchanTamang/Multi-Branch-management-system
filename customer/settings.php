<?php
// Start session
include 'includes/pageeffect.php';


// Include database connection
require_once '../includes/db_connect.php';

// Check if user is logged in as a customer
if (!isset($_SESSION['customer_id'])) {
    header("Location: login");
    exit();
}

// Get customer data
$customer_id = $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT c.*, c.profile_image, m.membership_type, m.status as membership_status, m.end_date, b.name as branch_name, b.location 
                        FROM customers c 
                        LEFT JOIN memberships m ON c.id = m.customer_id 
                        LEFT JOIN branches b ON c.branch = b.name 
                        WHERE c.id = ? 
                        ORDER BY m.end_date DESC LIMIT 1");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();

// Calculate days remaining until membership expiry
$days_remaining = 0;
$is_expired = false;
if (!empty($customer['end_date'])) {
    $today = new DateTime();
    $expiry_date = new DateTime($customer['end_date']);
    $interval = $today->diff($expiry_date);
    $days_remaining = $interval->days;
    $is_expired = $today > $expiry_date;
}

// Calculate loyalty points (example calculation)
$loyalty_points = 0;
if (isset($customer['join_date'])) {
    $join_date = new DateTime($customer['join_date']);
    $today = new DateTime();
    $months = $join_date->diff($today)->m + ($join_date->diff($today)->y * 12);
    $loyalty_points = $months * 500; // 500 points per month of membership
    
    // Add points for check-ins (assuming there's a check_ins table)
    $stmt = $conn->prepare("SELECT COUNT(*) as check_in_count FROM check_ins WHERE customer_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $check_ins_result = $stmt->get_result();
        $check_ins = $check_ins_result->fetch_assoc();
        if ($check_ins) {
            $loyalty_points += $check_ins['check_in_count'] * 10; // 10 points per check-in
        }
    }
}

// Format phone number for display
$phone_display = '';
if (!empty($customer['phone'])) {
    // Format as XXX XXX XXX
    $phone = preg_replace('/[^0-9]/', '', $customer['phone']);
    if (strlen($phone) >= 9) {
        $phone_display = substr($phone, 0, 3) . ' ' . substr($phone, 3, 3) . ' ' . substr($phone, 6, 3);
    } else {
        $phone_display = $customer['phone'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | Gym Network</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .text-primary { color: #e74c3c; font-weight: bold; }
        .bg-primary { background-color: #FF6B45; }
        .bg-light { background-color: #f9f9f9; }
        
        /* Gradient background */
        .bg-gradient-pattern {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
        }
        
        /* Profile circle background */
        .profile-circle {
            background-color: #00D2C3;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        /* Settings item styles */
        .settings-item {
            display: flex;
            align-items: center;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 8px;
            background-color: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }
        
        .settings-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .settings-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
        }
        
        /* Icon background colors - using the same color as dashboard */
        .icon-profile { background-color: rgba(255, 107, 69, 0.15); color: #FF6B45; }
        .icon-classes { background-color: rgba(255, 107, 69, 0.15); color: #FF6B45; }
        .icon-workout { background-color: rgba(255, 107, 69, 0.15); color: #FF6B45; }
        .icon-support { background-color: rgba(255, 107, 69, 0.15); color: #FF6B45; }
        .icon-password { background-color: rgba(255, 107, 69, 0.15); color: #FF6B45; }
        
        /* Points badge */
        .points-badge {
            background-color: white;
            border-radius: 20px;
            padding: 8px 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Days remaining badge */
        .days-badge {
            background-color: white;
            border-radius: 20px;
            padding: 4px 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: inline-flex;
            align-items: center;
            gap: 4px;
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 12px;
        }
        
        /* Header background */
        #head {
            background-image: repeating-radial-gradient(circle at 0 0, transparent 0, #ff6b45 100px), repeating-linear-gradient(#e74c3c, #ff6b45);
            background-color: #ff6b45;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #ccc; }
    </style>
</head>
<body class="bg-gradient-pattern min-h-screen">
    <!-- Include sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div id="content-wrapper" class="min-h-screen transition-all duration-300">
  
        <!-- Settings Content -->
        <main class="p-4 lg:p-6">
            <div class="max-w-md mx-auto">
                <!-- Profile Section -->
                <div id="head" class="rounded-2xl p-6 mb-6 shadow-sm text-center relative">
                    <!-- Days remaining badge -->
                    
                    
                    <!-- Top action buttons -->
                    <div class="flex justify-between absolute top-4 left-4 right-4">
                        <div class="w-10 h-10 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-white">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-white">
  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
</svg>

                        </div>
                    </div>
                    
                    <!-- Profile Image -->
                    <div class="mt-8 mb-4 flex justify-center">
                        <div class="profile-circle">
                            <?php if (!empty($customer['profile_image'])): ?>
                                <img src="<?php echo '../' . htmlspecialchars($customer['profile_image']); ?>" alt="Profile" class="h-full w-full object-cover">
                            <?php else: ?>
                                <div class="text-white text-5xl font-bold">
                                    <?php echo strtoupper(substr($customer['first_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Name -->
                    <h2 class="text-2xl font-bold mb-2 text-white">
                        <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                    </h2>
                    
                    <!-- Phone Number -->
                    <?php if (!empty($phone_display)): ?>
                    <div class="flex items-center justify-center text-white text-opacity-90 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                        </svg>
                        <span><?php echo $phone_display; ?></span>
                    </div>
                    <?php endif; ?>
                    
                
                </div>
                
                <!-- Settings Menu -->
                <div class="bg-white rounded-2xl p-4 shadow-sm">
                    <!-- Profile Settings -->
                    <a href="profile" class="settings-item">
                        <div class="settings-icon icon-profile">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-medium">Profile</h3>
                            <p class="text-sm text-gray-500">Update your personal information</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                    
                    <!-- Classes -->
                    <a href="classes" class="settings-item">
                        <div class="settings-icon icon-classes">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-medium">Classes</h3>
                            <p class="text-sm text-gray-500">View and book fitness classes</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                    
                    <!-- Workout -->
                    <a href="workouts" class="settings-item">
                        <div class="settings-icon icon-workout">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-medium">Workout</h3>
                            <p class="text-sm text-gray-500">Track your fitness progress</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                    
                    <!-- Support -->
                    <a href="report" class="settings-item">
                        <div class="settings-icon icon-support">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
</svg>

                        </div>
                        <div class="flex-1">
                            <h3 class="font-medium">Report</h3>
                            <p class="text-sm text-gray-500">Get weekly report</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                    
                    <!-- Password Setting -->
                    <a href="changepassword" class="settings-item">
                        <div class="settings-icon icon-password">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-medium">Password Setting</h3>
                            <p class="text-sm text-gray-500">Change your password</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                    
                    <!-- Membership Card -->
                    <a href="card" class="settings-item">
                        <div class="settings-icon icon-profile">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-medium">Membership Card</h3>
                            <p class="text-sm text-gray-500">View and download your digital card</p>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                    
                    <!-- Logout Button -->
                
                </div>
                
            
            </div>
        </main>
    </div>
    
    <script>
        // Add any JavaScript functionality here
        document.addEventListener('DOMContentLoaded', function() {
            // Example: Add active state to current menu item
            const currentPath = window.location.pathname;
            const menuItems = document.querySelectorAll('.settings-item');
            
            menuItems.forEach(item => {
                const href = item.getAttribute('href');
                if (href && currentPath.includes(href)) {
                    item.classList.add('bg-gray-50');
                }
            });
        });
    </script>
</body>
</html>