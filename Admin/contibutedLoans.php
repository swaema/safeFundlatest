<?php
//contibutedLoans.php
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';
session_start();
if (!UserAuth::isAdminAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
}
if (isset($_GET['e'])) {
    $error = $_GET['e'];
}
if (isset($_POST['rate'])) {
    $rate = $_POST['rateValue'];
    $id = $_POST['id'];
    $email = $_POST['email'];

    $error = Loan::interstRate($id, $rate, $email);
}
if (isset($_POST['deleteLoan'])) {
    $id = $_POST['id'];
    $email = $_POST['email'];
    $amount = $_POST['amount'];
    $check = Loan::delete($id, $amount, $email);
    if ($check) {
        $error = "Loan Rejected";
    }
}
if (isset($_POST['accept'])) {
    try {
        $loanId = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        $userId = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
        $loaninfo = Loan::getLoanById($loanId);
        $amount = $loaninfo['loanAmount'];

        $monthly = filter_var($_POST['monthly'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $installments = filter_var($_POST['noOfInstallments'], FILTER_SANITIZE_NUMBER_INT);
        $totalLoanAmount = filter_var($_POST['totalLoanAmount'], FILTER_SANITIZE_NUMBER_INT);

        error_log("Starting loan transfer process");
        error_log("Loan ID: " . $loanId);
        error_log("Amount: " . $amount);
        
        $config = require '../config.php';
        \Stripe\Stripe::setApiKey($config['stripe_secret_key']);

        try {
            $transfer = \Stripe\Transfer::create([
                'amount' => (int)($amount * 100),
                'currency' => 'usd',
                'destination' => 'acct_1Qiy5GL2AYu9PwEY',
                'description' => "Loan disbursement for loan #" . $loanId,
            ]);

            error_log("Transfer created: " . $transfer->id);
            Loan::changeStatus($loanId, 'approved');
            
            $db = new Database();
            $sql = "INSERT INTO loan_transfers (loan_id, amount, stripe_transfer_id, status) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("idss", $loanId, $amount, $transfer->id, 'completed');
            $stmt->execute();

            echo "<script>toastr.success('Loan approved and funds transferred successfully!');</script>";
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Stripe Error: " . $e->getMessage());
            echo "<script>toastr.error('Error: " . addslashes($e->getMessage()) . "');</script>";
        }
    } catch (Exception $e) {
        error_log("General Error: " . $e->getMessage());
        echo "<script>toastr.error('Error processing loan. Please try again.');</script>";
    }
}

$errors = [];
$id = $_SESSION['user_id'];
$loans = Loan::allContributedLoans();
?>

<?php
include_once('Layout/head.php');
include_once('Layout/sidebar.php');
?>

<!-- Custom CSS -->
<style>
    .dashboard-container {
        background-color: #f8f9fa;
        min-height: 100vh;
        padding: 2rem;
    }
    
    .card-loan {
        transition: transform 0.2s;
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .card-loan:hover {
        transform: translateY(-5px);
    }
    
    .loan-header {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 10px 10px 0 0;
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
    
    .custom-btn {
        border-radius: 20px;
        padding: 0.5rem 1.2rem;
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
    
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 500;
    }
    
    .user-image {
        border-radius: 50%;
        border: 3px solid #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
</style>

<div class="col-md-10 dashboard-container">
    <?php if (isset($error)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="loan-header mb-4">
        <h2 class="mb-0">Contributed Loan Applications</h2>
        <p class="text-light mb-0 mt-2">Manage and review all contributed loan applications</p>
    </div>

    <div class="table-container p-4">
        <table id="example" class="table custom-table table-hover">
            <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Contact Details</th>
                    <th>Loan Details</th>
                    <th>Employment</th>
                    <th>Purpose</th>
                    <th>Contribution</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (is_array($loans) && count($loans) > 0): ?>
                    <?php foreach ($loans as $loan): ?>
                        <?php
                        $tAmount = $loan['loanAmount'] + (($loan['noOfInstallments']/12) * $loan['loanAmount'] * ($loan['interstRate'] / 100)) + ($loan['loanAmount'] * (2 / 100));
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img class="user-image me-3" style="width: 50px; height: 50px;" 
                                         src="../<?php echo htmlspecialchars($loan['image']); ?>"
                                         alt="User Image" 
                                         onerror="this.onerror=null; this.src='../uploads/users/default/download.png';">
                                    <div>
                                        <strong><?php echo htmlspecialchars($loan['name']); ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($loan['mobile']); ?></div>
                                <div><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($loan['email']); ?></div>
                                <div><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($loan['address']); ?></div>
                            </td>
                            <td>
                                <div class="fw-bold">$<?php echo number_format($loan['loanAmount'], 2); ?></div>
                                <div class="text-muted"><?php echo htmlspecialchars($loan['noOfInstallments']); ?> installments</div>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo htmlspecialchars($loan['employeementTenure']); ?> years</span>
                            </td>
                            <td><?php echo htmlspecialchars($loan['loanPurpose']); ?></td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-success" 
                                         role="progressbar" 
                                         style="width: <?php echo htmlspecialchars($loan['totalLoanPercent']); ?>%"
                                         aria-valuenow="<?php echo htmlspecialchars($loan['totalLoanPercent']); ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?php echo htmlspecialchars(number_format($loan['totalLoanPercent'], 2)); ?>%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-primary custom-btn" data-bs-toggle="modal"
                                            data-bs-target="#loan-<?php echo htmlspecialchars($loan['id']) ?>">
                                        <i class="fas fa-info-circle me-1"></i> Details
                                    </button>
                                    <?php if ($loan['totalLoanPercent'] == 100): ?>
                                        <form method="post" action="" class="ms-2">
                                            <input type="hidden" name="totalLoanAmount" value="<?php echo htmlspecialchars($tAmount); ?>">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($loan['id']); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($loan['user_id']); ?>">
                                            <input type="hidden" name="monthly" value="<?php echo htmlspecialchars($monthly); ?>">
                                            <input type="hidden" name="noOfInstallments" value="<?php echo htmlspecialchars($loan['noOfInstallments']); ?>">
                                            <button type="submit" name="accept" class="btn btn-success custom-btn">
                                                <i class="fas fa-check me-1"></i> Approve
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <!-- Modal -->
                        <div class="modal fade" id="loan-<?php echo htmlspecialchars($loan['id']) ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content modal-custom">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="fas fa-file-invoice me-2"></i>Loan Application Details
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row g-4">
                                            <div class="col-md-6">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <h6 class="card-title text-primary">Applicant Information</h6>
                                                        <ul class="list-unstyled">
                                                            <li class="mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($loan['name']); ?></li>
                                                            <li class="mb-2"><strong>Annual Income:</strong> $<?php echo number_format($loan['AnnualIncome'], 2); ?></li>
                                                            <li class="mb-2"><strong>Risk Grade:</strong> 
                                                                <span class="badge bg-<?php echo $loan['grade'] === 'A' ? 'success' : ($loan['grade'] === 'B' ? 'warning' : 'danger'); ?>">
                                                                    <?php echo htmlspecialchars($loan['grade']); ?>
                                                                </span>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <h6 class="card-title text-primary">Loan Details</h6>
                                                        <ul class="list-unstyled">
                                                            <li class="mb-2"><strong>Principal Amount:</strong> $<?php echo number_format($loan['loanAmount'], 2); ?></li>
                                                            <li class="mb-2"><strong>Interest Rate:</strong> <?php echo htmlspecialchars($loan['interstRate']); ?>%</li>
                                                            <li class="mb-2"><strong>Term:</strong> <?php echo htmlspecialchars($loan['noOfInstallments']); ?> months</li>
                                                            <li class="mb-2"><strong>Monthly Payment:</strong> $<?php echo number_format($monthly, 2); ?></li>
                                                            <li class="mb-2"><strong>Total Amount:</strong> $<?php echo number_format($totalAmount, 2); ?></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <h6 class="card-title text-primary">Loan Timeline</h6>
                                                        <div class="d-flex justify-content-between">
                                                            <div>
                                                                <strong>Start Date:</strong><br>
                                                                <?php echo (new DateTime($loan['requested_at']))->format('Y-m-d'); ?>
                                                            </div>
                                                            <div>
                                                                <strong>End Date:</strong><br>
                                                                <?php 
                                                                $enddate = new DateTime($loan['requested_at']);
                                                                $enddate->modify('+' . $loan['noOfInstallments'] . ' months');
                                                                echo $enddate->format('Y-m-d'); 
                                                                ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="card bg-light">
                                                    <div class="card-body">
                                                        <h6 class="card-title text-primary">Purpose</h6>
                                                        <p class="mb-0"><?php echo htmlspecialchars($loan['loanPurpose']); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once('Layout/footer.php'); ?>

<!-- Initialize DataTable with custom options -->
<script>
    $(document).ready(function() {
        $('#example').DataTable({
            responsive: true,
            pageLength: 10,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search loans...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ loans",
                paginate: {
                    first: '<i class="fas fa-angle-double-left"></i>',
                    last: '<i class="fas fa-angle-double-right"></i>',
                    next: '<i class="fas fa-angle-right"></i>',
                    previous: '<i class="fas fa-angle-left"></i>'
                }
            },
            initComplete: function() {
                $('.dataTables_filter input').addClass('form-control');
                $('.dataTables_length select').addClass('form-select');
            }
        });
    });

    function myConfirm() {
        return confirm("Are you sure you want to reject this loan application?");
    }

    // Initialize all tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
</script>