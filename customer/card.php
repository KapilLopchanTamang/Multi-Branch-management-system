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
$stmt = $conn->prepare("SELECT c.*, b.name as branch_name, b.location 
                    FROM customers c 
                    LEFT JOIN branches b ON c.branch = b.name 
                    WHERE c.id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();

// Get membership data
$stmt = $conn->prepare("SELECT * FROM memberships WHERE customer_id = ? ORDER BY end_date DESC LIMIT 1");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$memberships = $stmt->get_result();
$current_membership = $memberships->fetch_assoc();

// If no membership found, create default values
if (!$current_membership) {
    $current_membership = [
        'membership_type' => $customer['subscription_type'] ?? 'monthly',
        'status' => 'Active',
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d', strtotime('+1 month'))
    ];
}

// Check if membership is expired
$is_expired = false;
$today = new DateTime();
$expiry_date = new DateTime($current_membership['end_date']);
if ($today > $expiry_date) {
    $is_expired = true;
}

// Format subscription type for display
$subscription_types = [
    'monthly' => 'Monthly',
    'six_months' => '6 Months',
    'yearly' => 'Yearly'
];

$subscription_text = $subscription_types[$customer['subscription_type'] ?? 'monthly'];

// Create QR code data with expiration date
$customerId = $customer['id'];
$customerName = $customer['first_name'] . ' ' . $customer['last_name'];
$expiryDate = $current_membership['end_date'];
$qrData = "GYM_CUSTOMER_ID:{$customerId}|NAME:{$customerName}|EXPIRY:{$expiryDate}";

// Check if Composer autoloader exists
if (!file_exists('../vendor/autoload.php')) {
    die("Error: ../vendor/autoload.php not found. Please run 'composer require bacon/bacon-qr-code'");
}

require_once '../vendor/autoload.php';

// Import all necessary classes
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Writer;

// Generate QR code
$qrCodePath = '';
$qrCodeDataUri = '';

try {
    // Create directory for QR codes if it doesn't exist
    $qrDir = '../uploads/qrcodes';
    if (!is_dir($qrDir)) {
        mkdir($qrDir, 0755, true);
    }
    
    // Define QR code file path
    $qrCodePath = $qrDir . '/customer_' . $customerId . '_qr.png';
    
    // Check if we should use Imagick or SVG
    if (extension_loaded('imagick')) {
        // Use Imagick backend with custom colors and improved settings
        $renderer = new ImageRenderer(
            new RendererStyle(
                400,                  // Size increased for better quality
                3,                    // Margin reduced for cleaner look
                null,                 // Default foreground color
                null,                 // Default background color
                                   // Rounded blocks for smoother appearance
            ),
            new ImagickImageBackEnd()
        );
    } else {
        // Fallback to SVG if Imagick is not available
        $renderer = new ImageRenderer(
            new RendererStyle(
                400,                  // Size increased for better quality
                3,                    // Margin reduced for cleaner look
                null,                 // Default foreground color
                null,                 // Default background color
                                   // Rounded blocks for smoother appearance
            ),
            new SvgImageBackEnd()
        );
    }
    
    $writer = new Writer($renderer);
    
    // Generate the QR code and save to file
    $writer->writeFile($qrData, $qrCodePath);
    
    // Convert to data URI for display
    $imageType = (extension_loaded('imagick')) ? 'png' : 'svg+xml';
    $imageData = file_get_contents($qrCodePath);
    $base64 = base64_encode($imageData);
    $qrCodeDataUri = 'data:image/' . $imageType . ';base64,' . $base64;
    
} catch (Exception $e) {
    // Fallback to QR Server API if bacon/bacon-qr-code fails
    $qrCodeDataUri = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' . urlencode($qrData) . '&margin=3&color=E74C3C&bgcolor=FFFFFF&qzone=3';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Card | Gym Network</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .text-primary { color: #e74c3c; font-weight: bold; }
        .bg-primary { background-color: #FF6B45; }
        .bg-light { background-color: #f9f9f9; }
        
        /* Gradient pattern for cards */
        .gradient-pattern {
            background-image: repeating-radial-gradient(circle at 0 0, transparent 0, #ff6b45 100px), repeating-linear-gradient(#e74c3c, #ff6b45);
            background-color: #ff6b45;
        }
        
        /* Expired card style */
        .expired-card .gradient-pattern {
            background-image: repeating-radial-gradient(circle at 0 0, transparent 0, #888 100px), repeating-linear-gradient(#666, #888);
            background-color: #888;
        }
        
        .expired-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        
        .expired-stamp {
            border: 5px solid #f44336;
            color: #f44336;
            padding: 10px 20px;
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            transform: rotate(-15deg);
            border-radius: 5px;
            background-color: rgba(255, 255, 255, 0.8);
        }
        
        /* Card flip animation */
        .card-container {
            perspective: 1000px;
        }
        
        .card-inner {
            transition: transform 0.8s;
            transform-style: preserve-3d;
        }
        
        .card-container:hover .card-inner {
            transform: rotateY(180deg);
        }
        
        .card-front, .card-back {
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
        }
        
        .card-back {
            transform: rotateY(180deg);
        }
        
        /* QR code styling */
        .qr-code-container {
            padding: 1px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: inline-block;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #ccc; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Include sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div id="content-wrapper" class="min-h-screen transition-all duration-300">
        <!-- Top Navigation -->
        <header class="bg-white shadow-sm">
            <div class="flex justify-between items-center px-4 py-3 lg:px-6">
                <h1 class="text-xl font-semibold text-gray-800">Membership Card</h1>
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
                        <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 overflow-hidden">
                            <?php if (!empty($customer['profile_image'])): ?>
                                <img src="<?php echo '../' . htmlspecialchars($customer['profile_image']); ?>" alt="Profile" class="h-full w-full object-cover">
                            <?php else: ?>
                                <?php echo strtoupper(substr($customer['first_name'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Card Content -->
        <main class="p-4 lg:p-6">
            <div class="max-w-md mx-auto">
                <?php if ($is_expired): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        <div>
                            <p class="font-bold">Your membership has expired</p>
                            <p class="text-sm">Please renew your membership to continue using gym facilities.</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Membership Card -->
                <div class="card-container w-full h-56 mb-8 <?php echo $is_expired ? 'expired-card' : ''; ?>">
                    <div class="card-inner relative w-full h-full">
                        <!-- Card Front -->
                        <div class="card-front absolute w-full h-full rounded-xl overflow-hidden shadow-lg">
                            <div class="gradient-pattern w-full h-full p-6 text-white">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h2 class="text-2xl font-bold mb-1">GYM NETWORK</h2>
                                        <p class="text-sm opacity-90"><?php echo htmlspecialchars($customer['branch_name'] ?? $customer['branch']); ?></p>
                                    </div>
                                    <div class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm">
                                        <?php echo $subscription_text; ?>
                                    </div>
                                </div>
                                
                                <div class="absolute bottom-6 left-6 right-6">
                                    <div class="flex justify-between items-end">
                                        <div>
                                            <p class="text-xs opacity-80 mb-1">MEMBER</p>
                                            <h3 class="text-xl font-bold"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h3>
                                            <p class="text-sm opacity-90">ID: <?php echo $customer['id']; ?></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs opacity-80 mb-1">VALID UNTIL</p>
                                            <p class="text-lg font-semibold"><?php echo date('d/m/Y', strtotime($current_membership['end_date'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="absolute top-0 right-0 bottom-0 left-0 bg-black bg-opacity-10 pointer-events-none"></div>
                                
                                <?php if ($is_expired): ?>
                                <div class="expired-overlay">
                                    <div class="expired-stamp">Expired</div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Card Back -->
                        <div class="card-back absolute w-full h-full bg-white rounded-xl overflow-hidden shadow-lg">
                            <div class="w-full h-full flex flex-col">
                                <div class="gradient-pattern h-12 w-full"></div>
                                
                                <div class="flex-1 p-6 flex flex-col items-center justify-center">
                                    <div class="mb-4 qr-code-container">
                                        <img src="<?php echo $qrCodeDataUri; ?>" alt="QR Code" class="w-40 h-40">
                                    </div>
                                    <p class="text-center text-sm text-gray-600">Scan this QR code at the reception desk for quick check-in</p>
                                </div>
                                
                                <div class="p-4 text-center text-xs text-gray-500">
                                    <p>This card is non-transferable and must be presented upon request</p>
                                </div>
                                
                                <?php if ($is_expired): ?>
                                <div class="expired-overlay">
                                    <div class="expired-stamp">Expired</div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center text-sm text-gray-600 mb-6">
                    <p>Hover over the card to see the QR code</p>
                </div>
                
                <!-- Card Actions -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Card Options</h3>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <button onclick="printCard()" class="flex flex-col items-center justify-center p-4 bg-light rounded-lg hover:bg-gray-200 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-700 mb-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                                </svg>
                                <span class="text-sm font-medium text-gray-700">Print Card</span>
                            </button>
                            
                            <button onclick="downloadQRCode()" class="flex flex-col items-center justify-center p-4 bg-light rounded-lg hover:bg-gray-200 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-700 mb-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                <span class="text-sm font-medium text-gray-700">Download QR</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Membership Info -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden mt-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Membership Information</h3>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Membership Type</p>
                                <p class="font-medium text-gray-800"><?php echo $subscription_text; ?></p>
                            </div>
                            
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Status</p>
                                <p class="font-medium <?php echo $is_expired ? 'text-red-600' : 'text-green-600'; ?>">
                                    <?php echo $is_expired ? 'Expired' : 'Active'; ?>
                                </p>
                            </div>
                            
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Start Date</p>
                                <p class="font-medium text-gray-800"><?php echo date('M d, Y', strtotime($current_membership['start_date'])); ?></p>
                            </div>
                            
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Expiry Date</p>
                                <p class="font-medium <?php echo $is_expired ? 'text-red-600' : 'text-gray-800'; ?>">
                                    <?php echo date('M d, Y', strtotime($current_membership['end_date'])); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <?php if ($is_expired): ?>
                            <a href="membership.php" class="block w-full py-2 bg-red-600 text-white text-center rounded-md hover:bg-red-700 transition-colors">
                                Renew Membership
                            </a>
                            <?php else: ?>
                            <a href="membership.php" class="block w-full py-2 bg-primary text-white text-center rounded-md hover:bg-opacity-90 transition-colors">
                                View Membership Details
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Print membership card
        function printCard() {
            const customerName = "<?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>";
            const customerId = <?php echo $customer['id']; ?>;
            const branchName = "<?php echo htmlspecialchars($customer['branch_name'] ?? $customer['branch']); ?>";
            const expiryDate = "<?php echo date('d/m/Y', strtotime($current_membership['end_date'])); ?>";
            const subscriptionText = "<?php echo $subscription_text; ?>";
            const qrCodeDataUri = "<?php echo $qrCodeDataUri; ?>";
            const isExpired = <?php echo $is_expired ? 'true' : 'false'; ?>;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Gym Membership Card - ${customerName}</title>
                    <style>
                        body { 
                            font-family: Arial, sans-serif; 
                            text-align: center; 
                            padding: 20px;
                            margin: 0;
                        }
                        .card-container { 
                            margin: 0 auto; 
                            width: 85mm;
                            height: 54mm;
                            position: relative;
                            border-radius: 10px;
                            overflow: hidden;
                            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                            page-break-inside: avoid;
                        }
                        .card-front {
                            background-image: repeating-radial-gradient(circle at 0 0, transparent 0, ${isExpired ? '#888' : '#ff6b45'} 100px), 
                                             repeating-linear-gradient(${isExpired ? '#666, #888' : '#e74c3c, #ff6b45'});
                            background-color: ${isExpired ? '#888' : '#ff6b45'};
                            color: white;
                            padding: 20px;
                            height: 100%;
                            box-sizing: border-box;
                            position: relative;
                        }
                        .card-back {
                            background-color: white;
                            padding: 20px;
                            height: 100%;
                            box-sizing: border-box;
                            position: relative;
                            margin-top: 20px;
                            border-radius: 10px;
                            overflow: hidden;
                            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                        }
                        .card-back-header {
                            position: absolute;
                            top: 0;
                            left: 0;
                            right: 0;
                            height: 12mm;
                            background-image: repeating-radial-gradient(circle at 0 0, transparent 0, ${isExpired ? '#888' : '#ff6b45'} 100px), 
                                             repeating-linear-gradient(${isExpired ? '#666, #888' : '#e74c3c, #ff6b45'});
                        }
                        .card-back-content {
                            position: relative;
                            top: 15mm;
                            text-align: center;
                        }
                        .gym-name {
                            font-size: 18px;
                            font-weight: bold;
                            margin-bottom: 2px;
                        }
                        .branch-name {
                            font-size: 12px;
                            opacity: 0.9;
                        }
                        .subscription {
                            position: absolute;
                            top: 20px;
                            right: 20px;
                            background-color: rgba(255, 255, 255, 0.2);
                            padding: 4px 10px;
                            border-radius: 20px;
                            font-size: 12px;
                        }
                        .member-info {
                            position: absolute;
                            bottom: 20px;
                            left: 20px;
                        }
                        .label {
                            font-size: 10px;
                            opacity: 0.8;
                            margin-bottom: 2px;
                        }
                        .member-name {
                            font-size: 16px;
                            font-weight: bold;
                        }
                        .member-id {
                            font-size: 12px;
                            opacity: 0.9;
                        }
                        .expiry-info {
                            position: absolute;
                            bottom: 20px;
                            right: 20px;
                            text-align: right;
                        }
                        .expiry-date {
                            font-size: 14px;
                            font-weight: bold;
                        }
                        .qr-code {
                            width: 100px;
                            height: 100px;
                            margin: 0 auto;
                            padding: 5px;
                            background: white;
                            border-radius: 5px;
                            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                        }
                        .instructions {
                            font-size: 12px;
                            color: #555;
                            margin-top: 10px;
                        }
                        .disclaimer {
                            font-size: 10px;
                            color: #777;
                            margin-top: 20px;
                        }
                        .page-break {
                            page-break-after: always;
                        }
                        .expired-overlay {
                            position: absolute;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            background-color: rgba(0, 0, 0, 0.6);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            z-index: 10;
                        }
                        .expired-stamp {
                            border: 5px solid #f44336;
                            color: #f44336;
                            padding: 10px 20px;
                            font-size: 24px;
                            font-weight: bold;
                            text-transform: uppercase;
                            transform: rotate(-15deg);
                            border-radius: 5px;
                            background-color: rgba(255, 255, 255, 0.8);
                        }
                        @media print {
                            .no-print {
                                display: none;
                            }
                        }
                    </style>
                </head>
                <body>
                    <!-- Front of Card -->
                    <div class="card-container">
                        <div class="card-front">
                            <div class="gym-name">GYM NETWORK</div>
                            <div class="branch-name">${branchName}</div>
                            
                            <div class="subscription">${subscriptionText}</div>
                            
                            <div class="member-info">
                                <div class="label">MEMBER</div>
                                <div class="member-name">${customerName}</div>
                                <div class="member-id">ID: ${customerId}</div>
                            </div>
                            
                            <div class="expiry-info">
                                <div class="label">VALID UNTIL</div>
                                <div class="expiry-date">${expiryDate}</div>
                            </div>
                            
                            ${isExpired ? `
                            <div class="expired-overlay">
                                <div class="expired-stamp">Expired</div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <div class="page-break"></div>
                    
                    <!-- Back of Card -->
                    <div class="card-container">
                        <div class="card-back">
                            <div class="card-back-header"></div>
                            <div class="card-back-content">
                                <img src="${qrCodeDataUri}" alt="QR Code" class="qr-code">
                                <div class="instructions">Scan this QR code at the reception desk for quick check-in</div>
                                <div class="disclaimer">This card is non-transferable and must be presented upon request</div>
                            </div>
                            
                            ${isExpired ? `
                            <div class="expired-overlay">
                                <div class="expired-stamp">Expired</div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <div class="no-print" style="margin-top: 20px;">
                        <button onclick="window.print()" style="padding: 10px 20px; background-color: #ff6b45; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            Print Card
                        </button>
                        <button onclick="window.close()" style="padding: 10px 20px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; margin-left: 10px; cursor: pointer;">
                            Close
                        </button>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
        }
        
        // Download QR code
        function downloadQRCode() {
            const customerName = "<?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>";
            const qrCodeDataUri = "<?php echo $qrCodeDataUri; ?>";
            
            // Create a temporary link element
            const link = document.createElement('a');
            link.href = qrCodeDataUri;
            link.download = `${customerName.replace(/\s+/g, '_')}_QR_Code.png`;
            
            // Append to the document, click it, and remove it
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>