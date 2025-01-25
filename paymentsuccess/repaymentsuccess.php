<?php
session_start();
require '../Classes/Stripe.php';
require '../Classes/Database.php';

try {
    // Base URL construction
    $base_url = sprintf(
        "%s://%s%s",
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
        $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'],
        '/'
    );

    // Capture and validate Stripe payment
    $info = StripePayment::captureOrder();
    if (!$info['status']) {
        throw new Exception("Payment verification failed");
    }

    $paymentinfo = $info['session'];
    $payAmount = ($paymentinfo->amount_total) / 100;
    
    // Extract metadata
    $metadata = $paymentinfo->metadata;
    $loanId = $metadata->loanid;
    $principal = $metadata->principal;
    $interestRate = $metadata->interest;
    $interest = ($interestRate / 100) * $principal;
    $adminfee = $metadata->adminfee;
    $user_id = $_SESSION['user_id'];

    // Get database connection
    $db = Database::getConnection();
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Start transaction
    $db->begin_transaction();

    try {
        // Insert loan installment
        $query = "INSERT INTO `loaninstallments` 
                 (`user_id`, `loan_id`, `payable_amount`, `pay_date`, `principal`, `interest`, `admin_fee`, `status`) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        if (!$stmt) {
            throw new Exception("Failed to prepare installment statement: " . $db->error);
        }

        $payDate = date("Y-m-d");
        $status = "Paid";

        $stmt->bind_param("iidsiiis", 
            $user_id, $loanId, $payAmount, $payDate, 
            $principal, $interestRate, $adminfee, $status
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert installment record: " . $stmt->error);
        }

        $insId = $db->insert_id;
        $stmt->close();

        // Process contributors
        $contributorsQuery = "SELECT lenderId, LoanPercent, RecoveredPrincipal, ReturnedInterest 
                            FROM lendercontribution WHERE loanId = ?";
        $contributorsStmt = $db->prepare($contributorsQuery);
        if (!$contributorsStmt) {
            throw new Exception("Failed to prepare contributors statement");
        }

        $contributorsStmt->bind_param("i", $loanId);
        if (!$contributorsStmt->execute()) {
            throw new Exception("Failed to execute contributors query");
        }

        $contributorsResult = $contributorsStmt->get_result();

        // Process each contributor's share
        while ($row = $contributorsResult->fetch_assoc()) {
            $lenderId = $row['lenderId'];
            $loanPercent = $row['LoanPercent'];
            $recoveredPrincipal = $row['RecoveredPrincipal'];
            $returnedInterest = $row['ReturnedInterest'];

            // Calculate amounts
            $stakeAmount = ($payAmount * $loanPercent) / 100;
            $interestAmount = ($stakeAmount * $interestRate) / 100;
            $earning = $stakeAmount - $interestAmount;
            $stakeInterest = ($interest * $loanPercent) / 100;
            $stakePrincipal = ($principal * $loanPercent) / 100;

            // Update consolidated fund
            $currentAmountQuery = "SELECT Amount, Earning FROM consoledatedfund WHERE user_id = ?";
            $currentAmountStmt = $db->prepare($currentAmountQuery);
            $currentAmountStmt->bind_param("i", $lenderId);
            $currentAmountStmt->execute();
            $currentData = $currentAmountStmt->get_result()->fetch_assoc();

            if (!$currentData) {
                throw new Exception("No fund record found for lender: " . $lenderId);
            }

            $newAmount = $currentData['Amount'] + $stakeAmount;
            $newEarning = $currentData['Earning'] + $earning;

            // Update consolidated fund
            $updateFundQuery = "UPDATE consoledatedfund SET Amount = ?, Earning = ? WHERE user_id = ?";
            $updateFundStmt = $db->prepare($updateFundQuery);
            $updateFundStmt->bind_param("ddi", $newAmount, $newEarning, $lenderId);
            
            if (!$updateFundStmt->execute()) {
                throw new Exception("Failed to update consolidated fund for lender: " . $lenderId);
            }

            // Update lender contribution
            $recoveredPrincipal += $stakePrincipal;
            $returnedInterest += $stakeInterest;
            
            $updateContributionQuery = "UPDATE lendercontribution 
                                      SET RecoveredPrincipal = ?, ReturnedInterest = ? 
                                      WHERE lenderId = ?";
            $updateContributionStmt = $db->prepare($updateContributionQuery);
            $updateContributionStmt->bind_param("iii", 
                $recoveredPrincipal, $returnedInterest, $lenderId
            );

            if (!$updateContributionStmt->execute()) {
                throw new Exception("Failed to update contribution for lender: " . $lenderId);
            }

            $currentAmountStmt->close();
            $updateFundStmt->close();
            $updateContributionStmt->close();
        }

        // Process admin fee
        $adminFundQuery = "SELECT cf.*, u.id as admin_id 
                          FROM consoledatedfund cf 
                          INNER JOIN users u ON cf.user_id = u.id 
                          WHERE u.role = 'admin' LIMIT 1";
        $adminFundResult = $db->query($adminFundQuery);
        $adminData = $adminFundResult->fetch_assoc();

        if ($adminData) {
            $adminPayAmount = ($payAmount * 2) / 100;
            $adminInterestAmount = ($adminPayAmount * $interestRate) / 100;
            $adminEarningAmount = $adminPayAmount - $adminInterestAmount;
            
            $newAdminAmount = $adminData['Amount'] + $adminPayAmount;
            $newAdminEarning = $adminData['Earning'] + $adminEarningAmount;

            $updateAdminQuery = "UPDATE consoledatedfund 
                                SET Amount = ?, Earning = ? 
                                WHERE user_id = ?";
            $updateAdminStmt = $db->prepare($updateAdminQuery);
            $updateAdminStmt->bind_param("ddi", 
                $newAdminAmount, $newAdminEarning, $adminData['admin_id']
            );

            if (!$updateAdminStmt->execute()) {
                throw new Exception("Failed to update admin fund");
            }
        }

        // Commit transaction
        $db->commit();

        // Redirect to success page
        header("Location: " . $base_url . "Borrower/ActiveLoan.php?status=success");
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        throw $e;
    }

} catch (Exception $e) {
    // Log error for debugging
    error_log("Payment processing error: " . $e->getMessage());
    
    // Redirect with error message
    $error_message = urlencode("Payment processing failed: " . $e->getMessage());
    header("Location: " . $base_url . "Borrower/ActiveLoan.php?status=error&message=" . $error_message);
    exit();
}
?>