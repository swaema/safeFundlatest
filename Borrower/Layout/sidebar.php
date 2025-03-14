<div class="container-fluid">
  <div class="row">
    <div class="col-md-2 bg-dark p-0">
      <div class="sidebar-wrapper d-flex flex-column flex-shrink-0 pt-3 text-white bg-dark vh-100">
        <a href="../index.php" class="sidebar-brand d-flex align-items-center mb-3 px-3 text-white text-decoration-none">
          <i class="bi bi-person-workspace fs-4 me-2"></i>
          <span class="fs-4 fw-semibold">Borrower Menu</span>
        </a>
        
        <hr class="sidebar-divider opacity-50 mx-3">
        
        <ul class="nav nav-pills flex-column mb-auto px-2">
          <li class="nav-item mb-1">
            <a href="index.php" class="nav-link text-white hover-item">
              <i class="bi bi-house me-2"></i>
              <span>Dashboard</span>
            </a>
          </li>
          
          <!-- Loan Management Section -->
          <li class="sidebar-heading mt-3 mb-2 px-3">
            <span class="text-muted text-uppercase fs-7 fw-bold">Loan Management</span>
          </li>
          
          <li class="nav-item mb-1">
            <a href="LoanApplications.php" class="nav-link text-white hover-item">
              <i class="bi bi-file-text me-2"></i>
              <span>Loan Applications</span>
            </a>
          </li>
          
          <li class="nav-item mb-1">
            <a href="updatedLoans.php" class="nav-link text-white hover-item">
              <i class="bi bi-arrow-repeat me-2"></i>
              <span>Updated Loans</span>
            </a>
          </li>
          
          <li class="nav-item mb-1">
            <a href="haltedLoans.php" class="nav-link text-white hover-item">
              <i class="bi bi-pause-circle me-2"></i>
              <span>Halted Loans</span>
            </a>
          </li>
          
          <li class="nav-item mb-1">
            <a href="ActiveLoan.php" class="nav-link text-white hover-item">
              <i class="bi bi-check-circle me-2"></i>
              <span>Active Loans</span>
            </a>
          </li>
          
          <!-- Account Section -->
          <li class="sidebar-heading mt-3 mb-2 px-3">
            <span class="text-muted text-uppercase fs-7 fw-bold">Account</span>
          </li>
          
          <li class="nav-item mb-1">
            <a href="notifications.php" class="nav-link text-white hover-item position-relative">
              <i class="bi bi-bell me-2"></i>
              <span>Notifications</span>
              <span class="notification-badge"></span>
            </a>
          </li>
          
          <li class="nav-item mb-1">
            <a href="paymentHistory.php" class="nav-link text-white hover-item">
              <i class="bi bi-clock-history me-2"></i>
              <span>Payment History</span>
            </a>
          </li>
        </ul>
        
        <hr class="sidebar-divider opacity-50 mx-3">
        
        <div class="dropdown px-3 mb-3">
          <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
            id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="../<?php echo $_SESSION['user_image']; ?>" 
                 alt="<?php echo $_SESSION['user_name']; ?>"
                 onerror="this.onerror=null; this.src='../uploads/users/default/download.png';" 
                 width="32" height="32"
                 class="rounded-circle me-2">
            <strong><?php echo $_SESSION['user_name']; ?></strong>
          </a>
          <ul class="dropdown-menu dropdown-menu-dark shadow" aria-labelledby="dropdownUser1">
            <li><a class="dropdown-item py-2" href="profile.php">
              <i class="bi bi-person-circle me-2"></i>Profile
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item py-2" href="logout.php">
              <i class="bi bi-box-arrow-right me-2"></i>Sign out
            </a></li>
          </ul>
        </div>
      </div>
    </div>

<style>
.sidebar-wrapper {
  width: 280px;
  transition: all 0.3s ease;
}

.hover-item {
  border-radius: 6px;
  transition: all 0.2s ease;
  margin: 0 0.5rem;
}

.hover-item:hover {
  background-color: rgba(255, 255, 255, 0.1) !important;
  transform: translateX(5px);
}

.sidebar-heading {
  font-size: 0.75rem;
  letter-spacing: 0.5px;
}

.fs-7 {
  font-size: 0.75rem;
}

.nav-link {
  padding: 0.75rem 1rem;
}

.dropdown-item {
  transition: all 0.2s ease;
}

.dropdown-item:hover {
  background-color: rgba(255, 255, 255, 0.1);
  transform: translateX(5px);
}

.sidebar-divider {
  height: 0;
  border-top: 1px solid rgba(255, 255, 255, 0.15);
}

.dropdown-menu {
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 8px;
}

.rounded-circle {
  object-fit: cover;
  border: 2px solid rgba(255, 255, 255, 0.2);
}

.notification-badge {
  position: absolute;
  top: 50%;
  right: 1rem;
  transform: translateY(-50%);
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #dc3545;
  display: none;
}

/* Show notification badge when there are notifications */
.has-notifications .notification-badge {
  display: block;
}
</style>