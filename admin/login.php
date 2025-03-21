<?php
// Start session
session_start();

// Include database connection
require_once '../includes/db_connect.php';

// Set default role based on URL parameter
if (isset($_GET['role']) && $_GET['role'] === 'super_admin') {
  $_SESSION['default_role'] = 'super_admin';
} elseif (isset($_GET['role']) && $_GET['role'] === 'branch_admin') {
  $_SESSION['default_role'] = 'branch_admin';
} else {
  $_SESSION['default_role'] = '';
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Get form data and sanitize
  $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
  $password = $_POST['password'];
  $role = isset($_POST['role']) ? $_POST['role'] : (isset($_SESSION['default_role']) ? $_SESSION['default_role'] : '');
  $branch = isset($_POST['branch']) ? $_POST['branch'] : '';
  $remember = isset($_POST['remember']) ? 1 : 0;
  
  // Validate inputs
  $errors = [];
  
  if (empty($email)) {
    $errors[] = "Email is required";
  }
  
  if (empty($password)) {
    $errors[] = "Password is required";
  }
  
  // If no errors, proceed with login
  if (empty($errors)) {
    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
      $user = $result->fetch_assoc();
      
      // Verify password
      if (password_verify($password, $user['password'])) {
        // Check if role matches (if specified)
        if (!empty($role) && $role !== $user['role']) {
          $errors[] = "You don't have access to this role. Please select the correct role.";
        } else {
          // Set session variables
          $_SESSION['user_id'] = $user['id'];
          $_SESSION['user_name'] = $user['name'];
          $_SESSION['user_email'] = $user['email'];
          $_SESSION['user_role'] = $user['role'];
          
          // If branch is selected, store it in session
          if (!empty($branch)) {
            $_SESSION['selected_branch'] = $branch;
          }
          
          // If remember me is checked, set cookies
          if ($remember) {
            $token = bin2hex(random_bytes(32));
            
            // Store token in database
            $stmt = $conn->prepare("UPDATE admins SET remember_token = ? WHERE id = ?");
            $stmt->bind_param("si", $token, $user['id']);
            $stmt->execute();
            
            // Set cookies for 30 days
            setcookie("remember_user", $user['id'], time() + (86400 * 30), "/");
            setcookie("remember_token", $token, time() + (86400 * 30), "/");
          }
          
          // Store the base path in a session variable
          $_SESSION['base_path'] = '/multigym';
          
          // Redirect to dashboard based on role
          header("Location: dashboard.php");
          exit();
        }
      } else {
        $errors[] = "Invalid password";
      }
    } else {
      $errors[] = "Email not found";
    }
    
    $stmt->close();
  }
  
  // If there are errors, store them in session
  if (!empty($errors)) {
    $_SESSION['login_errors'] = $errors;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gym Admin Portal</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
  <div class="container">
      <div class="left-panel">
          <div class="content">
              <h1>Management is<br>at your fingertips</h1>
              <p>Take control of your gym branches with our powerful admin platform for the largest network of fitness centers.</p>
          </div>
          <div class="illustration">
              <img src="../assets/images/gym-illustration.png" alt="Gym Management Illustration">
          </div>
      </div>
      <div class="right-panel">
          <div class="form-container">
              <h2>Gym Admin Portal</h2>
              <p class="subtitle">Sign in to manage your branches</p>
              
              <?php if (isset($_SESSION['login_errors'])): ?>
                  <div class="error-message">
                      <ul>
                          <?php foreach ($_SESSION['login_errors'] as $error): ?>
                              <li><?php echo $error; ?></li>
                          <?php endforeach; ?>
                      </ul>
                  </div>
                  <?php unset($_SESSION['login_errors']); ?>
              <?php endif; ?>
              
              <form action="login.php" method="post">
                  <div class="form-group">
                      <label for="role">Select Role</label>
                      <select id="role" name="role" required>
                          <option value="">Select your role</option>
                          <option value="super_admin" <?php echo (isset($_SESSION['default_role']) && $_SESSION['default_role'] === 'super_admin') ? 'selected' : ''; ?>>Super Admin</option>
                          <option value="branch_admin" <?php echo (isset($_SESSION['default_role']) && $_SESSION['default_role'] === 'branch_admin') ? 'selected' : ''; ?>>Branch Admin</option>
                      </select>
                  </div>
                  
                  <div class="form-group">
                      <label for="email">Email</label>
                      <input type="email" id="email" name="email" required>
                  </div>
                  
                  <div class="form-group">
                      <label for="password">Password</label>
                      <div class="password-container">
                          <input type="password" id="password" name="password" required>
                          <span class="toggle-password" onclick="togglePassword()">
                              <i class="fas fa-eye"></i>
                          </span>
                      </div>
                  </div>
                  
                  <div class="branch-select form-group" id="branchSelect" style="display: none;">
                      <label for="branch">Select Branch</label>
                      <select id="branch" name="branch">
                          <option value="">Select your branch</option>
                          <?php
                          // Get all branches
                          $stmt = $conn->prepare("SELECT id, name FROM branches");
                          $stmt->execute();
                          $branches = $stmt->get_result();
                          
                          while ($branch = $branches->fetch_assoc()): 
                          ?>
                              <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                          <?php endwhile; ?>
                      </select>
                  </div>
                  
                  <div class="form-group remember-me">
                      <label class="checkbox-container">
                          <input type="checkbox" name="remember">
                          <span class="checkmark"></span>
                          <span class="checkbox-text">Remember me</span>
                      </label>
                      <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                  </div>
                  
                  <div class="form-group terms">
                      <p>By proceeding, I agree to Gym's <a href="../public/terms.php">Terms of Use</a> and acknowledge that I have read the <a href="../public/privacy.php">Privacy Policy</a>.</p>
                  </div>
                  
                  <button type="submit" class="sign-in-btn">Sign in</button>
              </form>
              
              <p class="register-link">Back to <a href="../public/index.php">Main Portal</a></p>
              
              <div style="margin-top: 20px; text-align: center;">
                  <a href="fix_passwords.php" style="color: #777; font-size: 12px;">Fix Admin Passwords</a>
              </div>
          </div>
      </div>
  </div>

  <script>
      // Toggle password visibility
      function togglePassword() {
          const passwordInput = document.getElementById("password");
          const toggleIcon = document.querySelector(".toggle-password i");
          
          if (passwordInput.type === "password") {
              passwordInput.type = "text";
              toggleIcon.classList.remove("fa-eye");
              toggleIcon.classList.add("fa-eye-slash");
          } else {
              passwordInput.type = "password";
              toggleIcon.classList.remove("fa-eye-slash");
              toggleIcon.classList.add("fa-eye");
          }
      }
      
      // Show/hide branch select based on role selection
      document.addEventListener("DOMContentLoaded", () => {
          const roleSelect = document.getElementById("role");
          const branchSelect = document.getElementById("branchSelect");
          
          // Initial check
          if (roleSelect.value === "branch_admin") {
              branchSelect.style.display = "block";
          } else {
              branchSelect.style.display = "none";
          }
          
          // Add event listener
          roleSelect.addEventListener("change", function() {
              if (this.value === "branch_admin") {
                  branchSelect.style.display = "block";
              } else {
                  branchSelect.style.display = "none";
              }
          });
      });
  </script>
</body>
</html>

