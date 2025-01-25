<?php
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';
require_once '../Classes/LoanInstallments.php';

session_start();

// Authentication check
if (!UserAuth::isLenderAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
    exit();
}

// Initialize variables
$error = isset($_GET['e']) ? $_GET['e'] : '';
$id = $_SESSION['user_id'];
$loans = Loan::allLenderLoans("approved");

// Include layout files
include_once('Layout/head.php');
include_once('Layout/sidebar.php');
?>

<div class="col-md-10 pb-5" style="background-color: #ECF0F4;">
    <div class="container-fluid">
        <!-- Error Message Display -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Page Title -->
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2 class="h3 fw-bold mt-3">Active Loans</h2>
            </div>
        </div>

        <!-- Main Content -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="example" class="table table-bordered table-hover">
                        <thead>
                            <tr class="text-white text-center" style="background-color: #142127;">
                                <th>Applicant</th>
                                <th>Contact</th>
                                <th>Interest Rate</th>
                                <th>Amount</th>
                                <th>Installments</th>
                                <th>Employment</th>
                                <th>Purpose</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $profit = 0;
                            if (is_array($loans) && count($loans) > 0):
                                foreach ($loans as $loan):
                                    $interest = ($loan['loanAmount'] * $loan['interstRate']) / 100 - ($loan['loanAmount'] * (2 / 100));
                                    $profit += $interest;
                            ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img class="rounded-circle me-2" style="height: 50px; width: 50px; object-fit: cover;"
                                                     src="../<?php echo htmlspecialchars($loan['image']); ?>"
                                                     alt="<?php echo htmlspecialchars($loan['name']); ?>"
                                                     onerror="this.src='../uploads/users/default/download.png';">
                                                <div class="fw-medium"><?php echo htmlspecialchars($loan['name']); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($loan['mobile']); ?><br>
                                                <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($loan['email']); ?><br>
                                                <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($loan['address']); ?>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle"><?php echo htmlspecialchars($loan['interstRate']); ?>%</td>
                                        <td class="text-end align-middle"><?php echo number_format($loan['loanAmount'], 2); ?></td>
                                        <td class="text-center align-middle"><?php echo htmlspecialchars($loan['noOfInstallments']); ?></td>
                                        <td class="align-middle"><?php echo htmlspecialchars($loan['employeementTenure']); ?></td>
                                        <td class="align-middle"><?php echo htmlspecialchars($loan['loanPurpose']); ?></td>
                                        <td class="align-middle"><?php echo date('Y-m-d', strtotime($loan['requested_at'])); ?></td>
                                        <td class="text-center align-middle">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#historyModal<?php echo $loan['id']; ?>">
                                                    History
                                                </button>
                                                <button type="button" class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $loan['id']; ?>">
                                                    Details
                                                </button>
                                            </div>

                                            <!-- History Modal -->
                                            <div class="modal fade" id="historyModal<?php echo $loan['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-primary text-white">
                                                            <h5 class="modal-title">Payment History</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body p-4">
                                                            <?php $loanInstallments = LoanInstallments::loanInstallmentbyLoanId($loan['id']); ?>
                                                            <div class="table-responsive">
                                                                <table class="table table-hover mb-0">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Amount</th>
                                                                            <th>Due Date</th>
                                                                            <th>Status</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php if (!empty($loanInstallments)): ?>
                                                                            <?php foreach ($loanInstallments as $installment): ?>
                                                                                <tr>
                                                                                    <td><?php echo number_format($installment['payable_amount'], 2); ?></td>
                                                                                    <td><?php echo date('Y-m-d', strtotime($installment['pay_date'])); ?></td>
                                                                                    <td>
                                                                                        <span class="badge rounded-pill <?php echo $installment['status'] == 'paid' ? 'bg-success' : 'bg-warning'; ?>">
                                                                                            <?php echo ucfirst($installment['status']); ?>
                                                                                        </span>
                                                                                    </td>
                                                                                </tr>
                                                                            <?php endforeach; ?>
                                                                        <?php else: ?>
                                                                            <tr>
                                                                                <td colspan="3" class="text-center">No payment history available</td>
                                                                            </tr>
                                                                        <?php endif; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Details Modal -->
                                            <div class="modal fade" id="detailsModal<?php echo $loan['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header" style="background-color: #006d72; color: white;">
                                                            <h5 class="modal-title">Loan Details</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body" style="background-color: #f8f9fa;">
                                                            <?php 
                                                            $percent = Loan::calculatePercent($loan['id']); 
                                                            $startDate = new DateTime($loan['requested_at']);
                                                            $endDate = clone $startDate;
                                                            $endDate->modify('+' . $loan['noOfInstallments'] . ' months');
                                                            
                                                            // Calculate loan details
                                                            $totalInterest = (($loan['noOfInstallments'] / 12) * $loan['loanAmount'] * ($loan['interstRate'] / 100));
                                                            $processingFee = $loan['loanAmount'] * 0.02; // 2% processing fee
                                                            $totalAmount = $loan['loanAmount'] + $totalInterest + $processingFee;
                                                            $monthlyPayment = $totalAmount / $loan['noOfInstallments'];
                                                            ?>

                                                            <!-- Progress Section -->
                                                            <div class="card mb-3">
                                                                <div class="card-body">
                                                                    <h6 class="card-title">Repayment Progress</h6>
                                                                    <div class="progress mb-2" style="height: 20px;">
                                                                        <div class="progress-bar bg-success" 
                                                                             role="progressbar" 
                                                                             style="width: <?php echo $percent; ?>%">
                                                                            <?php echo $percent; ?>%
                                                                        </div>
                                                                    </div>
                                                                    <div class="d-flex justify-content-between text-muted small">
                                                                        <span>Start: <?php echo $startDate->format('Y-m-d'); ?></span>
                                                                        <span>End: <?php echo $endDate->format('Y-m-d'); ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Loan Information -->
                                                            <div class="row g-3">
                                                                <div class="col-sm-6">
                                                                    <div class="card h-100">
                                                                        <div class="card-body">
                                                                            <h6 class="card-title">Principal Amount</h6>
                                                                            <p class="card-text fw-bold text-primary"><?php echo number_format($loan['loanAmount'], 2); ?></p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-sm-6">
                                                                    <div class="card h-100">
                                                                        <div class="card-body">
                                                                            <h6 class="card-title">Monthly Payment</h6>
                                                                            <p class="card-text fw-bold text-success"><?php echo number_format($monthlyPayment, 2); ?></p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-sm-6">
                                                                    <div class="card h-100">
                                                                        <div class="card-body">
                                                                            <h6 class="card-title">Interest Rate</h6>
                                                                            <p class="card-text fw-bold"><?php echo $loan['interstRate']; ?>%</p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-sm-6">
                                                                    <div class="card h-100">
                                                                        <div class="card-body">
                                                                            <h6 class="card-title">Total Interest</h6>
                                                                            <p class="card-text fw-bold text-danger"><?php echo number_format($totalInterest, 2); ?></p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Additional Information -->
                                                            <div class="card mt-3">
                                                                <div class="card-body">
                                                                    <h6 class="card-title">Additional Information</h6>
                                                                    <dl class="row mb-0">
                                                                        <dt class="col-sm-5">Credit Grade</dt>
                                                                        <dd class="col-sm-7"><?php echo htmlspecialchars($loan['grade']); ?></dd>
                                                                        
                                                                        <dt class="col-sm-5">Annual Income</dt>
                                                                        <dd class="col-sm-7"><?php echo number_format($loan['AnnualIncome'], 2); ?></dd>
                                                                        
                                                                        <dt class="col-sm-5">Processing Fee</dt>
                                                                        <dd class="col-sm-7"><?php echo number_format($processingFee, 2); ?> (2%)</dd>
                                                                        
                                                                        <dt class="col-sm-5">Purpose</dt>
                                                                        <dd class="col-sm-7"><?php echo htmlspecialchars($loan['loanPurpose']); ?></dd>
                                                                    </dl>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="text-muted">No active loans found</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Profit Display -->
                    <?php
                    try {
                        $db = Database::getConnection();
                        if ($db === null) {
                            throw new Exception("Database connection failed");
                        }
                        $dbProfit = $db->query("SELECT `Earning` FROM `consoledatedfund` WHERE `user_id` = $id")->fetch_assoc()['Earning'];
                        $profit = $dbProfit ?? 0;
                    } catch (Exception $e) {
                        $profit = 0;
                    }
                    ?>
                    <div class="card mt-4">
                        <div class="card-body">
                            <h3 class="text-success mb-0">
                                Total Profit: <span class="fw-bold"><?php echo number_format($profit, 2);?></span>
                            </h3>
                            </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once('Layout/footer.php'); ?>

