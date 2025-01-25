<?php

include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';
require_once '../Classes/LoanInstallments.php';
session_start();

if (!UserAuth::isBorrowerAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
}

if (isset($_GET['e'])) {
    $error = $_GET['e'];
}

$errors = [];
$id = $_SESSION['user_id'];

if (isset($_POST['payIns'])) {
    $loanId = $_POST['loanId'];
    $loaninfo = Loan::getLoanById($loanId);
    $totalloan = $loaninfo['TotalLoan'];
    $installamentamount = $loaninfo['InstallmentAmount'];
    $loanamount = $loaninfo['loanAmount'];
    $interestwithadmin = $totalloan-$loanamount;
    $noofinstallements = $loaninfo['noOfInstallments'];
    $successfee = $loanamount*0.02;
    $interest = round(($interestwithadmin-$successfee)/$noofinstallements,2);
    $principal = $loanamount/$noofinstallements;
    $principal = round($principal,2);
    $adminfee = $installamentamount-$principal-$interest;

    LoanInstallments::updateStatus($loanId, $installamentamount,$principal,$interest,$adminfee);
}

$loans = Loan::allLoansBorrower("approved");

include_once('Layout/head.php');
include_once('Layout/sidebar.php');
?>

<style>
/* Main Container Styles */
.dashboard-container {
    background-color: #f8f9fa;
    min-height: 100vh;
    padding: 2rem;
}

.dashboard-header {
    background: linear-gradient(135deg, #006d72 0%, #00959c 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

/* Card Styles */
.loan-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    padding: 1.5rem;
    margin-bottom: 2rem;
    transition: all 0.3s ease;
}

.info-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* Table Styles */
.table thead th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    color: #495057;
    font-weight: 600;
}

.table td {
    vertical-align: middle;
}

