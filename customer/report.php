<?php
// Start session and include page transition effect
include 'includes/pageeffect.php';

// Include database connection
require_once '../includes/db_connect.php';

// Check if user is logged in as a customer
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$customer_id = $_SESSION['customer_id'];
$reportType = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'weekly';
$startDate = isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : date('Y-m-d', strtotime('-1 week'));
$endDate = isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : date('Y-m-d');
$exportFormat = isset($_GET['export']) ? htmlspecialchars($_GET['export']) : '';

// Set default date ranges based on report type
if (isset($_GET['type']) && !isset($_GET['start_date'])) {
    switch ($reportType) {
        case 'weekly':
            $startDate = date('Y-m-d', strtotime('-1 week'));
            break;
        case 'monthly':
            $startDate = date('Y-m-d', strtotime('-1 month'));
            break;
        case 'yearly':
            $startDate = date('Y-m-d', strtotime('-1 year'));
            break;
    }
}

// Get customer data
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

// Get attendance data for the selected period
$stmt = $conn->prepare("SELECT DATE(check_in) as date, 
                               TIME(check_in) as check_in_time, 
                               TIME(check_out) as check_out_time,
                               TIMESTAMPDIFF(MINUTE, check_in, check_out) as duration_minutes,
                               branch,
                               notes
                        FROM attendance 
                        WHERE customer_id = ? 
                        AND DATE(check_in) BETWEEN ? AND ? 
                        ORDER BY check_in DESC");
$stmt->bind_param("iss", $customer_id, $startDate, $endDate);
$stmt->execute();
$attendanceResult = $stmt->get_result();
$attendanceData = [];
while ($row = $attendanceResult->fetch_assoc()) {
    $attendanceData[] = $row;
}

// Get class bookings for the selected period
$stmt = $conn->prepare("SELECT cb.*, c.class_name, c.class_date, c.start_time, c.end_time, c.instructor, c.branch
                        FROM class_bookings cb
                        JOIN classes c ON cb.class_id = c.id
                        WHERE cb.customer_id = ? 
                        AND c.class_date BETWEEN ? AND ? 
                        ORDER BY c.class_date DESC");
$stmt->bind_param("iss", $customer_id, $startDate, $endDate);
$stmt->execute();
$bookingsResult = $stmt->get_result();
$bookingsData = [];
while ($row = $bookingsResult->fetch_assoc()) {
    $bookingsData[] = $row;
}

// Process attendance data for charts
$attendanceDates = [];
$attendanceDurations = [];
$totalWorkouts = count($attendanceData);
$totalDuration = 0;

foreach ($attendanceData as $record) {
    $date = $record['date'];
    $attendanceDates[] = date('M d', strtotime($date));
    
    $duration = isset($record['duration_minutes']) ? round($record['duration_minutes'] / 60, 1) : 0;
    $attendanceDurations[] = $duration;
    $totalDuration += $duration;
}

// Calculate average workout duration
$avgDuration = $totalWorkouts > 0 ? round($totalDuration / $totalWorkouts, 1) : 0;

// Handle export requests
if (!empty($exportFormat)) {
    switch ($exportFormat) {
        case 'csv':
            exportCSV($customer, $attendanceData, $bookingsData, $reportType);
            break;
        case 'pdf':
            exportPDF($customer, $attendanceData, $bookingsData, $reportType);
            break;
    }
}

// Function to export data as CSV
function exportCSV($customer, $attendanceData, $bookingsData, $reportType) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Customer info
    fputcsv($output, ['Customer Report: ' . $customer['first_name'] . ' ' . $customer['last_name']]);
    fputcsv($output, ['Report Type', $reportType]);
    fputcsv($output, ['Generated On', date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Export attendance data
    fputcsv($output, ['Attendance Report']);
    fputcsv($output, ['Date', 'Check In', 'Check Out', 'Duration (Hours)', 'Branch', 'Notes']);
    
    foreach ($attendanceData as $record) {
        $duration = isset($record['duration_minutes']) ? round($record['duration_minutes'] / 60, 1) : 0;
        fputcsv($output, [
            $record['date'],
            $record['check_in_time'],
            $record['check_out_time'],
            $duration,
            $record['branch'],
            $record['notes']
        ]);
    }
    
    // Add a blank line between reports
    fputcsv($output, []);
    
    // Export class bookings data
    fputcsv($output, ['Class Bookings Report']);
    fputcsv($output, ['Class Name', 'Date', 'Start Time', 'End Time', 'Instructor', 'Branch', 'Status']);
    
    foreach ($bookingsData as $record) {
        fputcsv($output, [
            $record['class_name'],
            $record['class_date'],
            $record['start_time'],
            $record['end_time'],
            $record['instructor'],
            $record['branch'],
            $record['attendance_status']
        ]);
    }
    
    fclose($output);
    exit;
}

// Function to export data as PDF (placeholder)
function exportPDF($customer, $attendanceData, $bookingsData, $reportType) {
    // This is a placeholder - in a real implementation, you would use a PDF library
    header('Content-Type: text/html');
    echo '<p>PDF export functionality requires a PDF library. Please install a PDF library like mPDF or TCPDF.</p>';
    echo '<p><a href="report.php">Return to Reports</a></p>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress & Attendance Reports | Gym Network</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Include Chart.js library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        #head {
            background-image: repeating-radial-gradient(circle at 0 0, transparent 0, #ff6b45 100px), repeating-linear-gradient(#e74c3c, #ff6b45);
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
        
        /* Chart container */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        /* Transition for sidebar */
        #content-wrapper { transition: margin-left 0.3s ease; }
        
        /* Report card */
        .report-card {
            border-radius: 0.5rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            background-color: white;
            overflow: hidden;
        }
        
        /* Table styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            text-align: left;
            padding: 12px 15px;
            font-weight: 600;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .data-table tr:hover {
            background-color: #f9fafb;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Include sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div id="content-wrapper" class="ml-0 lg:ml-64 min-h-screen transition-all duration-300">
        <!-- Dashboard Content -->
        <main class="p-4 lg:p-6">
            <!-- Page Header -->
            <div id="head" class="rounded-3xl shadow-sm overflow-hidden mb-6 relative">
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-white mb-2">
                        Progress & Attendance Reports
                    </h2>
                    <p class="text-white opacity-90">
                        Track your fitness journey and monitor your progress over time
                    </p>
                </div>
            </div>
            
            <!-- Report Filters -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <form method="GET" action="report.php" class="flex flex-wrap gap-4">
                    <div class="w-full md:w-auto">
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                        <select id="type" name="type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 py-2 px-3">
                            <option value="weekly" <?php echo $reportType == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                            <option value="monthly" <?php echo $reportType == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                            <option value="yearly" <?php echo $reportType == 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                        </select>
                    </div>
                    
                    <div class="w-full md:w-auto">
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 py-2 px-3">
                    </div>
                    
                    <div class="w-full md:w-auto">
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 py-2 px-3">
                    </div>
                    
                    <div class="w-full md:w-auto flex items-end">
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-opacity-90 transition-colors">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Report Tabs -->
            <div class="mb-6">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button type="button" class="tab-button border-primary text-primary py-4 px-1 border-b-2 font-medium text-sm" data-tab="attendance">
                            Attendance
                        </button>
                        <button type="button" class="tab-button text-gray-500 hover:text-gray-700 py-4 px-1 border-b-2 border-transparent font-medium text-sm" data-tab="classes">
                            Classes
                        </button>
                        <button type="button" class="tab-button text-gray-500 hover:text-gray-700 py-4 px-1 border-b-2 border-transparent font-medium text-sm" data-tab="summary">
                            Summary
                        </button>
                    </nav>
                </div>
            </div>
            
            <!-- Attendance Tab Content -->
            <div id="attendance-tab" class="tab-content">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <!-- Total Workouts -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-gray-100 mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6" style="color: #FF6B45;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Total Workouts</p>
                                <p class="text-lg font-semibold text-gray-800">
                                    <?php echo $totalWorkouts; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Hours -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-gray-100 mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6" style="color: #FF6B45;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Total Hours</p>
                                <p class="text-lg font-semibold text-gray-800">
                                    <?php echo round($totalDuration, 1); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Average Duration -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-gray-100 mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6" style="color: #FF6B45;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Avg. Duration</p>
                                <p class="text-lg font-semibold text-gray-800">
                                    <?php echo $avgDuration; ?> hrs
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Frequency -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-gray-100 mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6" style="color: #FF6B45;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Frequency</p>
                                <p class="text-lg font-semibold text-gray-800">
                                    <?php 
                                        $days = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24);
                                        $days = max(1, $days);
                                        echo round(($totalWorkouts / $days) * 7, 1) . ' / week';
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Attendance Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Workout Frequency Chart -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Workout Frequency</h3>
                        <div class="chart-container">
                            <canvas id="workoutFrequencyChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Workout Duration Chart -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Workout Duration</h3>
                        <div class="chart-container">
                            <canvas id="workoutDurationChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Attendance Records -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Attendance Records</h3>
                        <div class="flex space-x-2">
                            <a href="report.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                Export CSV
                            </a>
                            <a href="report.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'pdf'])); ?>" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                Export PDF
                            </a>
                        </div>
                    </div>
                    
                    <?php if (count($attendanceData) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Duration</th>
                                    <th>Branch</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendanceData as $record): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                    <td><?php echo $record['check_in_time']; ?></td>
                                    <td><?php echo $record['check_out_time']; ?></td>
                                    <td>
                                        <?php 
                                            if (isset($record['duration_minutes'])) {
                                                echo round($record['duration_minutes'] / 60, 1) . ' hours';
                                            } else {
                                                echo '-';
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo $record['branch']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto text-gray-300 mb-3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
                        <p class="text-gray-500">No attendance data found for the selected period</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Classes Tab Content -->
            <div id="classes-tab" class="tab-content hidden">
                <!-- Class Bookings -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Class Participation</h3>
                    
                    <?php if (count($bookingsData) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Class Name</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Instructor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookingsData as $record): ?>
                                <tr>
                                    <td><?php echo $record['class_name']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($record['class_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($record['start_time'])) . ' - ' . date('h:i A', strtotime($record['end_time'])); ?></td>
                                    <td><?php echo $record['instructor']; ?></td>
                                    <td>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php 
                                                switch($record['attendance_status']) {
                                                    case 'attended': echo 'bg-green-100 text-green-800'; break;
                                                    case 'booked': echo 'bg-blue-100 text-blue-800'; break;
                                                    case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                                    case 'no_show': echo 'bg-yellow-100 text-yellow-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                            ?>">
                                            <?php echo ucfirst($record['attendance_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto text-gray-300 mb-3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                        <p class="text-gray-500">No class bookings found for the selected period</p>
                        <a href="classes.php" class="mt-3 inline-block px-4 py-2 bg-primary text-white rounded-md hover:bg-opacity-90 transition-colors">
                            Browse Classes
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Summary Tab Content -->
            <div id="summary-tab" class="tab-content hidden">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Workout Summary</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Total Workouts</span>
                                <span class="font-semibold"><?php echo $totalWorkouts; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Total Hours</span>
                                <span class="font-semibold"><?php echo round($totalDuration, 1); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Avg. Duration</span>
                                <span class="font-semibold"><?php echo $avgDuration; ?> hrs</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Weekly Frequency</span>
                                <span class="font-semibold">
                                    <?php 
                                        $days = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24);
                                        $days = max(1, $days);
                                        echo round(($totalWorkouts / $days) * 7, 1) . ' / week';
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Class Participation</h3>
                        <?php if (count($bookingsData) > 0): ?>
                        <div class="space-y-4">
                            <?php
                                // Calculate class statistics
                                $totalClasses = count($bookingsData);
                                $attendedClasses = 0;
                                $cancelledClasses = 0;
                                $noShowClasses = 0;
                                
                                foreach ($bookingsData as $booking) {
                                    switch ($booking['attendance_status']) {
                                        case 'attended': $attendedClasses++; break;
                                        case 'cancelled': $cancelledClasses++; break;
                                        case 'no_show': $noShowClasses++; break;
                                    }
                                }
                                
                                $attendanceRate = $totalClasses > 0 ? ($attendedClasses / $totalClasses) * 100 : 0;
                            ?>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Total Classes</span>
                                <span class="font-semibold"><?php echo $totalClasses; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Attended</span>
                                <span class="font-semibold"><?php echo $attendedClasses; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Cancelled</span>
                                <span class="font-semibold"><?php echo $cancelledClasses; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Attendance Rate</span>
                                <span class="font-semibold"><?php echo round($attendanceRate) . '%'; ?></span>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-gray-500">No class data available</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Overall Progress Chart -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Overall Activity</h3>
                    <div class="chart-container">
                        <canvas id="overallActivityChart"></canvas>
                    </div>
                </div>
                
                <!-- Recommendations -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Recommendations</h3>
                    
                    <div class="space-y-4">
                        <?php if ($totalWorkouts == 0): ?>
                        <div class="p-4 bg-blue-50 rounded-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-600">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">Start Your Fitness Journey</h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <p>We haven't recorded any workouts for you in this period. Visit the gym to start tracking your progress!</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php elseif ($totalWorkouts < 3 && $reportType == 'weekly'): ?>
                        <div class="p-4 bg-yellow-50 rounded-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-yellow-600">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Increase Your Workout Frequency</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>For optimal results, aim for at least 3-4 workouts per week. You're currently averaging <?php echo $totalWorkouts; ?> per week.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php elseif ($avgDuration < 1): ?>
                        <div class="p-4 bg-yellow-50 rounded-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-yellow-600">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Extend Your Workout Duration</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>Your average workout duration is <?php echo $avgDuration; ?> hours. Try to extend your sessions to at least 1 hour for better results.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="p-4 bg-green-50 rounded-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-green-600">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-green-800">Great Progress!</h3>
                                    <div class="mt-2 text-sm text-green-700">
                                        <p>You're maintaining a good workout routine. Keep up the great work!</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (count($bookingsData) == 0): ?>
                        <div class="p-4 bg-blue-50 rounded-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-600">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">Try Group Classes</h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <p>You haven't participated in any group classes. Classes are a great way to stay motivated and add variety to your routine.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const tabName = button.getAttribute('data-tab');
                    
                    // Update active tab button
                    tabButtons.forEach(btn => {
                        btn.classList.remove('border-primary', 'text-primary');
                        btn.classList.add('text-gray-500', 'border-transparent');
                    });
                    button.classList.remove('text-gray-500', 'border-transparent');
                    button.classList.add('border-primary', 'text-primary');
                    
                    // Show selected tab content
                    tabContents.forEach(content => {
                        content.classList.add('hidden');
                    });
                    document.getElementById(tabName + '-tab').classList.remove('hidden');
                });
            });
            
            // Initialize charts
            const attendanceDates = <?php echo json_encode($attendanceDates); ?>;
            const attendanceDurations = <?php echo json_encode($attendanceDurations); ?>;
            
            // Workout Frequency Chart
            if (document.getElementById('workoutFrequencyChart')) {
                new Chart(document.getElementById('workoutFrequencyChart'), {
                    type: 'bar',
                    data: {
                        labels: attendanceDates,
                        datasets: [{
                            label: 'Workouts',
                            data: attendanceDates.map(() => 1), // Each date represents one workout
                            backgroundColor: '#FF6B45',
                            borderColor: '#FF6B45',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }
            
            // Workout Duration Chart
            if (document.getElementById('workoutDurationChart')) {
                new Chart(document.getElementById('workoutDurationChart'), {
                    type: 'line',
                    data: {
                        labels: attendanceDates,
                        datasets: [{
                            label: 'Hours',
                            data: attendanceDurations,
                            backgroundColor: 'rgba(255, 107, 69, 0.2)',
                            borderColor: '#FF6B45',
                            borderWidth: 2,
                            tension: 0.1,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Hours'
                                }
                            }
                        }
                    }
                });
            }
            
            // Overall Activity Chart
            if (document.getElementById('overallActivityChart')) {
                new Chart(document.getElementById('overallActivityChart'), {
                    type: 'bar',
                    data: {
                        labels: attendanceDates,
                        datasets: [{
                            label: 'Workout Hours',
                            data: attendanceDurations,
                            backgroundColor: '#FF6B45',
                            borderColor: '#FF6B45',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Hours'
                                }
                            }
                        }
                    }
                });
            }
        });
        
        // Toggle sidebar on mobile
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        const contentWrapper = document.getElementById('content-wrapper');
        
        if (sidebarToggle && sidebar && contentWrapper) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('translate-x-0');
                sidebar.classList.toggle('-translate-x-full');
                
                if (window.innerWidth >= 1024) {
                    contentWrapper.classList.toggle('ml-0');
                    contentWrapper.classList.toggle('ml-64');
                }
            });
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth < 1024 && 
                sidebar && 
                !sidebar.contains(event.target) && 
                sidebarToggle && 
                !sidebarToggle.contains(event.target) &&
                sidebar.classList.contains('translate-x-0')) {
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
            }
        });
    </script>
</body>
</html>