<!-- Custom Styles -->
<style>
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1050;
    width: 100%;
    height: 100%;
    overflow-x: hidden;
    overflow-y: auto;
    outline: 0;
    background: rgba(0, 0, 0, 0);
}

.modal-open .modal {
    overflow-x: hidden;
    overflow-y: auto;
}

.modal-dialog {
    position: relative;
    width: auto;
    margin: 1.75rem auto;
    max-width: 600px;
    pointer-events: auto;
}

.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1040;
    width: 100vw;
    height: 100vh;
    background-color: #000;
}

.modal-backdrop.show {
    opacity: 0.5;
}

.modal-content {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
    background-color: #fff;
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    outline: 0;
}

.progress {
    background-color: rgba(0, 0, 0, 0.1);
}

.table th {
    font-weight: 600;
}

.btn-group .btn {
    margin: 0 2px;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.badge {
    font-weight: 500;
    padding: 0.5em 0.8em;
}
</style>

<!-- Scripts -->
<script>

$(document).ready(function() {
    // Initialize DataTable (keeping your existing configuration)
    $('#example').DataTable({
        responsive: true,
        order: [[7, 'desc']],
        pageLength: 10,
        language: {
            emptyTable: "No active loans available",
            info: "Showing _START_ to _END_ of _TOTAL_ loans",
            search: "Search loans:",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        columnDefs: [
            { orderable: false, targets: -1 }
        ]
    });

    // Remove any existing modal event handlers to prevent double binding
    $('.modal').off('show.bs.modal hidden.bs.modal');
    
    // Force cleanup of any existing modals on page load
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('padding-right', '');
    $('.modal').removeClass('show').css('display', '');

    // Single function to handle modal cleanup
    function cleanupModal() {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
        $('.modal').removeClass('show').css('display', '');
    }

    // Handle modal open with debounce to prevent double-trigger
    let modalTimeout;
    $(document).on('click', '[data-bs-toggle="modal"]', function(e) {
        e.preventDefault();
        clearTimeout(modalTimeout);
        
        const targetModal = $($(this).data('bs-target'));
        
        modalTimeout = setTimeout(() => {
            cleanupModal(); // Clean up any existing modals
            targetModal.modal('show');
        }, 50);
    });

    // Simplified modal show handler
    $('.modal').on('show.bs.modal', function(e) {
        const $modal = $(this);
        
        // Prevent multiple modals
        $('.modal').not($modal).modal('hide');
        
        // Ensure single backdrop
        $('.modal-backdrop').remove();
        
        // Set proper z-index
        $modal.css('z-index', 1050);
        
        // Prevent body scroll
        $('body').addClass('modal-open');
    });

    // Simplified modal hide handler
    $('.modal').on('hidden.bs.modal', function(e) {
        cleanupModal();
    });

    // Close modal on escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            cleanupModal();
        }
    });

    // Progress bar animation (keeping your existing code)
    $('.progress-bar').each(function() {
        let $this = $(this);
        let width = $this.css('width');
        $this.css('width', '0%')
            .animate({
                width: width
            }, {
                duration: 1000
            });
    });
});
// Wait for the page to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Store references to commonly used elements
    const body = document.querySelector('body');
    
    // Function to handle modal state
    function handleModalState(modalElement, isOpening) {
        const currentModals = document.querySelectorAll('.modal.show').length;
        
        if (isOpening) {
            // Ensure clean state
            modalElement.style.paddingRight = '0';
            body.style.paddingRight = '0';
            
            // Set proper z-index
            modalElement.style.zIndex = 1055 + (currentModals * 10);
            const backdrop = document.querySelector('.modal-backdrop:last-child');
            if (backdrop) {
                backdrop.style.zIndex = 1054 + (currentModals * 10);
            }
        }
    }

    // Add event listeners to all modals
    document.querySelectorAll('.modal').forEach(modal => {
        // Before modal shows
        modal.addEventListener('show.bs.modal', function(event) {
            handleModalState(this, true);
        });

        // After modal is fully shown
        modal.addEventListener('shown.bs.modal', function(event) {
            body.classList.add('modal-open');
        });

        // Before modal starts hiding
        modal.addEventListener('hide.bs.modal', function(event) {
            handleModalState(this, false);
        });

        // After modal is hidden
        modal.addEventListener('hidden.bs.modal', function(event) {
            const stillOpen = document.querySelectorAll('.modal.show').length;
            if (stillOpen === 0) {
                body.classList.remove('modal-open');
                // Remove all existing backdrops
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                    backdrop.remove();
                });
            }
            // Reset this modal's state
            this.style.display = '';
            this.style.paddingRight = '';
        });
    });

    // Handle escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modals = Array.from(document.querySelectorAll('.modal.show'));
            if (modals.length > 0) {
                const topModal = modals[modals.length - 1];
                const modalInstance = bootstrap.Modal.getInstance(topModal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
        }
    });
});
</script>
