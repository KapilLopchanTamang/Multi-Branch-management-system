<?php
// Main entry point - redirects to the dashboard
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gym Network - Dashboard Portal</title>
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
      body {
          font-family: "Arial", sans-serif;
          margin: 0;
          padding: 0;
          background-color: #FFE8E6;
      }
      
      .dashboard-container {
          min-height: 100vh;
          display: flex;
          flex-direction: column;
          align-items: center;
          padding: 40px 20px;
      }
      
      .dashboard-header {
          text-align: center;
          margin-bottom: 60px;
      }
      
      .dashboard-header h1 {
          font-size: 48px;
          color: #000;
          margin-bottom: 20px;
      }
      
      .dashboard-header p {
          font-size: 18px;
          color: #333;
          max-width: 800px;
          line-height: 1.6;
      }
      
      .dashboard-cards {
          display: flex;
          flex-wrap: wrap;
          justify-content: center;
          gap: 30px;
          max-width: 1200px;
          width: 100%;
      }
      
      .dashboard-card {
          background-color: #FFF;
          border-radius: 15px;
          box-shadow: 0 10px 30px rgba(0,0,0,0.1);
          padding: 40px 30px;
          width: 300px;
          text-align: center;
          transition: transform 0.3s, box-shadow 0.3s;
          display: flex;
          flex-direction: column;
          align-items: center;
      }
      
      .dashboard-card:hover {
          transform: translateY(-15px);
          box-shadow: 0 15px 40px rgba(0,0,0,0.15);
      }
      
      .card-icon {
          width: 80px;
          height: 80px;
          background-color: #FF6B45;
          color: white;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          margin-bottom: 25px;
          font-size: 32px;
      }
      
      .dashboard-card h2 {
          font-size: 24px;
          color: #000;
          margin-bottom: 15px;
      }
      
      .dashboard-card p {
          font-size: 16px;
          color: #555;
          margin-bottom: 25px;
          line-height: 1.5;
          flex-grow: 1;
      }
      
      .card-btn {
          display: inline-block;
          padding: 12px 30px;
          background-color: #000;
          color: #FFF;
          text-decoration: none;
          border-radius: 4px;
          font-weight: 600;
          transition: background-color 0.3s;
          width: 100%;
          box-sizing: border-box;
      }
      
      .card-btn:hover {
          background-color: #333;
      }
      
      .footer {
          margin-top: 60px;
          text-align: center;
          color: #555;
      }
      
      @media (max-width: 992px) {
          .dashboard-cards {
              gap: 20px;
          }
          
          .dashboard-card {
              width: calc(50% - 20px);
              padding: 30px 20px;
          }
      }
      
      @media (max-width: 768px) {
          .dashboard-header h1 {
              font-size: 36px;
          }
          
          .dashboard-card {
              width: 100%;
              max-width: 400px;
          }
      }
  </style>
</head>
<body>
  <div class="dashboard-container">
      <div class="dashboard-header">
          <h1>Gym Network Management System</h1>
          <p>Welcome to our multi-branch fitness network management system. Please select your role to access the appropriate portal.</p>
      </div>
      
      <div class="dashboard-cards">
          <div class="dashboard-card">
              <div class="card-icon">
                  <i class="fas fa-user-shield"></i>
              </div>
              <h2>Super Admin</h2>
              <p>Manage all gym branches, administrators, and global settings. Monitor performance across the entire network.</p>
              <a href="admin/login.php?role=super_admin" class="card-btn">Super Admin Login</a>
          </div>
          
          <div class="dashboard-card">
              <div class="card-icon">
                  <i class="fas fa-user-tie"></i>
              </div>
              <h2>Branch Admin</h2>
              <p>Manage your specific gym branch, including staff, members, classes, and equipment. View branch-specific reports.</p>
              <a href="admin/login.php?role=branch_admin" class="card-btn">Branch Admin Login</a>
          </div>
          
          <div class="dashboard-card">
              <div class="card-icon">
                  <i class="fas fa-user"></i>
              </div>
              <h2>Customer</h2>
              <p>Access your membership details, book classes, track your fitness progress, and manage your personal profile.</p>
              <a href="customer/login.php" class="card-btn">Customer Login</a>
          </div>
      </div>
      
      <div class="footer">
          <p>&copy; <?php echo date('Y'); ?> Gym Network. All rights reserved.</p>
      </div>
  </div>
</body>
</html>

