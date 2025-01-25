<?php
require '../Classes/Stripe.php';
require '../Classes/Database.php';
$info = StripePayment::captureOrder();
if ($info['status']){
    $paymentinfo = $info['session'];
    $totalpay = ($paymentinfo->amount_total)/100;
    $lender_id = $paymentinfo->metadata->lenderid;
    $loan_id = $paymentinfo->metadata->loanid;
    $percentage = $paymentinfo->metadata->percentage;
    $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }
            $query = "INSERT INTO `lendercontribution`(`lenderId`, `loanId`, `LoanPercent`, `LoanAmount`, `RecoveredPrincipal`, `ReturnedInterest`) VALUES (?, ?, ?, ?, 0, 0)";


            // Prepare the statement
            $stmt = $db->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $db->error);
            }

            // Bind parameters
            $stmt->bind_param("iids", $lender_id, $loan_id, $percentage,$totalpay);
            if (!$stmt->execute()) {
                throw new Exception("Error inserting loan contribution: " . $stmt->error);
            }

            // Step 5: Close the statement after execution
            $stmt->close();

            // Step 6: Send email notification
            $subject = "Lender Contribution to Your Loan";
            $body = "Dear Borrower, \n\nA lender has contributed $percentage% to your loan. Thank you for your patience.";
            // Use a valid recipient email here

            // $mail = Mail::sendMail($subject, $body, $email);
            // if (!$mail) {
            //     throw new Exception("Failed to send email notification.");
            // }

            header('Location: /SafeFund/Lender/LoanApplications.php');
            exit();
          
}else{
    echo "Payment failed";
    exit();
}

?>