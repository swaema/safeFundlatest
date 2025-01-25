<?php
require_once 'Classes/User.php';
require_once 'Classes/DocumentUploader.php';
require_once 'Classes/Notification.php';
require_once 'Classes/Database.php';
require_once 'Classes/Borrower.php';
require_once 'Classes/Lender.php';
require_once 'Classes/Mail.php';

$messageSuccess = ""; // To store success  messages
$messageError = ""; // To store  error messages


if (isset($_POST['SignUp'])) {
    try {
        if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['role']) || empty($_POST['mobile']) || empty($_POST['address'])) {
            throw new Exception("All fields are required.");
        }
        $amount = 0;
        // Retrieve form data
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $role = trim($_POST['role']);
        $mobile = trim($_POST['mobile']);
        $address = trim($_POST['address']);
        
       

        //valiadtion for name
        // if (strlen($mobile) < 5 || strlen($mobile) > 8) {
        //     var_dump("success");
        // }
        // var_dump("error");

        if (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
            throw new Exception("Name can only contain letters and spaces.");
        }
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address.");
        }

        // Validate password length
        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long.");
        }

        // Validate role selection
        if (!in_array($role, ['borrower', 'lender'])) {
            throw new Exception("Invalid role selected.");
        }

        // Create user and hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $user = new User(null, $name, $email, $hashedPassword, $role, $mobile, $address
        ,$amount
    );
        $result = $user->save();
        if ($result === "User saved successfully.") {
            // Get user ID for folder creation
            $userId = $user->id;
            $userFolder = "uploads/users/{$userId}";

            // Ensure the user folder is created
            if (!is_dir($userFolder) && !mkdir($userFolder, 0777, true)) {
                throw new Exception("Failed to create user folder.");
            }

            // Handle profile image upload
            if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Profile image upload is required.");
            }

            $profileImage = $_FILES['profile_image'];
            $profileImageName = time() . "_" . basename($profileImage['name']);
            $profileImagePath = DocumentUploader::upload($profileImage['tmp_name'], $userFolder . '/profile_image', $profileImageName);

            if (!$profileImagePath) {
                throw new Exception("Failed to upload profile image.");
            }

            $user->image = $profileImagePath; // Save the profile image path
            $user->id = $userId;
            $result = $user->update(); // Update the user with the profile image path

            // Handle file uploads for documents
            $requiredFiles = ['nic', 'utility_bills', 'salary_statements'];
            foreach ($requiredFiles as $fileType) {
                if (empty($_FILES[$fileType]) || $_FILES[$fileType]['error'][0] !== UPLOAD_ERR_OK) {
                    throw new Exception("All document uploads are required: {$fileType}.");
                }
            }

            // Upload NIC documents
            $uploadedDocs = [
                'nic' => [],
                'utility_bills' => [],
                'salary_statements' => []
            ];

            foreach (['nic', 'utility_bills', 'salary_statements'] as $fileType) {
                foreach ($_FILES[$fileType]['tmp_name'] as $index => $tmpName) {
                    $fileName = $_FILES[$fileType]['name'][$index];
                    $filePath = DocumentUploader::upload($tmpName, $userFolder . '/' . $fileType, $fileName);
                    if (!$filePath) {
                        throw new Exception("Failed to upload {$fileType} document: {$fileName}.");
                    }
                    $uploadedDocs[$fileType][] = $filePath;
                }
            }

            // Assign documents to the user
            if ($role === 'borrower') {
                $borrower = new Borrower($userId, $name, $email, $hashedPassword, $role, $mobile, $address);
                $borrower->documents = $uploadedDocs;
                $borrower->save(); // Save borrower details and documents
            } elseif ($role === 'lender') {
                $lender = new Lender(
                    $userId,
                    $name,
                    $email,
                    $hashedPassword,
                    $role,
                    $mobile,
                    $address,
                   
                );
                $lender->documents = $uploadedDocs;
                $lender->save(); // Save lender details and documents
            }

            // Send notification (optional, if the Notification class is used)
            //Notification::create($userId, "Welcome to Credit Management System! Your registration is successful.", "email");

            // Success message
            $messageSuccess = "Registration successful! Your documents and profile image are uploaded.";
            // header("Location: index.php?s=$messageSuccess");
            $otp = rand(100000, 999999);
            storeOTPInSession($email, $otp, );
        } else {
            $messageError = $result;
            header("Location: index.php?e=$result");

        }
    } catch (Exception $e) {

        $messageError = $e->getMessage();
        header("Location: index.php?e=$messageError");

    }


}
function storeOTPInSession($email, $otp)
{
    session_start();
    $_SESSION['otp_cache'][$email] = [
        'otp' => $otp,
        'created_at' => time()
    ];
    sendOTPEmail($email, $otp);
    var_dump($_SESSION['otp_cache'][$email]);
    exit;
}
function sendOTPEmail($email, $otp)
{
    $check = Mail::SendOtp($email, $otp);
    if ($check == 1) {
        header("Location: otp.php?s=OTP Sent Successfully&email=" . urlencode($email));
    }
}