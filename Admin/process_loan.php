<?php
// process_loan.php
ob_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

include_once('../Classes/UserAuth.php');
require_once '../Classes/Database.php';
require_once '../Classes/Loan.php';
require_once '../Classes/Notifiactions.php';
require_once '../vendor/autoload.php';

session_start();

if (!UserAuth::isAdminAuthenticated()) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

try {
    $config = require '../config.php';
    
    if (!isset($config['stripe_secret_key'])) {
        throw new Exception('Stripe configuration is missing');
    }

    \Stripe\Stripe::setApiKey($config['stripe_secret_key']);

    // Validate inputs
    $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);

    if (!$id || !$email || !$amount) {
        throw new Exception("Missing required fields");
    }

    // Get loan details
    $db = Database::getConnection();
    $loanQuery = "SELECT l.*, u.name as borrower_name, u.id as borrower_id, 
                  l.noOfInstallments, l.loanPurpose 
                  FROM loans l 
                  JOIN users u ON l.user_id = u.id 
                  WHERE l.id = ?";
    $stmt = $db->prepare($loanQuery);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $loanDetails = $stmt->get_result()->fetch_assoc();
    
    if (!$loanDetails) {
        throw new Exception("Loan not found");
    }

    $db->begin_transaction();

    try {
        // Create mock transfer for demo
        $mockTransferId = 'tr_demo_' . time() . rand(1000, 9999);
        $transferStatus = 'succeeded';

        // Update loan status and acceptance date
        $updateLoanSql = "UPDATE loans SET 
            status = 'approved',
            Accepted_Date = CURRENT_DATE,
            updated_at = NOW()
            WHERE id = ?";
        $stmt = $db->prepare($updateLoanSql);
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update loan status");
        }

        // Record transfer in loan_transfers
        $transferSql = "INSERT INTO loan_transfers (loan_id, amount, stripe_transfer_id, status) 
                       VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($transferSql);
        $stmt->bind_param("idss", $id, $amount, $mockTransferId, $transferStatus);
        if (!$stmt->execute()) {
            throw new Exception("Failed to record transfer");
        }

        // Create multiple notifications for different events
        $notifications = [
            // Loan Approval Notification
            [
                'user_id' => $loanDetails['borrower_id'],
                'message' => "Dear {$loanDetails['borrower_name']}, your loan request for amount $" . 
                           number_format($amount, 2) . " for the purpose of {$loanDetails['loanPurpose']} " .
                           "has been accepted. The number of installments is {$loanDetails['noOfInstallments']}."
            ],
            // Fund Disbursement Notification
            [
                'user_id' => $loanDetails['borrower_id'],
                'message' => "Dear {$loanDetails['borrower_name']}, $" . number_format($amount, 2) . 
                           " has been successfully transferred to your account. Transfer ID: $mockTransferId"
            ],
            // Terms and Conditions Notification
            [
                'user_id' => $loanDetails['borrower_id'],
                'message' => "Dear {$loanDetails['borrower_name']}, please note your monthly installment " .
                           "amount is $" . number_format($loanDetails['InstallmentAmount'], 2) . 
                           ". First payment is due in 30 days."
            ]
        ];

        // Insert all notifications
        $notifySql = "INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())";
        $stmt = $db->prepare($notifySql);

        foreach ($notifications as $notification) {
            $stmt->bind_param("is", $notification['user_id'], $notification['message']);
            if (!$stmt->execute()) {
                throw new Exception("Failed to create notification");
            }
        }

        // Create transaction record
        $transactionSql = "INSERT INTO transactions (user_id, type, amount, status, reference_id) 
                          VALUES (?, 'loan_fee', ?, 'completed', ?)";
        $stmt = $db->prepare($transactionSql);
        $stmt->bind_param("idi", $loanDetails['borrower_id'], $amount, $id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to record transaction");
        }

        $db->commit();

        // Send email notification
        $subject = "Loan Approved and Funds Transferred";
        $message = "Dear " . $loanDetails['borrower_name'] . ",\n\n" .
                  "Your loan application has been approved and $" . number_format($amount, 2) . 
                  " has been transferred to your account.\n\n" .
                  "Loan Details:\n" .
                  "- Loan ID: " . $id . "\n" .
                  "- Amount: $" . number_format($amount, 2) . "\n" .
                  "- Number of Installments: " . $loanDetails['noOfInstallments'] . "\n" .
                  "- Monthly Payment: $" . number_format($loanDetails['InstallmentAmount'], 2) . "\n" .
                  "- Transfer ID: " . $mockTransferId . "\n\n" .
                  "Your first payment is due in 30 days.\n\n" .
                  "Thank you for choosing our services.";
        
        mail($email, $subject, $message);

        echo json_encode([
            'success' => true,
            'message' => 'Loan approved and funds transferred successfully',
            'transfer_id' => $mockTransferId,
            'title' => 'Success!',
            'icon' => 'success'
        ]);

    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error processing loan: " . $e->getMessage());
    echo json_encode([
        'error' => $e->getMessage(),
        'title' => 'Error',
        'icon' => 'error'
    ]);
}