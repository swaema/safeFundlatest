<?php
//Lenders.php
session_start();
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/User.php';
require_once '../Classes/Lender.php';

if (!UserAuth::isAdminAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
}

$error = isset($_GET['e']) ? $_GET['e'] : '';
$success = isset($_GET['s']) ? $_GET['s'] : '';

// Handle user updates
// Add this function at the top of your file, after the includes
function logError($message) {
    $logFile = __DIR__ . '/../error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

if (isset($_POST['updateUser'])) {
    try {
        $userId = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
        $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $mobile = filter_var($_POST['mobile'], FILTER_SANITIZE_STRING);
        $address = filter_var($_POST['address'], FILTER_SANITIZE_STRING);

        // Log the update attempt
        logError("Attempting to update user ID: " . $userId);
        logError("Update data: " . json_encode([
            'name' => $name,
            'email' => $email,
            'mobile' => $mobile,
            'address' => $address
        ]));

        try {
            $updated = User::updateUser($userId, [
                'name' => $name,
                'email' => $email,
                'mobile' => $mobile,
                'address' => $address
            ]);
            logError("Update result: " . var_export($updated, true));
        } catch (Throwable $e) {
            logError("Exception in updateUser method: " . $e->getMessage());
            logError("Exception trace: " . $e->getTraceAsString());
            throw $e;
        }

        if ($updated) {
            $_SESSION['success'] = "User updated successfully";
            logError("Update successful, redirecting...");
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            logError("Update returned false");
            throw new Exception("Failed to update user");
        }
    } catch (Exception $e) {
        logError("Error in update process: " . $e->getMessage());
        logError("Stack trace: " . $e->getTraceAsString());
        $_SESSION['error'] = "Error updating user: " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (Throwable $e) {
        logError("Critical error in update process: " . $e->getMessage());
        logError("Stack trace: " . $e->getTraceAsString());
        $_SESSION['error'] = "Critical error updating user";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

if (isset($_POST['suspend'])) {
    $userId = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    if (User::changeUserStatus($userId, "suspend")) {
        header('Location: Lenders.php?s=User suspended successfully');
        exit;
    }
}

$users = User::allByRole("lender");
?>

<?php include_once('Layout/head.php'); ?>
<?php include_once('Layout/sidebar.php'); ?>

<style>
    .dashboard-container {
        background: linear-gradient(135deg, #f5f7fa 0%, #eef2f7 100%);
        min-height: 100vh;
        padding: 2rem;
    }

    .stats-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        transition: transform 0.2s;
    }

    .stats-card:hover {
        transform: translateY(-5px);
    }

    .page-header {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        padding: 2rem;
        border-radius: 15px;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .table-container {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    }

    .custom-table th {
        background: #1e3c72;
        color: white;
        padding: 1rem;
        font-weight: 500;
    }

    .custom-table td {
        vertical-align: middle;
        padding: 1rem;
    }

    .modal-custom .modal-header {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
        border-radius: 15px 15px 0 0;
    }

    .modal-custom .modal-content {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .action-btn {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        transition: all 0.3s;
        width: 120px;
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 500;
    }

    .user-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 1rem;
    }

    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #fff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .form-control {
        border-radius: 10px;
        padding: 0.75rem 1rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        border: none;
    }

    .dropdown-menu {
        border-radius: 10px;
        border: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
</style>

<div class="col-md-10 dashboard-container">
    <?php if ($success || $error): ?>
        <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success ?: $error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <h2 class="mb-0">Lenders Management</h2>
        <p class="text-light mb-0 mt-2">Manage and monitor all lender accounts</p>
        
        <!-- Quick Stats -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <h3 class="text-primary"><?php echo count($users); ?></h3>
                    <p class="text-muted mb-0">Total Lenders</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                <h3 class="text-success"><?php echo count(array_filter($users, fn($u) => strtolower($u['status']) === 'active')); ?></h3>
                    <p class="text-muted mb-0">Active Lenders</p>
                </div>
            </div>
            <!-- Add more stats cards as needed -->
        </div>
    </div>

    <div class="table-container">
        <table id="example" class="table custom-table table-hover">
            <thead>
                <tr>
                    <th>Lender</th>
                    <th>Contact Details</th>
                    <th>Status</th>
                    <th>Documents</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (is_array($users) && count($users) > 0): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img class="user-avatar me-3" 
                                         src="../<?php echo htmlspecialchars($user['image']); ?>"
                                         alt="<?php echo htmlspecialchars($user['name']); ?>" 
                                         onerror="this.src='../uploads/users/default/download.png';">
                                    <div>
                                        <strong class="d-block"><?php echo htmlspecialchars($user['name']); ?></strong>
                                        <small class="text-muted">ID: <?php echo htmlspecialchars($user['id']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?></div>
                                <div><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($user['mobile']); ?></div>
                                <div><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($user['address']); ?></div>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $user['status'] === 'Active' ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo htmlspecialchars($user['status'] ?? 'Inactive'); ?>
                                </span>
                            </td>
                            <td>
                                <?php $documents = User::getDocument($user['id']); ?>
                                <?php if (!empty($documents)): ?>
                                    <button class="btn btn-outline-primary btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#docsModal<?php echo $user['id']; ?>">
                                        <i class="fas fa-file me-2"></i>View Documents
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">No documents</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button class="action-btn btn btn-outline-primary" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#detailsModal<?php echo $user['id']; ?>">
                                        <i class="fas fa-eye me-2"></i>View
                                    </button>
                                    <button class="action-btn btn btn-outline-warning ms-2" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal<?php echo $user['id']; ?>">
                                        <i class="fas fa-edit me-2"></i>Edit
                                    </button>
                                    <?php if ($user['status'] === 'Active'): ?>
                                        <form method="post" action="" class="d-inline ms-2" onsubmit="return confirm('Are you sure?');">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="suspend" class="action-btn btn btn-outline-danger">
                                                <i class="fas fa-ban me-2"></i>Suspend
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <!-- Details Modal -->
                        <div class="modal fade" id="detailsModal<?php echo $user['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content modal-custom">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="fas fa-user-circle me-2"></i>Lender Profile
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <img src="../<?php echo htmlspecialchars($user['image']); ?>"
                                                     class="img-fluid rounded"
                                                     alt="Profile Image"
                                                     onerror="this.src='../uploads/users/default/download.png';">
                                            </div>
                                            <div class="col-md-8">
                                                <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                                                <p class="text-muted mb-4">Lender Account</p>
                                                
                                                <div class="row g-3">
                                                    <div class="col-sm-6">
                                                        <label class="text-muted">Email</label>
                                                        <p class="mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label class="text-muted">Mobile</label>
                                                        <p class="mb-0"><?php echo htmlspecialchars($user['mobile']); ?></p>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="text-muted">Address</label>
                                                        <p class="mb-0"><?php echo htmlspecialchars($user['address']); ?></p>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label class="text-muted">Status</label>
                                                        <p class="mb-0">
                                                            <span class="badge <?php echo $user['status'] === 'Active' ? 'bg-success' : 'bg-warning'; ?>">
                                                                <?php echo htmlspecialchars($user['status'] ?? 'Inactive'); ?>
                                                            </span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?php echo $user['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content modal-custom">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="fas fa-edit me-2"></i>Edit Lender
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form action="" method="post">
                                        <div class="modal-body">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Name</label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       name="name" 
                                                       value="<?php echo htmlspecialchars($user['name']); ?>" 
                                                       required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" 
                                                       class="form-control" 
                                                       name="email"value="<?php echo htmlspecialchars($user['email']); ?>" 
                                                       required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Mobile</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                                    <input type="text" 
                                                           class="form-control" 
                                                           name="mobile" 
                                                           value="<?php echo htmlspecialchars($user['mobile']); ?>" 
                                                           required>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Address</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                                    <textarea class="form-control" 
                                                              name="address" 
                                                              required
                                                              rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-top-0">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="updateUser" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Documents Modal -->
                        <?php if (!empty($documents)): ?>
                            <div class="modal fade" id="docsModal<?php echo $user['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content modal-custom">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="fas fa-file-alt me-2"></i>User Documents
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="list-group">
                                                <?php foreach ($documents as $document): ?>
                                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <i class="fas fa-file-pdf me-2 text-danger"></i>
                                                            <?php echo htmlspecialchars(basename($document['path'])); ?>
                                                        </div>
                                                        <a href="../<?php echo htmlspecialchars($document['path']); ?>" 
                                                           class="btn btn-sm btn-primary"
                                                           download>
                                                            <i class="fas fa-download me-2"></i>Download
                                                        </a>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once('Layout/footer.php'); ?>

<script>
$(document).ready(function() {
    // Initialize DataTable with enhanced features
    $('#example').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 10,
        "responsive": true,
        "language": {
            "search": "<i class='fas fa-search'></i> Search:",
            "paginate": {
                "first": '<i class="fas fa-angle-double-left"></i>',
                "previous": '<i class="fas fa-angle-left"></i>',
                "next": '<i class="fas fa-angle-right"></i>',
                "last": '<i class="fas fa-angle-double-right"></i>'
            }
        },
        "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        "drawCallback": function() {
            $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
        }
    });

    // Initialize all tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            boundary: document.body
        });
    });

    // Enhanced toastr notifications
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "3000"
    };

    <?php if (isset($_GET['s'])): ?>
        toastr.success("<?php echo htmlspecialchars($_GET['s']); ?>", "Success!");
    <?php endif; ?>

    <?php if (isset($_GET['e'])): ?>
        toastr.error("<?php echo htmlspecialchars($_GET['e']); ?>", "Error!");
    <?php endif; ?>

    // Smooth animation for modals
    $('.modal').on('show.bs.modal', function() {
        $(this).find('.modal-content')
            .css({
                transform: 'scale(0.7)',
                opacity: 0
            })
            .animate({
                transform: 'scale(1)',
                opacity: 1
            }, 300);
    });
});

// Enhanced confirmation dialog
function myConfirm(message = "Are you sure you want to perform this action?") {
    return new Promise((resolve) => {
        const confirmed = window.confirm(message);
        resolve(confirmed);
    });
}

// Image preview functionality
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Add loading state to buttons
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        const submitButton = this.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        }
    });
});
</script>

</div>
<?php include_once('Layout/footer.php'); ?>