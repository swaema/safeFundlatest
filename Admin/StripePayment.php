<?php


require_once '../vendor/autoload.php';
require_once '../Classes/Database.php';

class StripePayment {
    private static $stripe;
    private static $config;

    public static function initialize() {
        self::$config = require '../config.php';
        \Stripe\Stripe::setApiKey(self::$config['stripe_secret_key']);
    }

    public static function transferFunds($amount, $destinationAccount) {
        try {
            self::initialize();

            // Log the attempt
            self::logMessage("Attempting transfer of $amount to account $destinationAccount");

            // Create the transfer
            $transfer = \Stripe\Transfer::create([
                'amount' => (int)($amount * 100), // Convert to cents
                'currency' => 'usd',
                'destination' => $destinationAccount,
                'description' => 'Loan disbursement transfer'
            ]);

            // Log successful transfer
            self::logMessage("Transfer successful: {$transfer->id}");

            // Record transfer in database
            self::recordTransfer($transfer, $amount, $destinationAccount);

            return true;

        } catch (\Stripe\Exception\ApiErrorException $e) {
            self::logMessage("Stripe API Error: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            self::logMessage("General Error: " . $e->getMessage());
            return false;
        }
    }

    private static function recordTransfer($transfer, $amount, $destinationAccount) {
        try {
            $db = Database::getConnection();
            $sql = "INSERT INTO stripe_transfers (
                stripe_transfer_id, 
                amount, 
                destination_account,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, NOW())";

            $stmt = $db->prepare($sql);
            $stmt->bind_param(
                "sdss",
                $transfer->id,
                $amount,
                $destinationAccount,
                $transfer->status
            );
            $stmt->execute();

            if ($stmt->error) {
                self::logMessage("Database Error: " . $stmt->error);
            }

            $stmt->close();

        } catch (Exception $e) {
            self::logMessage("Database Record Error: " . $e->getMessage());
        }
    }

    private static function logMessage($message) {
        $logFile = __DIR__ . '/stripe_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        
        error_log($logMessage, 3, $logFile);
    }

    public static function createTransfersTable() {
        try {
            $db = Database::getConnection();
            $sql = "CREATE TABLE IF NOT EXISTS stripe_transfers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                stripe_transfer_id VARCHAR(255) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                destination_account VARCHAR(255) NOT NULL,
                status VARCHAR(50) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (stripe_transfer_id)
            )";
            
            if (!$db->query($sql)) {
                self::logMessage("Table creation failed: " . $db->error);
            }
        } catch (Exception $e) {
            self::logMessage("Table creation error: " . $e->getMessage());
        }
    }
}

// Create the transfers table when the class is loaded
StripePayment::createTransfersTable();