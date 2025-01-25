<?php
//Borrowers.php
session_start();
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/User.php';
require_once '../Classes/Lender.php';
if (!UserAuth::isAdminAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
}
if (isset($_GET['e'])) {
    $error = $_GET['e'];
}
if (isset($_POST['active'])) {
    $userId = $_POST['id'];
    User::activeUser($userId);
}
$errors = [];
$users = User::allByRole("borrower");
if (isset($_POST['suspend'])) {
    $userId = $_POST['id'];
    User::changeUserStatus($userId, "suspend");
}
?>

<?php
include_once('Layout/head.php');
include_once('Layout/sidebar.php');
?>

<style>
.dashboard-container {
    background-color: #f8f9fa;
    min-height: 100vh;
    padding: 2rem;
}

.page-header {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 2rem;
}

.card-borrower {
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-borrower:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.table-container {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.custom-table {
    margin: 0;
}

.custom-table thead th {
    background: #1e3c72;
    color: white;
    padding: 1rem;
    font-weight: 500;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 500;
}

.user-image {
    border-radius: 50%;
    width: 50px;
    height: 50px;
    object-fit: cover;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.action-btn {
    border-radius: 20px;
    padding: 0.4rem 1rem;
    transition: all 0.3s;
}

.modal-custom {
    border-radius: 15px;
}

.modal-custom .modal-header {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    border-radius: 15px 15px 0 0;
}

.document-card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.document-card:hover {
    background: #e9ecef;
}

.form-control {
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 0.75rem;
}

.form-control:focus {
    box-shadow: 0 0 0 0.2rem rgba(30, 60, 114, 0.25);
}
</style>

<div class="col-md-10 dashboard-container">
    <?php if (isset($error)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0">Borrowers Management</h2>
                <p class="text-light mb-0">View and manage all borrower accounts</p>
            </div>
            <div>
                <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#borrowerModal">
                    <i class="fas fa-plus me-2"></i>Add New Borrower
                </button>
            </div>
        </div>
    </div>

    <div class="table-container p-4">
        <table id="example" class="table custom-table table-hover">
            <thead>
                <tr>
                    <th>Borrower Details</th>
                    <th>Contact Information</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (is_array($users) && count($users) > 0): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img class="user-image me-3" 
                                         src="../<?php echo htmlspecialchars($user['image']); ?>"
                                         alt="User Image" 
                                         onerror="this.onerror=null; this.src='../uploads/users/default/download.png';">
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                        <div class="text-muted"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($user['email']); ?></div>
                                <div><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($user['address']); ?></div>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $user['status'] === 'Active' ? 'success' : 
                                    ($user['status'] === 'Inactive' ? 'warning' : 'danger'); ?> status-badge">
                                    <?php echo htmlspecialchars($user['status'] ?? 'Inactive'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-primary action-btn" data-bs-toggle="modal"
                                            data-bs-target="#exampleModal<?php echo $user['id'] ?>">
                                        <i class="fas fa-eye me-1"></i> View
                                    </button>
                                    <button type="button" class="btn btn-warning action-btn ms-2 btnProduct2"
                                            id="<?php echo htmlspecialchars($user['id']); ?>">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </button>
                                    <form method="post" action="" onsubmit="return myConfirm();" class="ms-2">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                        <button type="submit" class="btn btn-danger action-btn" name="suspend">
                                            <i class="fas fa-ban me-1"></i> Suspend
                                        </button>
                                    </form>
                                </div>

                                <!-- User Details Modal -->
                                <div class="modal fade" id="exampleModal<?php echo $user['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content modal-custom">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-user me-2"></i>Borrower Details
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6 mb-4">
                                                        <div class="card h-100">
                                                            <div class="card-body">
                                                                <h6 class="card-title text-primary">Personal Information</h6>
                                                                <div class="mb-3">
                                                                    <strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <strong>Phone:</strong> <?php echo htmlspecialchars($user['email']); ?>
                                                                </div>
                                                                <div>
                                                                    <strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 mb-4">
                                                        <div class="card h-100">
                                                            <div class="card-body">
                                                                <h6 class="card-title text-primary">Documents</h6>
                                                                <?php $documents = User::getDocument($user['id']); ?>
                                                                <?php if (!empty($documents)): ?>
                                                                    <?php foreach ($documents as $document): ?>
                                                                        <div class="document-card">
                                                                            <div class="d-flex justify-content-between align-items-center">
                                                                                <span><i class="fas fa-file me-2"></i>Document</span>
                                                                                <a href="../<?php echo htmlspecialchars($document['path']); ?>" 
                                                                                   class="btn btn-sm btn-primary" download>
                                                                                    <i class="fas fa-download me-1"></i>Download
                                                                                </a>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                <?php else: ?>
                                                                    <p class="text-muted">No documents available</p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Borrower Modal -->
<div class="modal fade" id="borrowerModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modal-custom">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>Add New Borrower
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="signupForm" action="../signup.php" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <input type="hidden" name="path" value="borrower">
                    <input type="hidden" name="role" value="borrower">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" pattern="^[a-zA-Z\s]+$" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="password" 
                                       pattern="^(?=.*[A-Z])(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,20}$" required>
                                <span class="input-group-text">
                                    <i class="bi bi-eye-fill" id="toggle-password" style="cursor: pointer;"></i>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" name="confirmPassword" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" class="form-control" name="mobile" pattern="^\d{5,15}$" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Profile Image</label>
                            <input type="file" class="form-control" name="profile_image" accept=".png,.jpg,.jpeg,.gif" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NIC (Front and Back)</label>
                            <input type="file" class="form-control" name="nic[]" multiple required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Utility Bills (At least 2)</label>
                            <input type="file" class="form-control" name="utility_bills[]" multiple required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Salary Statements (Last 6 Months)</label>
                            <input type="file" class="form-control" name="salary_statements[]" multiple required>
                        </div>
                    </div
                    <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" name="BorrowerSignUp" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>Create Borrower Account
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container for Notifications -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="liveToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body"></div>
    </div>
</div>

<script>
    // Password toggle functionality
    const togglePassword = document.querySelector("#toggle-password");
    const passwordField = document.querySelector("#password");

    togglePassword.addEventListener("click", function() {
        const type = passwordField.getAttribute("type") === "password" ? "text" : "password";
        passwordField.setAttribute("type", type);
        this.classList.toggle("bi-eye-slash-fill");
    });

    // Form validation
    (() => {
        'use strict'
        const forms = document.querySelectorAll('.needs-validation')
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()

    // DataTable initialization with enhanced features
    $(document).ready(function() {
        $('#example').DataTable({
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            pageLength: 10,
            order: [[0, 'asc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search borrowers..."
            }
        });

        // Initialize all tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });

    // Confirmation dialog
    function myConfirm() {
        return Swal.fire({
            title: 'Are you sure?',
            text: "This will suspend the borrower's account. They won't be able to access the system until reactivated.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, suspend it!'
        }).then((result) => {
            return result.isConfirmed;
        });
    }

    // Function to show toast notifications
    function showToast(message, type = 'success') {
        const toast = document.getElementById('liveToast');
        const toastBody = toast.querySelector('.toast-body');
        
        toast.classList.remove('bg-success', 'bg-danger');
        toast.classList.add(`bg-${type}`);
        toastBody.textContent = message;
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }

    // File input validation
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            const invalidFiles = files.filter(file => file.size > maxSize);
            
            if (invalidFiles.length > 0) {
                showToast('Some files are too large. Maximum size is 5MB per file.', 'danger');
                e.target.value = '';
            }
        });
    });
</script>

<?php include_once('Layout/footer.php'); ?>