/* Modal Styles */
.modal-custom .modal-content {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.modal-custom .modal-header {
    background: linear-gradient(135deg, #006d72 0%, #00959c 100%);
    color: white;
    border-radius: 15px 15px 0 0;
    padding: 1.5rem;
}

.modal-custom .modal-body {
    background-color: #f8f9fa;
    padding: 2rem;
}

.modal-custom .modal-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    padding: 1rem 2rem;
    border-radius: 0 0 15px 15px;
}

/* Progress Bar */
.custom-progress {
    height: 10px;
    border-radius: 5px;
    background-color: #e9ecef;
    margin-bottom: 1rem;
}

/* Buttons */
.action-btn {
    border-radius: 20px;
    padding: 0.5rem 1.2rem;
    transition: all 0.3s;
}

.payment-btn {
    background: #ff9c00;
    color: white;
    border: none;
    padding: 0.5rem 2rem;
    border-radius: 25px;
    transition: all 0.3s;
}

.payment-btn:hover {
    background: #e68a00;
    transform: translateY(-2px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Detail Rows */
.detail-row {
    padding: 0.8rem;
    border-bottom: 1px solid #eee;
}

.detail-row:last-child {
    border-bottom: none;
}

/* Badges */
.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 500;
}

/* Alerts */
.alert {
    border-radius: 10px;
    border: none;
    margin-bottom: 1rem;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 1rem;
    }
    
    .modal-custom .modal-body {
        padding: 1rem;
    }
    
    .info-card {
        padding: 1rem;
    }
}
</style>

<div class="col-md-10 dashboard-container">
    <?php if (isset($error)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="dashboard-header">
        <h2 class="mb-0">Active Loans Dashboard</h2>
        <p class="text-light mb-0 mt-2">Manage and track your active loan applications</p>
    </div>

    <div class="loan-card">
        <div class="table-responsive">
            <table id="example" class="table table-hover">
                <thead>
                    <tr>
                        <th>Interest Rate</th>
                        <th>Total Amount</th>
                        <th>Installments</th>
                        <th>Employment</th>
                        <th>Purpose</th>
                        <th>Application Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (is_array($loans) && count($loans) > 0): 
                        foreach ($loans as $loan):
                            $totalAmount = $loan['loanAmount'] + 
                                (($loan['noOfInstallments'] / 12) * $loan['loanAmount'] * ($loan['interstRate'] / 100)) + 
                                ($loan['loanAmount'] * (2 / 100));
                    ?>
                        <tr>
                            <td>
                                <span class="badge bg-info"><?php echo htmlspecialchars($loan['interstRate']); ?>%</span>
                            </td>
                            <td>
                                <strong>$<?php echo number_format($totalAmount, 2); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($loan['noOfInstallments']); ?></td>
                            <td><?php echo htmlspecialchars($loan['employeementTenure']); ?> years</td>
                            <td><?php echo htmlspecialchars($loan['loanPurpose']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($loan['requested_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-primary btn-sm action-btn" 
                                            data-bs-toggle="modal" data-bs-target="#historyModal<?php echo $loan['id']; ?>">
                                        <i class="fas fa-history me-1"></i> History
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm action-btn ms-2" 
                                            data-bs-toggle="modal" data-bs-target="#paymentModal<?php echo $loan['id']; ?>">
                                        <i class="fas fa-info-circle me-1"></i> Details
                                    </button>
                                </div>

                                <!-- Include Payment History Modal -->
                                <?php include 'modals/history-modal.php'; ?>

                                <!-- Include Payment Details Modal -->
                                <?php include 'modals/payment-modal.php'; ?>
                            </td>
                        </tr>
                    <?php 
                        endforeach; 
                    endif; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once('Layout/footer.php'); ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#example').DataTable({
        responsive: true,
        pageLength: 10,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search loans...",
        },
        columnDefs: [
            { className: "align-middle", targets: "_all" }
        ],
        initComplete: function() {
            $('.dataTables_filter input').addClass('form-control');
            $('.dataTables_length select').addClass('form-select');
        }
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});

// Sweet Alert confirmation
function myConfirm() {
    return Swal.fire({
        title: 'Are you sure?',
        text: "Do you want to proceed with this action?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        return result.isConfirmed;
    });
}

// Handle payment form submission
$('.payment-form').on('submit', async function(e) {
    e.preventDefault();
    
    // Show confirmation dialog
    const confirmed = await Swal.fire({
        title: 'Confirm Payment',
        text: "Are you sure you want to proceed with this payment?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!'
    });

    if (!confirmed.isConfirmed) return;

    const form = $(this);
    const submitBtn = form.find('button[type="submit"]');
    
    // Disable button and show loading state
    submitBtn.prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
    
    try {
        // Get the form action URL
        const actionUrl = form.attr('action') || 'process-payment.php';
        
        // Make sure we have a valid URL
        if (!actionUrl) {
            throw new Error('Invalid form configuration');
        }

        // Send the request
        const response = await fetch(actionUrl, {
            method: 'POST',
            body: new FormData(form[0])
        });

        // Check if the response is OK
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        // Try to parse the JSON response
        let result;
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.indexOf("application/json") !== -1) {
            result = await response.json();
        } else {
            // If response is not JSON, treat it as text
            const text = await response.text();
            result = { success: response.ok, message: text };
        }

        // Handle the response
        if (result.success) {
            await Swal.fire({
                title: 'Success!',
                text: result.message || 'Payment processed successfully!',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            
            // Redirect or reload based on the response
            if (result.redirect) {
                window.location.href = result.redirect;
            } else {
                location.reload();
            }
        } else {
            throw new Error(result.error || 'Payment processing failed');
        }
    } catch (error) {
        console.error('Payment Error:', error);
        
        await Swal.fire({
            title: 'Error!',
            text: error.message || 'Something went wrong processing your payment',
            icon: 'error',
            confirmButtonColor: '#3085d6'
        });
    } finally {
        // Reset button state
        submitBtn.prop('disabled', false).html('Make Payment');
    }
});
</script>