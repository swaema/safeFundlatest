<?php
//LoanApplication.php
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';
require_once '../vendor/autoload.php';

session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add this right after session_start()
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('POST data received: ' . print_r($_POST, true));
}

// Load configuration
$config = require '../config.php';

// Initialize Stripe
\Stripe\Stripe::setApiKey($config['stripe_secret_key']);

if (!UserAuth::isAdminAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
    exit;
}

$error = isset($_GET['e']) ? $_GET['e'] : '';

// Handle interest rate update
if (isset($_POST['rate'])) {
    try {
        $rate = filter_var($_POST['rateValue'], FILTER_VALIDATE_FLOAT);
        $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        
        if ($rate === false || $id === false || !$email) {
            throw new Exception("Invalid input data");
        }
        
        $error = Loan::interstRate($id, $rate, $email);
    } catch (Exception $e) {
        $error = "Error updating interest rate: " . $e->getMessage();
    }
}

// Handle loan deletion
if (isset($_POST['deleteLoan'])) {
    try {
        $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
        
        if ($id === false || !$email || $amount === false) {
            throw new Exception("Invalid input data");
        }
        
        $check = Loan::delete($id, $amount, $email);
        if ($check) {
            $error = "Loan Rejected Successfully";
        }
    } catch (Exception $e) {
        $error = "Error rejecting loan: " . $e->getMessage();
    }
}

// Handle loan acceptance and payment
if (isset($_POST['accept'])) {
    try {
        // Validate inputs
        $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
        $stripeAccountId = filter_var($_POST['stripe_account_id'], FILTER_SANITIZE_STRING);

        if ($id === false || !$email || $amount === false || empty($stripeAccountId)) {
            throw new Exception("Missing or invalid input data");
        }

        $db = Database::getConnection();
        $db->begin_transaction();

        try {
            // Create Stripe transfer
            $transfer = \Stripe\Transfer::create([
                'amount' => (int)($amount * 100),
                'currency' => 'usd',
                'destination' => $stripeAccountId,
                'description' => "Loan disbursement for loan #$id",
                'metadata' => [
                    'loan_id' => $id,
                    'borrower_email' => $email
                ]
            ]);

            // Record transfer
            $sql = "INSERT INTO loan_transfers (loan_id, amount, stripe_transfer_id, status) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database prepare error: " . $db->error);
            }
            
            $stmt->bind_param("idss", $id, $amount, $transfer->id, $transfer->status);
            if (!$stmt->execute()) {
                throw new Exception("Database execution error: " . $stmt->error);
            }

            // Update loan status
            $check = Loan::AcceptLoanbyAdmin($id, $amount, $email);
            if (!$check) {
                throw new Exception("Failed to update loan status");
            }

            $db->commit();

            // Send notification
            $subject = "Loan Approved and Funds Transferred";
            $message = "Your loan application has been approved and $" . number_format($amount, 2) . " has been transferred to your account.";
            mail($email, $subject, $message);

            $error = "Loan Accepted and Amount Transferred Successfully. Transfer ID: " . $transfer->id;

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("Stripe Error: " . $e->getMessage());
        $error = "Payment Error: " . $e->getMessage();
    } catch (Exception $e) {
        error_log("General Error: " . $e->getMessage());
        $error = "Error: " . $e->getMessage();
    }
}

// Ensure transfer table exists
$db = Database::getConnection();
$db->query("CREATE TABLE IF NOT EXISTS loan_transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    stripe_transfer_id VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id)
)");

$id = $_SESSION['user_id'];
$loans = Loan::allLoans("Pending");

