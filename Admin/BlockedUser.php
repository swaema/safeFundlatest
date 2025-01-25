<?php
//BlockedUser.php
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
$errors = []; // Initialize an array to hold error messages
if (isset($_POST['active'])) {
    $userId = $_POST['id'];
    User::activeUser($userId);
}
$users = User::allByStatus('blocked');


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
    <div class="title text-center">
        <h2 class="h3 fw-bold mt-3 " style="font-family: sans-serif;">Blocked User
        </h2>
    </div>

    <div class="container-fluid">
        <div class="row mt-2 d-flex justify-content-center">
            <div class="col-lg-11 col-md-11 col-sm-11">
                <div class="table-responsive p-5" style="background-color: #FFFFFF; border-radius: 10px; ">
                    <table id="example" class="table table-bordered">
                        <thead class="text-white" style="background-color: #142127;">
                            <tr class="text-white bg-dark text-center">
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">User Type</th>
                                <th scope="col">Mobile No.</th>
                                <th scope="col">Address</th>
                                <th scope="col">Status</th>
                                <th scope="col">Image</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (is_array($users) && count($users) > 0): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td scope="row"><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td scope="row"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['address']); ?></td>
                                        <td><?php echo htmlspecialchars($user['status'] ?? 'Inactive'); ?></td>
                                        <td>
                                            <img style="height:70px;" src="../<?php echo htmlspecialchars($user['image']); ?>"
                                                alt="User Image">
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button"
                                                    id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Click Here
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                    <li>
                                                        <button type="button" class="btn btn-info btn-sm mb-2"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#exampleModal<?php echo $user['id'] ?>">
                                                            Details
                                                        </button>

                                                    </li>
                                                    <li>
                                                        <form method="post" action="">
                                                            <input type="hidden" name="id"
                                                                value="<?php echo htmlspecialchars($user['id']); ?>">
                                                            <button type="submit" class="btn btn-info btn-sm mb-2"
                                                                name="active">Active</button>
                                                        </form>
                                                    </li>

                                                    <li><button type="button" class="btn btn-warning btnProduct2"
                                                            id="<?php echo htmlspecialchars($user['id']); ?>">Edit</button>
                                                    </li>

                                                    <li>
                                                        <form method="post" action="" onsubmit="return myConfirm();">
                                                            <input type="hidden" name="UserToDelete"
                                                                value="<?php echo htmlspecialchars($user['id']); ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm mb-2"
                                                                name="deleteAcessory">Delete</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="modal fade" id="exampleModal<?php echo $user['id']?>" tabindex="-1"
                                                aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h1 class="modal-title fs-5" id="exampleModalLabel">Details</h1>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <?php $documents = User::getDocument($user['id']) ?>
                                                            <div class="row">
                                                                <?php foreach ($documents as $document): ?>
                                                                    
                                                                    <div class="col-12">
                                                                        <img style="height:70px;"
                                                                            src="../<?php echo htmlspecialchars($document['path']); ?>"
                                                                            alt="User Image" 
                                                                            onerror="this.onerror=null; this.src='../uploads/users/default/download.png';">
                                                                    </div>
                                                                <?php endforeach; ?>
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