<?php
// Start session
include 'includes/pageeffect.php';

// Include database connection
require_once '../includes/db_connect.php';

// Check if user is logged in as a customer
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

// Get customer data
$customer_id = $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT c.*, b.name as branch_name, b.location 
                        FROM customers c 
                        LEFT JOIN branches b ON c.branch = b.name 
                        WHERE c.id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();

// Get notifications
$stmt = $conn->prepare("SELECT * FROM notifications 
                        WHERE user_type = 'customer' AND user_id = ? 
                        ORDER BY created_at DESC");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$notifications = $stmt->get_result();

// Mark notification as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notification_id = $_GET['mark_read'];
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_type = 'customer' AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $customer_id);
    $stmt->execute();
    
    // Redirect to remove the query parameter
    header("Location: notification.php");
    exit();
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_type = 'customer' AND user_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    
    // Redirect to remove the query parameter
    header("Location: notification.php");
    exit();
}

// Count unread notifications
$stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications 
                        WHERE user_type = 'customer' AND user_id = ? AND is_read = 0");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$unread_count = $result->fetch_assoc()['unread_count'];

// Sample notifications if none exist
if ($notifications->num_rows == 0) {
    // Create sample notifications for demo
    $sample_notifications = [
        [
            'title' => 'Welcome to Gym Network!',
            'message' => 'Thank you for joining our fitness community. We\'re excited to help you achieve your fitness goals!',
            'type' => 'welcome',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
        [
            'title' => 'New Yoga Class Added',
            'message' => 'We\'ve added a new Yoga class on Tuesdays at 6:00 PM. Book your spot now!',
            'type' => 'class',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ],
        [
            'title' => 'Membership Renewal Reminder',
            'message' => 'Your membership will expire in 30 days. Renew now to avoid interruption in your fitness journey.',
            'type' => 'membership',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
    
    // Insert sample notifications
    $stmt = $conn->prepare("INSERT INTO notifications (user_type, user_id, title, message, type, is_read, created_at) 
                            VALUES ('customer', ?, ?, ?, ?, 0, ?)");
    
    foreach ($sample_notifications as $notification) {
        $stmt->bind_param("issss", 
                        $customer_id, 
                        $notification['title'], 
                        $notification['message'], 
                        $notification['type'], 
                        $notification['created_at']);
        $stmt->execute();
    }
    
    // Refresh notifications
    $stmt = $conn->prepare("SELECT * FROM notifications 
                            WHERE user_type = 'customer' AND user_id = ? 
                            ORDER BY created_at DESC");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $notifications = $stmt->get_result();
    
    // Update unread count
    $unread_count = count($sample_notifications);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Gym Network</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
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
        <header class="bg-white shadow-sm">
            <div class="flex justify-between items-center px-4 py-3 lg:px-6">
                <h1 class="text-xl font-semibold text-gray-800">Notifications</h1>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button class="p-1 text-gray-500 hover:text-gray-700 focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                        </button>
                        <?php if ($unread_count > 0): ?>
                            <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-primary"></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center">
                        <span class="text-sm text-gray-700 mr-2 hidden sm:inline-block">
                            <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                        </span>
                        <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600">
                            <?php echo strtoupper(substr($customer['first_name'], 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Notifications Content -->
        <main class="p-4 lg:p-6">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <div class="flex items-center">
                        <h2 class="text-xl font-semibold text-gray-800">All Notifications</h2>
                        <?php if ($unread_count > 0): ?>
                            <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full bg-primary text-white">
                                <?php echo $unread_count; ?> new
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($unread_count > 0): ?>
                        <a href="?mark_all_read=1" class="text-sm font-medium" style="color: #FF6B45;">
                            Mark all as read
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="divide-y divide-gray-200">
                    <?php if ($notifications && $notifications->num_rows > 0): ?>
                        <?php while ($notification = $notifications->fetch_assoc()): ?>
                            <div class="p-6 <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 mr-4">
                                        <?php if ($notification['type'] == 'welcome'): ?>
                                            <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-green-600">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                        <?php elseif ($notification['type'] == 'class'): ?>
                                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-blue-600">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                                </svg>
                                            </div>
                                        <?php elseif ($notification['type'] == 'membership'): ?>
                                            <div class="h-10 w-10 rounded-full bg-orange-100 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-orange-600">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                                </svg>
                                            </div>
                                        <?php else: ?>
                                            <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-600">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-lg font-medium text-gray-900">
                                                <?php echo htmlspecialchars($notification['title']); ?>
                                            </h3>
                                            <p class="text-sm text-gray-500">
                                                <?php echo date('M d, g:i A', strtotime($notification['created_at'])); ?>
                                            </p>
                                        </div>
                                        
                                        <p class="mt-1 text-gray-600">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </p>
                                        
                                        <div class="mt-3 flex items-center">
                                            <?php if (!$notification['is_read']): ?>
                                                <a href="?mark_read=<?php echo $notification['id']; ?>" class="text-sm font-medium mr-4" style="color: #FF6B45;">
                                                    Mark as read
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($notification['type'] == 'class'): ?>
                                                <a href="classes.php" class="text-sm font-medium" style="color: #FF6B45;">
                                                    View Classes
                                                </a>
                                            <?php elseif ($notification['type'] == 'membership'): ?>
                                                <a href="membership.php" class="text-sm font-medium" style="color: #FF6B45;">
                                                    View Membership
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="p-6 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto text-gray-400 mb-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            <h3 class="text-lg font-medium text-gray-800 mb-2">No Notifications</h3>
                            <p class="text-gray-600">You don't have any notifications at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>