include_once('Layout/head.php');
include_once('Layout/sidebar.php');
?>
<style>
    :root {
        --primary-color: #006d72;
        --secondary-color: #142127;
        --success-color: #28a745;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
        --light-bg: #f8f9fa;
    }

    .dashboard-container {
        background-color: var(--light-bg);
        min-height: 100vh;
        padding: 2rem;
    }

    .dashboard-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, #00959c 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .loan-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .table-custom {
        background: white;
        border-radius: 10px;
    }

    .table-custom thead th {
        background: var(--secondary-color);
        color: white;
        padding: 1rem;
        border: none;
    }

    .applicant-card {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .applicant-image {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .amount-display {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--primary-color);
    }

    .action-btn {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        transition: all 0.3s ease;
    }

    .action-btn:hover {
        transform: translateY(-2px);
    }

    .modal-custom {
        border-radius: 15px;
    }

    .modal-custom .modal-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, #00959c 100%);
        color: white;
        border-radius: 15px 15px 0 0;
        padding: 1.5rem;
    }

    .modal-custom .modal-content {
        border: none;
        border-radius: 15px;
    }

    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        height: 100%;
    }

    .detail-row {
        padding: 0.8rem;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 500;
    }

    .dropdown-menu {
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        padding: 0.5rem;
    }

    .dropdown-item {
        border-radius: 5px;
        padding: 0.5rem 1rem;
        margin: 0.2rem 0;
        transition: all 0.2s ease;
    }

    .dropdown-item:hover {
        background-color: var(--light-bg);
    }

    .alert {
        border-radius: 10px;
        border: none;
    }

    .payment-form .form-control {
        border-radius: 8px;
        padding: 0.75rem;
    }

    .payment-alert {
        border-left: 4px solid var(--primary-color);
        background-color: rgba(0,109,114,0.1);
        padding: 1rem;
        border-radius: 0 8px 8px 0;
    }
</style>

