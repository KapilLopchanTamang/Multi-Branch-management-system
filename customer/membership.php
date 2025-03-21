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

// Get membership data
$stmt = $conn->prepare("SELECT * FROM memberships WHERE customer_id = ? ORDER BY end_date DESC");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$memberships = $stmt->get_result();
$current_membership = $memberships->fetch_assoc();

// Get payment history
$stmt = $conn->prepare("SELECT * FROM payments WHERE customer_id = ? ORDER BY payment_date DESC LIMIT 5");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$payments = $stmt->get_result();

// Calculate days remaining
$days_remaining = 0;
$membership_status = 'Inactive';
if ($current_membership) {
    $end_date = new DateTime($current_membership['end_date']);
    $today = new DateTime();
    $days_remaining = $today->diff($end_date)->days;
    $membership_status = $current_membership['status'];
}

// Define membership plans for upgrade options
$membership_plans = [
    'monthly' => [
        'name' => 'Monthly',
        'price' => 49.99,
        'description' => 'Access to all gym facilities and basic classes',
        'features' => ['Full gym access', 'Basic fitness classes', 'Locker access', 'Fitness assessment']
    ],
    'six_months' => [
        'name' => '6 Months',
        'price' => 239.99,
        'description' => 'Save 20% with our 6-month membership plan',
        'features' => ['Full gym access', 'All fitness classes', 'Locker access', 'Fitness assessment', '1 free PT session']
    ],
    'yearly' => [
        'name' => 'Yearly',
        'price' => 449.99,
        'description' => 'Our best value plan with premium benefits',
        'features' => ['Full gym access', 'All fitness classes', 'Locker access', 'Fitness assessment', '3 free PT sessions', 'Nutrition consultation']
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership | Gym Network</title>
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
                <h1 class="text-xl font-semibold text-gray-800">My Membership</h1>
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
                            <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                        </span>
                        <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600">
                            <?php echo strtoupper(substr($customer['first_name'], 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Membership Content -->
        <main class="p-4 lg:p-6">
            <!-- Current Membership Card -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-1">Current Membership</h2>
                            <p class="text-gray-600">
                                Your membership at <?php echo htmlspecialchars($customer['branch_name'] ?? $customer['branch']); ?>
                            </p>
                        </div>
                        
                        <?php if ($current_membership && $membership_status == 'Active'): ?>
                            <div class="mt-4 md:mt-0">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="mt-4 md:mt-0">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                    Inactive
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="p-6">
                    <?php if ($current_membership): ?>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-1">Membership Type</h3>
                                <p class="text-lg font-semibold text-gray-800">
                                    <?php echo ucfirst($current_membership['membership_type']); ?>
                                </p>
                            </div>
                            
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-1">Start Date</h3>
                                <p class="text-lg font-semibold text-gray-800">
                                    <?php echo date('M d, Y', strtotime($current_membership['start_date'])); ?>
                                </p>
                            </div>
                            
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-1">Expiry Date</h3>
                                <p class="text-lg font-semibold text-gray-800">
                                    <?php echo date('M d, Y', strtotime($current_membership['end_date'])); ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php if ($membership_status == 'Active'): ?>
                            <div class="mt-6">
                                <div class="bg-light rounded-lg p-4">
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <h3 class="text-md font-medium text-gray-800 mb-1">Membership Validity</h3>
                                            <p class="text-gray-600">
                                                Your membership is valid for <?php echo $days_remaining; ?> more days
                                            </p>
                                        </div>
                                        
                                        <div class="mt-4 md:mt-0">
                                            <div class="w-full md:w-48 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                <?php 
                                                $total_days = (strtotime($current_membership['end_date']) - strtotime($current_membership['start_date'])) / (60 * 60 * 24);
                                                $percentage = min(100, max(0, ($days_remaining / $total_days) * 100));
                                                ?>
                                                <div class="h-full bg-primary" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-6 flex flex-col sm:flex-row gap-4">
                            <button class="px-4 py-2 bg-primary text-white rounded-md hover:bg-opacity-90 transition-colors">
                                Renew Membership
                            </button>
                            <button class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                View Membership Details
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto text-gray-400 mb-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                            <h3 class="text-lg font-medium text-gray-800 mb-2">No Active Membership</h3>
                            <p class="text-gray-600 mb-6">You don't have an active membership at the moment.</p>
                            <button class="px-4 py-2 bg-primary text-white rounded-md hover:bg-opacity-90 transition-colors">
                                Purchase Membership
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
          


            
            <!-- Payment History -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-800">Payment History</h2>
                    <a href="#" class="text-sm font-medium" style="color: #FF6B45;">View All</a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($payments && $payments->num_rows > 0): ?>
                                <?php while ($payment = $payments->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?php echo ucfirst($current_membership['membership_type'] ?? 'Premium'); ?> Membership
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">$<?php echo number_format($payment['amount'], 2); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="#" class="text-primary hover:text-opacity-80">Download</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No payment records found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>