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

// Handle profile update
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $fitness_goal = $_POST['fitness_goal'];
    $emergency_contact_name = trim($_POST['emergency_contact_name']);
    $emergency_contact_phone = trim($_POST['emergency_contact_phone']);
    $health_conditions = trim($_POST['health_conditions']);
    
    // Validate inputs
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Update customer data
        $stmt = $conn->prepare("UPDATE customers SET 
                                first_name = ?, 
                                last_name = ?, 
                                email = ?, 
                                phone = ?, 
                                address = ?, 
                                fitness_goal = ?,
                                emergency_contact_name = ?,
                                emergency_contact_phone = ?,
                                health_conditions = ?,
                                updated_at = CURRENT_TIMESTAMP
                                WHERE id = ?");
        
        $stmt->bind_param("sssssssssi", 
                        $first_name, 
                        $last_name, 
                        $email, 
                        $phone, 
                        $address, 
                        $fitness_goal,
                        $emergency_contact_name,
                        $emergency_contact_phone,
                        $health_conditions,
                        $customer_id);
        
        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
            
            // Refresh customer data
            $stmt = $conn->prepare("SELECT c.*, b.name as branch_name, b.location 
                                FROM customers c 
                                LEFT JOIN branches b ON c.branch = b.name 
                                WHERE c.id = ?");
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $customer = $result->fetch_assoc();
            
            // Update session data
            $_SESSION['customer_name'] = $first_name . ' ' . $last_name;
        } else {
            $error_message = "Error updating profile: " . $conn->error;
        }
    }
}