<div class="col-md-10 dashboard-container">
    <?php if (isset($error) && !empty(trim($error))): ?>
        <div class="alert <?php echo strpos($error, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="dashboard-header">
        <h2 class="h3 mb-2">Loan Applications Dashboard</h2>
        <p class="mb-0">Review and manage pending loan applications</p>
    </div>

    <div class="loan-card">
        <div class="table-responsive">
            <table id="example" class="table table-hover table-custom align-middle">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Contact Information</th>
                        <th>Loan Details</th>
                        <th>Employment</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (is_array($loans) && count($loans) > 0): ?>
                        <?php foreach ($loans as $loan): 
                            $totalAmount = $loan['loanAmount'] + 
                                (($loan['noOfInstallments'] / 12) * $loan['loanAmount'] * ($loan['interstRate'] / 100)) + 
                                ($loan['loanAmount'] * (2 / 100));
                        ?>
                            <tr>
                                <td>
                                    <div class="applicant-card">
                                        <img class="applicant-image" 
                                             src="../<?php echo htmlspecialchars($loan['image']); ?>"
                                             alt="<?php echo htmlspecialchars($loan['name']); ?>"
                                             onerror="this.src='../uploads/users/default/download.png'">
                                        <div>
                                            <strong><?php echo htmlspecialchars($loan['name']); ?></strong>
                                            <div class="text-muted small">ID: <?php echo htmlspecialchars($loan['id']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($loan['mobile']); ?></div>
                                    <div><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($loan['email']); ?></div>
                                    <div><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($loan['address']); ?></div>
                                </td>
                                <td>
                                    <div class="amount-display">$<?php echo number_format($totalAmount, 2); ?></div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($loan['noOfInstallments']); ?> installments</div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($loan['interstRate']); ?>% interest</div>
                                </td>
                                <td>
                                    <span class="badge bg-info rounded-pill"><?php echo htmlspecialchars($loan['employeementTenure']); ?> years</span>
                                </td>
                                <td><?php echo htmlspecialchars($loan['loanPurpose']); ?></td>
                                <td>
                                    <span class="status-badge bg-warning">Pending</span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-primary btn-sm action-btn dropdown-toggle" 
                                                type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-cog me-1"></i> Manage
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button class="dropdown-item" data-bs-toggle="modal" 
                                                        data-bs-target="#loan-<?php echo htmlspecialchars($loan['id']); ?>">
                                                    <i class="fas fa-eye me-2"></i> View Details
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item" 
                                                        onclick="showEditInterestModal('<?php echo htmlspecialchars($loan['id']); ?>')">
                                                    <i class="fas fa-percent me-2"></i> Edit Interest Rate
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item text-success" 
                                                        onclick="showPaymentModal('<?php echo htmlspecialchars($loan['id']); ?>', 
                                                                               '<?php echo htmlspecialchars($loan['email']); ?>', 
                                                                               '<?php echo $loan['loanAmount']; ?>', 
                                                                               'acct_1Qiy5GL2AYu9PwEY')">
                                                    <i class="fas fa-check-circle me-2"></i> Approve Loan
                                                </button>
                                            </li>
                                            <li>
                                                <form method="post" action="" onsubmit="return confirmReject();" class="dropdown-item">
                                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($loan['id']); ?>">
                                                    <input type="hidden" name="amount" value="<?php echo $loan['loanAmount']; ?>">
                                                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($loan['email']); ?>">
                                                    <button type="submit" class="btn btn-link text-danger p-0 w-100 text-start" name="deleteLoan">
                                                        <i class="fas fa-times-circle me-2"></i> Reject Loan
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Loan Details Modal -->
                                    <div class="modal fade modal-custom" id="loan-<?php echo htmlspecialchars($loan['id']); ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-file-invoice me-2"></i> 
                                                        Loan Application Details
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="row g-4">
                                                        <div class="col-md-6">
                                                            <div class="stat-card">
                                                                <h6 class="text-primary mb-4">Applicant Information</h6>
                                                                <div class="detail-row">
                                                                    <span>Name</span>
                                                                    <strong><?php echo htmlspecialchars($loan['name']); ?></strong>
                                                                </div>
                                                                <div class="detail-row">
                                                                    <span>Contact</span>
                                                                    <strong><?php echo htmlspecialchars($loan['mobile']); ?></strong>
                                                                </div>
                                                                <div class="detail-row">
                                                                    <span>Email</span>
                                                                    <strong><?php echo htmlspecialchars($loan['email']); ?></strong>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="stat-card">
                                                                <h6 class="text-primary mb-4">Loan Information</h6>
                                                                <div class="detail-row">
                                                                    <span>Principal Amount</span>
                                                                    <strong>$<?php echo number_format($loan['loanAmount'], 2); ?></strong>
                                                                </div>
                                                                <div class="detail-row">
                                                                    <span>Interest Rate</span>
                                                                    <strong><?php echo $loan['interstRate']; ?>%</strong>
                                                                </div>
                                                                <div class="detail-row">
                                                                    <span>Total Amount</span>
                                                                    <strong>$<?php echo number_format($totalAmount, 2); ?></strong>
                                                                </div>
                                                                <div class="detail-row">
                                                                    <span>Monthly Payment</span>
                                                                    <strong>$<?php echo number_format($totalAmount / $loan['noOfInstallments'], 2); ?></strong>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Edit Interest Rate Modal -->
                                    <div class="modal fade modal-custom" id="editInterestRate-<?php echo htmlspecialchars($loan['id']) ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-percent me-2"></i>
                                                        Edit Interest Rate
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <form action="" method="post" class="interest-rate-form">
                                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($loan['id']); ?>">
                                                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($loan['email']); ?>">
                                                        
                                                        <div class="mb-4">
                                                            <label class="form-label">Interest Rate (%)</label>
                                                            <input type="number" 
                                                            class="form-control" 
                                                                   name="rateValue" 
                                                                   step="0.01" 
                                                                   min="0" 
                                                                   max="100" 
                                                                   value="<?php echo htmlspecialchars($loan['interstRate']); ?>" 
                                                                   required>
                                                        </div>
                                                        
                                                        <div class="text-end">
                                                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="rate" class="btn btn-primary">Update Rate</button>
                                                        </div>
                                                    </form>
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
</div>

