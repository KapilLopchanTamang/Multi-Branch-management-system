<?php
// Start session
session_start();

// Include database connection
require_once '../includes/db_connect.php';

// Check if user is logged in as a customer
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
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



// Get upcoming classes for this branch
$stmt = $conn->prepare("SELECT * FROM classes WHERE branch = ? AND class_date >= CURDATE() ORDER BY class_date, start_time LIMIT 5");
$stmt->bind_param("s", $customer['branch_name']);
$stmt->execute();
$upcoming_classes = $stmt->get_result();

// Get attendance data for chart
$stmt = $conn->prepare("SELECT DATE(check_in) as date, COUNT(*) as count FROM attendance 
                        WHERE customer_id = ? AND check_in >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                        GROUP BY DATE(check_in)");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$attendance_data = $stmt->get_result();

// Format attendance data for chart
$attendance_dates = [];
$attendance_counts = [];
while ($row = $attendance_data->fetch_assoc()) {
    $attendance_dates[] = date('M d', strtotime($row['date']));
    $attendance_counts[] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Gym Network</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        #head{
            background-image:  repeating-radial-gradient(circle at 0 0, transparent 0, #ff6b45 100px), repeating-linear-gradient(#e74c3c, #ff6b45);
            background-color: #ff6b45;
        }
        .text-primary { color: #e74c3c; font-weight: bold; }
        .bg-primary { background-color: #FF6B45; }
        .bg-light { background-color: #f9f9f9; }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #ccc; }
        
        /* Transition for sidebar */
        #content-wrapper { transition: margin-left 0.3s ease; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Include sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    
    <!-- Main Content -->
    <div id="content-wrapper" class="ml-0 lg:ml-64 min-h-screen transition-all duration-300">
        <!-- Top Navigation -->
        <header  class=" shadow-sm">

            <div class="flex justify-between items-center px-4 py-3 lg:px-6">
                <h1 class="text-xl font-semibold text-gray-800">Dashboard</h1>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button class="p-1 text-gray-500 hover:text-gray-700 focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                        </button>
                        <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-primary"></span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-sm text-gray-700 mr-2 hidden sm:inline-block">
                            <?php echo htmlspecialchars(string: $customer['first_name'] . ' ' . $customer['last_name']); ?>
                        </span>
                        <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600">
                            <?php echo strtoupper(substr($customer['first_name'], 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Dashboard Content -->
        <main class="p-4 lg:p-6">
         <!-- Welcome Banner -->
<div id="head" class=" rounded-2xl shadow-sm overflow-hidden mb-6">
    <div class="p-6 flex flex-col md:flex-row items-center">
        <div class="md:w-2/3 mb-4 md:mb-0 md:pr-6">
            <h2 class="text-2xl font-bold text-gray-200 mb-2">    <p class="text-gray-200 mb-4 uppercase">
                
                <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
            
        </p></h2>
        
            <div class="flex flex-wrap gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary text-white">
                    <?php echo ucfirst(str_replace('_', ' ', $customer['fitness_goal'] ?? 'General Fitness')); ?>
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                    <?php echo ucfirst($customer['subscription_type'] ?? 'Monthly'); ?> Plan
                </span>
            </div>
        </div>
        <div class="md:w-1/3 flex justify-center">
            <?php if (!empty($customer['profile_image'])): ?>
                <img class="h-40 w-auto object-cover rounded-lg" src="<?php echo '../' . htmlspecialchars($customer['profile_image']); ?>" alt="Profile">
            <?php else: ?>
                <div class="h-40 w-40 rounded-lg bg-gray-200 flex items-center justify-center text-gray-600 text-6xl">
                    <?php echo strtoupper(substr($customer['first_name'], 0, 1)); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Membership Status -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-primary">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Membership Status</p>
                            <p class="text-lg font-semibold text-gray-800">
                                <?php echo ucfirst($customer['membership_status'] ?? 'Active'); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Membership Type -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-100 mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6" style="color: #FF6B45;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Membership Type</p>
                            <p class="text-lg font-semibold text-gray-800">
                                <?php echo ucfirst($customer['membership_type'] ?? 'Premium'); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Expiry Date -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-blue-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Expiry Date</p>
                            <p class="text-lg font-semibold text-gray-800">
                                <?php echo date('M d, Y', strtotime($customer['end_date'] ?? '2025-12-31')); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Workouts -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-green-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">This Month</p>
                            <p class="text-lg font-semibold text-gray-800">
                                <?php echo count($attendance_counts) ?: 0; ?> Workouts
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <a href="classes.php" class="flex flex-col items-center justify-center p-4 bg-light rounded-lg hover:bg-gray-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 mb-2" style="color: #FF6B45;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                        <span class="text-sm font-medium text-gray-700">Book Class</span>
                    </a>
                    
                    <a href="workouts.php" class="flex flex-col items-center justify-center p-4 bg-light rounded-lg hover:bg-gray-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 mb-2" style="color: #FF6B45;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" />
                        </svg>
                        <span class="text-sm font-medium text-gray-700">Start Workout</span>
                    </a>
                    
                    <a href="profile.php" class="flex flex-col items-center justify-center p-4 bg-light rounded-lg hover:bg-gray-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 mb-2" style="color: #FF6B45;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                        <span class="text-sm font-medium text-gray-700">Update Profile</span>
                    </a>
                    
                    <a href="membership.php" class="flex flex-col items-center justify-center p-4 bg-light rounded-lg hover:bg-gray-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 mb-2" style="color: #FF6B45;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                        </svg>
                        <span class="text-sm font-medium text-gray-700">Renew Membership</span>
                    </a>
                </div>
            </div>
            
            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
         
           
            </div>
        </main>
    </div>
</body>
</html>