// Handle profile image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile_image'])) {
    // Check if file was uploaded without errors
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg',];
        $filename = $_FILES['profile_image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        // Verify file extension
        if (in_array(strtolower($filetype), $allowed)) {
            // Create unique filename
            $new_filename = 'profile_' . $customer_id . '_' . time() . '.' . $filetype;
            $upload_dir = '../uploads/profile_images/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $upload_path = $upload_dir . $new_filename;
            
            // Move the file
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                // Update database with new image path
                $image_path = 'uploads/profile_images/' . $new_filename;
                $stmt = $conn->prepare("UPDATE customers SET profile_image = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->bind_param("si", $image_path, $customer_id);
                
                if ($stmt->execute()) {
                    $success_message = "Profile image updated successfully!";
                    
                    // Refresh customer data
                    $stmt = $conn->prepare("SELECT c.*, b.name as branch_name, b.location 
                                        FROM customers c 
                                        LEFT JOIN branches b ON c.branch = b.name 
                                        WHERE c.id = ?");
                    $stmt->bind_param("i", $customer_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $customer = $result->fetch_assoc();
                } else {
                    $error_message = "Error updating profile image in database: " . $conn->error;
                }
            } else {
                $error_message = "Error uploading file. Please try again.";
            }
        } else {
            $error_message = "Invalid file type. Please upload a JPG, JPEG, PNG, or GIF file.";
        }
    } else {
        $error_message = "Error uploading file. Please try again.";
    }
}

// Handle profile image removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_profile_image'])) {
    // Update database to remove image path
    $stmt = $conn->prepare("UPDATE customers SET profile_image = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    
    if ($stmt->execute()) {
        $success_message = "Profile image removed successfully!";
        
        // Refresh customer data
        $stmt = $conn->prepare("SELECT c.*, b.name as branch_name, b.location 
                            FROM customers c 
                            LEFT JOIN branches b ON c.branch = b.name 
                            WHERE c.id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
    } else {
        $error_message = "Error removing profile image: " . $conn->error;
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "Please fill in all password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error_message = "New password must be at least 8 characters long.";
    } else {
        // Verify current password
        if (password_verify($current_password, $customer['password'])) {
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $conn->prepare("UPDATE customers SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $customer_id);
            
            if ($stmt->execute()) {
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "Error changing password: " . $conn->error;
            }
        } else {
            $error_message = "Current password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | Gym Network</title>
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
        
        /* Profile image overlay */
        .profile-image-container {
            position: relative;
            display: inline-block;
        }
        
        .profile-image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0, 0, 0, 0.5);
            overflow: hidden;
            width: 100%;
            height: 0;
            transition: .3s ease;
            border-bottom-left-radius: 9999px;
            border-bottom-right-radius: 9999px;
        }
        
        .profile-image-container:hover .profile-image-overlay {
            height: 50%;
            cursor: pointer;
        }
        
        .profile-image-text {
            color: white;
            font-size: 12px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            width: 100%;
        }
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
                <h1 class="text-xl font-semibold text-gray-800">My Profile</h1>
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
        
        <!-- Profile Content -->
        <main class="p-4 lg:p-6">
            <?php if (!empty($success_message)): ?>
                <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded-lg">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="mb-4 p-4 bg-red-100 border border-red-200 text-red-700 rounded-lg">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Profile Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="p-6 text-center border-b">
                            <!-- Profile Image with Upload Option -->
                            <div class="profile-image-container mx-auto mb-4" id="profileImageContainer">
                                <div class="h-24 w-24 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 text-4xl overflow-hidden">
                                    <?php if (!empty($customer['profile_image'])): ?>
                                        <img src="<?php echo '../' . htmlspecialchars($customer['profile_image']); ?>" alt="Profile" class="h-full w-full object-cover">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($customer['first_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="profile-image-overlay">
                                    <div class="profile-image-text">Update Photo</div>
                                </div>
                            </div>
                            
                            <!-- Profile Image Modal -->
                            <div id="profileImageModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
                                <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-semibold text-gray-800">Update Profile Picture</h3>
                                        <button id="closeModal" class="text-gray-500 hover:text-gray-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    <form action="" method="POST" enctype="multipart/form-data" id="profileImageForm">
                                        <div class="mb-4">
                                            <label for="profile_image" class="block text-sm font-medium text-gray-700 mb-1">Choose a new profile picture</label>
                                            <input type="file" id="profile_image" name="profile_image" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                        </div>
                                        
                                        <!-- Image Preview -->
                                        <div id="imagePreview" class="mb-4 hidden">
                                            <div class="h-32 w-32 rounded-full bg-gray-200 mx-auto overflow-hidden">
                                                <img id="previewImage" src="#" alt="Preview" class="h-full w-full object-cover">
                                            </div>
                                        </div>
                                        
                                        <div class="flex justify-between">
                                            <button type="submit" name="update_profile_image" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-opacity-90 transition-colors">
                                                Upload New Photo
                                            </button>
                                            
                                            <?php if (!empty($customer['profile_image'])): ?>
                                                <button type="submit" name="remove_profile_image" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                                    Remove Photo
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <h2 class="text-xl font-semibold text-gray-800">
                                <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                            </h2>
                            <p class="text-gray-500 mt-1">
                                Member since <?php echo date('M Y', strtotime($customer['created_at'])); ?>
                            </p>
                            <div class="mt-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary text-white">
                                    <?php echo ucfirst(str_replace('_', ' ', $customer['fitness_goal'] ?? 'General Fitness')); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Membership Info</h3>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Branch:</span>
                                    <span class="text-gray-800 font-medium"><?php echo htmlspecialchars($customer['branch_name'] ?? $customer['branch']); ?></span>
                                </div>
                                
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Status:</span>
                                    <span class="text-green-600 font-medium"><?php echo ucfirst($customer['status'] ?? 'Active'); ?></span>
                                </div>
                                
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Plan:</span>
                                    <span class="text-gray-800 font-medium"><?php echo ucfirst($customer['subscription_type'] ?? 'Monthly'); ?></span>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <a href="membership.php" class="block w-full py-2 px-4 bg-primary text-white text-center rounded-md hover:bg-opacity-90 transition-colors">
                                    View Membership Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Edit Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">Personal Information</h3>
                        </div>
                        
                        <div class="p-6">
                            <form action="" method="POST">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($customer['first_name']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>
                                    
                                    <div>
                                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($customer['last_name']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>
                                    
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>
                                    
                                    <div>
                                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($customer['phone']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>
                                    
                                    <div class="md:col-span-2">
                                        <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($customer['address'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>
                                    
                                    <div>
                                        <label for="fitness_goal" class="block text-sm font-medium text-gray-700 mb-1">Fitness Goal</label>
                                        <select id="fitness_goal" name="fitness_goal" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="weight_loss" <?php echo ($customer['fitness_goal'] == 'weight_loss') ? 'selected' : ''; ?>>Weight Loss</option>
                                            <option value="muscle_gain" <?php echo ($customer['fitness_goal'] == 'muscle_gain') ? 'selected' : ''; ?>>Muscle Gain</option>
                                            <option value="general_fitness" <?php echo ($customer['fitness_goal'] == 'general_fitness') ? 'selected' : ''; ?>>General Fitness</option>
                                            <option value="strength_training" <?php echo ($customer['fitness_goal'] == 'strength_training') ? 'selected' : ''; ?>>Strength Training</option>
                                            <option value="endurance" <?php echo ($customer['fitness_goal'] == 'endurance') ? 'selected' : ''; ?>>Endurance</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label for="weight" class="block text-sm font-medium text-gray-700 mb-1">Weight (kg)</label>
                                        <input type="number" step="0.01" id="weight" name="weight" value="<?php echo htmlspecialchars($customer['weight'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>
                                </div>
                                
                                <h4 class="text-md font-medium text-gray-800 mb-3">Emergency Contact</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label for="emergency_contact_name" class="block text-sm font-medium text-gray-700 mb-1">Contact Name</label>
                                        <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo htmlspecialchars($customer['emergency_contact_name'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>
                                    
                                    <div>
                                        <label for="emergency_contact_phone" class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
                                        <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" value="<?php echo htmlspecialchars($customer['emergency_contact_phone'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>
                                </div>
                                
                                <div class="mb-6">
                                    <label for="health_conditions" class="block text-sm font-medium text-gray-700 mb-1">Health Conditions</label>
                                    <textarea id="health_conditions" name="health_conditions" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"><?php echo htmlspecialchars($customer['health_conditions'] ?? ''); ?></textarea>
                                    <p class="mt-1 text-sm text-gray-500">Please list any health conditions, injuries, or medications that our staff should be aware of.</p>
                                </div>
                                
                                <div class="flex justify-end">
                                    <button type="submit" name="update_profile" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-opacity-90 transition-colors">
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Change Password Form -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">Change Password</h3>
                        </div>
                        
                        <div class="p-6">
                            <form action="" method="POST">
                                <div class="space-y-4">
                                    <div>
                                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password *</label>
                                        <input type="password" id="current_password" name="current_password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>
                                    
                                    <div>
                                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password *</label>
                                        <input type="password" id="new_password" name="new_password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>
                                    
                                    <div>
                                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password *</label>
                                        <input type="password" id="confirm_password" name="confirm_password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>
                                </div>
                                
                                <div class="mt-6 flex justify-end">
                                    <button type="submit" name="change_password" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-opacity-90 transition-colors">
                                        Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- JavaScript for Profile Image Functionality -->
    <script>
        // DOM Elements
        const profileImageContainer = document.getElementById('profileImageContainer');
        const profileImageModal = document.getElementById('profileImageModal');
        const closeModal = document.getElementById('closeModal');
        const profileImageForm = document.getElementById('profileImageForm');
        const profileImage = document.getElementById('profile_image');
        const imagePreview = document.getElementById('imagePreview');
        const previewImage = document.getElementById('previewImage');
        
        // Open modal when clicking on profile image
        profileImageContainer.addEventListener('click', function() {
            profileImageModal.classList.remove('hidden');
        });
        
        // Close modal when clicking on close button
        closeModal.addEventListener('click', function() {
            profileImageModal.classList.add('hidden');
            // Reset form and preview
            profileImageForm.reset();
            imagePreview.classList.add('hidden');
        });
        
        // Close modal when clicking outside of it
        profileImageModal.addEventListener('click', function(e) {
            if (e.target === profileImageModal) {
                profileImageModal.classList.add('hidden');
                // Reset form and preview
                profileImageForm.reset();
                imagePreview.classList.add('hidden');
            }
        });
        
        // Preview image before upload
        profileImage.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    imagePreview.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            } else {
                imagePreview.classList.add('hidden');
            }
        });
    </script>
</body>
</html>