<!-- Payment Confirmation Modal -->
<div class="modal fade modal-custom" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">
                    <i class="fas fa-money-check-alt me-2"></i>
                    Confirm Loan Disbursement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="payment-form" method="post" action="" class="payment-form">
                    <input type="hidden" name="id" id="loan-id">
                    <input type="hidden" name="email" id="loan-email">
                    <input type="hidden" name="amount" id="loan-amount">
                    <input type="hidden" name="stripe_account_id" id="stripe-account-id">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Loan Amount</label>
                        <div class="form-control bg-light" id="display-amount"></div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Recipient Email</label>
                        <div class="form-control bg-light" id="display-email"></div>
                    </div>

                    <div class="payment-alert mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle me-2 text-primary"></i>
                            <div>
                                By confirming, you will initiate the transfer of funds to the borrower's account.
                                Please verify all details before proceeding.
                            </div>
                        </div>
                    </div>

                    <div id="payment-error" class="alert alert-danger" style="display: none;"></div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="accept" class="btn btn-primary action-btn">
                            <i class="fas fa-check me-2"></i>
                            Confirm and Transfer Funds
                        </button>
                        <button type="button" class="btn btn-light action-btn" data-bs-dismiss="modal">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once('Layout/footer.php'); ?>



<script>
// Initialize DataTable
$(document).ready(function () {
    $('#example').DataTable({
        order: [[6, 'desc']], // Sort by application date by default
        pageLength: 10,
        responsive: true
    });

    // Initialize all tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Function to confirm loan rejection
function confirmReject() {
    return confirm("Are you sure you want to reject this loan application?");
}

// Function to show edit interest rate modal
function showEditInterestModal(loanId) {
    const modalElement = document.getElementById(`editInterestRate-${loanId}`);
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
}

// Function to show payment modal
function showPaymentModal(id, email, amount, stripeAccountId) {
    // Set form values
    document.getElementById('loan-id').value = id;
    document.getElementById('loan-email').value = email;
    document.getElementById('loan-amount').value = amount;
    document.getElementById('stripe-account-id').value = stripeAccountId;
    
    // Set display values
    document.getElementById('display-amount').textContent = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
    document.getElementById('display-email').textContent = email;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
}

// Handle payment form submission
document.getElementById('payment-form').addEventListener('submit', async function(event) {
    event.preventDefault();
    
    const result = await Swal.fire({
        title: 'Confirm Loan Approval',
        html: `
            <div class="text-left">
                <p><strong>Amount:</strong> $${document.getElementById('display-amount').textContent}</p>
                <p><strong>Recipient:</strong> ${document.getElementById('display-email').textContent}</p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Yes, approve loan',
        cancelButtonText: 'Cancel'
    });

    if (!result.isConfirmed) {
        return;
    }

    const submitButton = this.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Processing...';

    try {
        const formData = new FormData(this);
        const response = await fetch('process_loan.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.error) {
            throw new Error(data.error);
        }

        // Success message
        await Swal.fire({
            title: data.title || 'Success!',
            text: data.message,
            icon: data.icon || 'success',
            confirmButtonColor: '#28a745'
        });

        // Reload page after success
        location.reload();

    } catch (error) {
        // Error message
        await Swal.fire({
            title: 'Error!',
            text: error.message,
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = 'Confirm and Transfer Funds';
    }
});

// Add this to your existing script section
function confirmReject() {
    return Swal.fire({
        title: 'Confirm Rejection',
        text: 'Are you sure you want to reject this loan application?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, reject it'
    }).then((result) => {
        return result.isConfirmed;
    });
}

// Handle interest rate form submission
document.querySelectorAll('.interest-rate-form').forEach(form => {
    form.addEventListener('submit', function(event) {
        if (!confirm('Are you sure you want to update the interest rate?')) {
            event.preventDefault();
            return false;
        }
    });
});
</script>

