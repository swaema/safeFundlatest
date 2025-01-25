<?php

include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';

session_start();
if (!UserAuth::isLenderAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
}
if (isset($_GET['e'])) {
    $error = $_GET['e'];
}
$errors = []; // Initialize an array to hold error messages
$id = $_SESSION['user_id'];
if (isset($_POST['addLoanApp'])) {
    try {
        // Sanitize user inputs

        $amount = filter_var($_POST['amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $term = filter_var($_POST['term'], FILTER_SANITIZE_NUMBER_INT);
        $purpose = htmlspecialchars($_POST['purpose'], ENT_QUOTES, 'UTF-8');
        $now = date('Y-m-d H:i:s');
        // Instantiate the LoanApplication class
        $loan = new Loan(null, $id, null, null, null, null, null, $amount, $term, $purpose, 'Pending', $now);

        // Save the loan
        $result = $loan->saveLoan();

        $error = $result; // Provide feedback to the user
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

if (isset($_POST['Contribute'])) {
    $loanId = filter_var($_POST['loanId'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $lender_id = $_SESSION['user_id'];
    $amountContributed = $_POST['amountContributed'];
    Loan::contributeLoan($loanId, $lender_id, $amountContributed);
}
$loans = Loan::allLoans("Accepted");
?>

<?php
include_once('Layout/head.php');
include_once('Layout/sidebar.php');
?>

<div class="col-md-10 pb-5" style="background-color: #ECF0F4;">
    <div class="conatiner-fluid">
        <div class="row">
            <div class="col-12">
                <div class="row text-center">
                    <h5>
                        <?php if (isset($error))
                            echo $error; ?>
                    </h5>
                </div>
                <div class="title text-center">
                    <h2 class="h3 fw-bold mt-3 " style="font-family: sans-serif;">
                        Loan Application
                    </h2>
                </div>
                <div class="container-fluid">
                    <div class="row mt-2 d-flex justify-content-center">
                        <div class="col-lg-12 col-md-12 col-sm-12">
                            <div class="table-responsive p-5" style="background-color: #FFFFFF; border-radius: 10px; ">
                                <table id="example" class="table table-bordered me-5">
                                    <thead class="text-white" style="background-color: #142127;">
                                        <tr class="text-white bg-dark text-center">
                                            <th scope="col">Applicant</th>
                                            <th scope="col">Contact</th>
                                            <th scope="col">Amount</th>
                                            <th scope="col">Installments</th>
                                            <th scope="col">Employement Tenure</th>
                                            <th scope="col">Purpose</th>
                                            <th scope="col">Application Date</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (is_array($loans) && count($loans) > 0): ?>
                                            <?php foreach ($loans as $loan): ?>
                                                <tr>
                                                    <td scope="row">
                                                        <?php echo htmlspecialchars($loan['name']); ?>
                                                        <br>
                                                        <img style="height:70px;"
                                                            src="../<?php echo htmlspecialchars($loan['image']); ?>"
                                                            alt="User Image"
                                                            onerror="this.onerror=null; this.src='../uploads/users/default/download.png';">
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($loan['mobile']); ?>
                                                        <br>
                                                        <?php echo htmlspecialchars($loan['email']); ?>
                                                        <br>
                                                        <?php echo htmlspecialchars($loan['address']); ?>
                                                    </td>
                                                    <?php
                                                     $loanAmount = $loan['loanAmount'];
                                                     $interestRate = $loan['interstRate'];
 
                                                     // Calculate the interest
                                                     $interest = (($loan['noOfInstallments']/12) *$loan['loanAmount'] * ($loan['interstRate'] / 100));
                                                     
                                                       
                                                     // Calculate the total amount
                                                     $totalAmount =  $loan['loanAmount'] + $interest + ($loan['loanAmount'] * (2 / 100));
                                                     $monthly = $totalAmount / $loan['noOfInstallments'];
                                                    ?>
                                                    <td><?php echo htmlspecialchars($loanAmount); ?></td>
                                                    <td><?php echo htmlspecialchars($loan['noOfInstallments']); ?></td>
                                                    <td><?php echo htmlspecialchars($loan['employeementTenure']); ?></td>
                                                    <td><?php echo htmlspecialchars($loan['loanPurpose']); ?></td>
                                                    <td><?php echo htmlspecialchars($loan['requested_at']); ?></td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-secondary dropdown-toggle"
                                                                type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                                                aria-expanded="false">
                                                                Click Here
                                                            </button>
                                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">

                                                                <li><button type="button" class="btn btn-info"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#loan-<?php echo htmlspecialchars($loan['id']) ?>"
                                                                        id="<?php echo htmlspecialchars($loan['id']); ?>">Details</button>
                                                                </li>
                                                                <!-- <li><button type="button" class="btn btn-success"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#contribute-<?php // echo htmlspecialchars($loan['id']) ?>"
                                                                        id="<?php echo htmlspecialchars($loan['id']); ?>">Contribute</button>
                                                                </li> -->

                                                            </ul>
                                                        </div>
                                                        <div class="modal fade"
                                                            id="loan-<?php echo htmlspecialchars($loan['id']) ?>" tabindex="-1"
                                                            aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                            <div class="modal-dialog ">
                                                                <div class="modal-content " style="background: #89A8B2;">
                                                                    <div class="modal-header">
                                                                        <h1 class="modal-title fs-5 text-white"
                                                                            id="exampleModalLabel">
                                                                            Details
                                                                        </h1>
                                                                        <button type="button" class="btn-close"
                                                                            data-bs-dismiss="modal" style="color:white"
                                                                            aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body text-white">
                                                                        <div class="row">
                                                                            <div class="col-6">Start Date</div>
                                                                            <div class="col-6">End Date</div>
                                                                            <?php
                                                                            $date = new DateTime($loan['requested_at']);
                                                                            $date->format('Y-m-d');
                                                                            ?>
                                                                            <div class="col-6">
                                                                                <?php echo $date->format('Y-m-d'); ?>
                                                                            </div>
                                                                            <?php
                                                                            $enddate = new DateTime($loan['requested_at']);

                                                                            // Add the number of months
                                                                            $enddate->modify('+' . $loan['noOfInstallments'] . ' months');

                                                                            // Format and echo the new date
                                                                    
                                                                            ?>
                                                                            <div class="col-6">
                                                                                <?php echo $enddate->format('Y-m-d'); ?>
                                                                            </div>
                                                                            <div class="col-6">
                                                                                <p class="fw-bold"> Name</p>
                                                                            </div>
                                                                            <div class="col-6">
                                                                                <?php echo $loan['name'] ?>
                                                                            </div>
                                                                            <div class="col-6">
                                                                                <p class="fw-bold">Principal Amount</p>
                                                                            </div>
                                                                            <div class="col-6">
                                                                                <?php echo $loan['loanAmount'] ?>
                                                                            </div>
                                                                            <div class="col-6">
                                                                                <p class="fw-bold"> Annual Income</p>
                                                                            </div>
                                                                            <div class="col-6">
                                                                                <?php echo $loan['AnnualIncome'] ?>
                                                                            </div>
                                                                            <div class="col-6">
                                                                                <p class="fw-bold"> Installments</p>
                                                                            </div>
                                                                            <div class="col-6">
                                                                                <?php echo $loan['noOfInstallments'] ?>
                                                                            </div>
                                                                            <div class="col-6">
                                                                                <p class="fw-bold"> Interest Rate</p>
                                                                            </div>
                                                                            <div class="col-6">
                                                                                <?php echo $loan['interstRate'] ?>
                                                                            </div>
                                                                            <div class="col-6">
                                                                                <p class="fw-bold"> Loan Purpose</p>
                                                                            </div>
                                                                            <div class="col-6">
                                                                                <?php echo $loan['loanPurpose'] ?>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="card bg-info p-2 rounded">
                                                                                <div class="row">
                                                                                    <div class="col-6">Total Amount</div>
                                                                                    <?php
                                                                                   $loanAmount = $loan['loanAmount'];
                                                                                   $interestRate = $loan['interstRate'];
                               
                                                                                   // Calculate the interest
                                                                                   $interest = (($loan['noOfInstallments']/12) *$loan['loanAmount'] * ($loan['interstRate'] / 100));
                                                                                   
                                                                                     
                                                                                   // Calculate the total amount
                                                                                   $totalAmount =  $loan['loanAmount'] + $interest + ($loan['loanAmount'] * (2 / 100));
                                                                                   $monthly = $totalAmount / $loan['noOfInstallments'];
                                                                                    ?>
                                                                                    <div class="col-6">
                                                                                        <?php echo $totalAmount; ?>
                                                                                    </div>
                                                                                    <div class="col-6">Per month Installment
                                                                                    </div>
                                                                                    <?php
                                                                                    $monthly = $totalAmount / $loan['noOfInstallments'];
                                                                                    ?>
                                                                                    <div class="col-6">
                                                                                        <?php echo round($monthly, 2); ?>
                                                                                    </div>
                                                                                    <div class="col-6">Risk Category</div>
                                                                                    <div class="col-6"><span
                                                                                            class="fw-bold"><?php echo $loan['grade'] ?></span>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row">
                                                                            <?php $contributed = Loan::getContributedLoan($loan['id']);
                                                                            $AmountContributed = $loanAmount * ($contributed / 100);
                                                                            ?>
                                                                            <label for="">Loan Contributed:
                                                                                <span><?php echo ceil($AmountContributed); ?></span>
                                                                            </label>
                                                                        </div>
                                                                        <?php 
                                                                                if($AmountContributed<$loanAmount)
                                                                                {?>
                                                                            <form action="" method="post"
                                                                            onsubmit="return validateLoanPercent()">
                                                                            
                                                                            <label class="form-label">Amount to
                                                                                contribute</label>
                                                                            <input value="<?php echo $AmountContributed; ?>"
                                                                                type="hidden" name="contriamount"
                                                                                id="contriamount">
                                                                            <input value="<?php echo $loan['email']; ?>"
                                                                                type="hidden" name="email" id="email">
                                                                            <input value="<?php echo $loan['id']; ?>"
                                                                                type="hidden" name="loanId" id="loanId">
                                                                            <input value="<?php echo $loanAmount; ?>"
                                                                                type="hidden" name="totalAmount"
                                                                                id="totalAmount">
                                                                            <input type="number" name="amountContributed"
                                                                                placeholder="Enter Amount to be contributed"
                                                                                class="form-control" id="loanpercent" min="0"
                                                                                max="<?php echo $loanAmount ?>" required>
                                                                            <input type="submit" name="Contribute"
                                                                                class="btn btn-primary mt-3" value="Contribute">
                                                                        </form>
                                                                            <?php }
                                                                            ?>
                                                                        
                                                                    </div>
                                                                </div>

                                                            </div>
                                                        </div>
                                                        <div class="modal fade"
                                                            id="contribute-<?php echo htmlspecialchars($loan['id']) ?>"
                                                            tabindex="-1" aria-labelledby="exampleModalLabel"
                                                            aria-hidden="true">
                                                            <div class="modal-dialog ">
                                                                <div class="modal-content ">
                                                                    <div class="modal-header">
                                                                        <h1 class="modal-title fs-5" id="exampleModalLabel">
                                                                            Loan Contribution
                                                                        </h1>
                                                                        <button type="button" class="btn-close"
                                                                            data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body ">

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
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php
include_once('Layout/footer.php');
?>
<script>
    $(document).ready(function () {
        $('#example').DataTable();
    });
    function myConfirm() {
        var result = confirm("Want to delete?");
        if (result == true) {
            return true;
        } else {
            return false;
        }
    }


    function validateLoanPercent() {
        let loanPercentInput = parseFloat(document.getElementById('loanpercent').value);
        let contributed = parseFloat(document.getElementById('contriamount').value);
        let totalAmount = parseFloat(document.getElementById('totalAmount').value);

        let newTotal = contributed + loanPercentInput;

        if (newTotal > totalAmount) {
            alert('The total contribution exceeds loan Amount. Please enter a valid percentage.');
            return false; // Prevent form submission
        }

        return true; // Proceed with form submission if validation passes
    }
</script>