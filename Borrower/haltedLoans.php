<?php
session_start();
include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';

if (!UserAuth::isBorrowerAuthenticated()) {
    header('Location:../login.php?e=You are not Logged in.');
}
if (isset($_GET['e'])) {
    $error = $_GET['e'];
}
$errors = []; // Initialize an array to hold error messages
$id = $_SESSION['user_id'];
if (isset($_POST['activate'])) {
    $id = $_POST['loanId'];
   $check= Loan::changeStatus($id, "Pending");
    if ($check) {
        $message = "Loan Actived Successfully";
        header("Location: haltedLoans.php?e=" . urlencode($message));
        exit();
    } else {
        $message = "Something went Wrong";
        header("Location: haltedLoans.php?e=" . urlencode($message));
        exit;
    }
}


$loans = Loan::allLoansByUser($id, "halted");

?>

<?php
include_once('Layout/head.php');
include_once('Layout/sidebar.php');
?>

<div class="col-md-10 pb-5" style="background-color: #ECF0F4;">
    <div class="row text-center">
        <h5>
            <?php if (isset($error))
                echo $error; ?>
        </h5>
    </div>
    <div class="row d-flex justify-content-center">
        <div class="col-6">
            <?php $loan = Loan::checkLoan();
            if ($loan) {
                ?>
                <h6 class="alert alert-danger">You are defaulter for Loan</h6>
                <?php
            }
            ?>

        </div>
    </div>
    <div class="title text-center">
        <h2 class="h3 fw-bold mt-3 " style="font-family: sans-serif;">
            Halted Loan Applications

        </h2>
    </div>

    <div class="container-fluid">
        <div class="row mt-2 d-flex justify-content-center">
            <div class="col-lg-11 col-md-11 col-sm-11">
                <div class="table-responsive p-5" style="background-color: #FFFFFF; border-radius: 10px; ">
                    <table id="example" class="table table-bordered">
                        <thead class="text-white" style="background-color: #142127;">
                            <tr class="text-white bg-dark text-center">
                               
                                <th scope="col">Amount</th>
                                <th scope="col">Interest Rate</th>

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
                                        
                                    <td>  <?php echo $loan['loanAmount'] + (($loan['noOfInstallments']/12) *$loan['loanAmount'] * ($loan['interstRate'] / 100)) + ($loan['loanAmount'] * (2 / 100)); ?></td>
                                        <td><?php echo htmlspecialchars($loan['interstRate']); ?></td>
                                        <td><?php echo htmlspecialchars($loan['employeementTenure']); ?></td>
                                        <td><?php echo htmlspecialchars($loan['loanPurpose']); ?></td>
                                        <td><?php echo htmlspecialchars($loan['requested_at']); ?></td>

                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button"
                                                    id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Click Here
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">


                                                    <li>
                                                        <form method="post" action="">
                                                            <input type="hidden" name="loanId"
                                                                value="<?php echo htmlspecialchars($loan['id']); ?>">
                                                            <input type="submit" name="activate"
                                                                class="btn btn-danger btn-sm mb-2" value="Activte">
                                                        </form>
                                                    </li>
                                                </ul>
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
</script>