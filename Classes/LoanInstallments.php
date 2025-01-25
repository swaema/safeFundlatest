<?php
require '../vendor/autoload.php';
require_once '../Classes/Stripe.php';
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
class LoanInstallments
{

    public $id;

    public function dumpConstructorValues()
    {
        var_dump($this);
        exit;

    }
  
    public static function updateStatus($loanId, $payAmount,$principal,$interest,$adminfee)
    {
        try {
            $payAmount = round($payAmount, 2);
            StripePayment::createOrder($payAmount,"paymentsuccess/repaymentsuccess.php","cancel.php","mr test", "this is a description",["loanid" => $loanId,"principal" => $principal,"interest" => $interest,"adminfee" => $adminfee]);
       exit();
         
            
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
    }



    public static function payInstallments($insId)
    {
        try {
            // var_dump("e");
            // exit;
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }

            // Retrieve installment details from database
            $query = "SELECT * from loaninstallments where loanInstallmentsId = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $insId);
            $stmt->execute();
            $result = $stmt->get_result();
            $installment = $result->fetch_assoc();
            $payAmount = $installment['payable_amount'];
            $stmt->close();
            $clientId = 'AeBgOimKzCJR4HozGn2UMxyeBvpiaojII2MuR4_XvWhIPXEwD5SbcWvjV0PhxI51vvq-9grpd0jLkOZ2';  // Replace with your PayPal client ID
            $clientSecret = 'EKn6WyCJI-cevNCMQLdnEtAx-Y302oHGXwa83dL1DHAFjJLSj2m0hCn1mjNHOYW_Ls2SV_oPnvoi8ovU';  // Replace with your PayPal client secret
            $environment = new SandboxEnvironment($clientId, $clientSecret);
            $client = new PayPalHttpClient($environment);

            // Create a new order for PayPal payment
            $request = new OrdersCreateRequest();
            $request->prefer('return=representation');
            $request->body = [
                "intent" => "CAPTURE",
                "purchase_units" => [
                    [
                        "amount" => [
                            "currency_code" => "USD",  // Change this to your currency if needed
                            "value" => $payAmount // The installment amount to be paid
                        ]
                    ]
                ],
                "application_context" => [
                    "cancel_url" => "http://localhost/SafeFund/SafeFund/Borrower/cancel.php",
                    "return_url" => "http://localhost/SafeFund/SafeFund/Borrower/loanInstallments.php?insId=" . $insId
                ]
            ];

            // Execute PayPal order creation
            $response = $client->execute($request);
            $orderId = $response->result->id;

            // Check if the payment was created successfully
            if ($response->result->status === 'CREATED') {
                // Redirect user to PayPal for payment approval
                foreach ($response->result->links as $link) {
                    if ($link->rel === 'approve') {
                        // Redirect to PayPal approval URL
                        header("Location: " . $link->href);
                        exit(); // Stop further execution after redirection
                    }
                }
            } else {
                var_dump("PayPal order creation failed. Status: " . $response->result->status);
                throw new Exception("PayPal order creation failed. Status: " . $response->result->status);
            }
        } catch (Exception $e) {
            error_log("Error processing payment: " . $e->getMessage());
            return "Error: " . $e->getMessage();
        }
    }


    public static function loanInstallmentbyLoanId($loanid)
    {
        $db = Database::getConnection();
        if ($db === null) {
            throw new Exception("Database connection failed");
        }
        $query = "SELECT * from loaninstallments
              where loan_id =? and status = ?
             ";
        $stmt = $db->prepare($query);
        $status = "Paid";
        $stmt->bind_param("is", $loanid, $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $installments = [];
        while ($data = $result->fetch_assoc()) {
            $installments[] = $data;
        }
        $stmt->close();
        return $installments;
    }
    public static function InstallmentAmountbyLoanId($loanid)
{
    $db = Database::getConnection();
    if ($db === null) {
        throw new Exception("Database connection failed");
    }

    // Query to fetch the total amount paid and the last payment date by loan_id with status = 'paid'
    $query = "SELECT SUM(payable_amount) AS total_paid, MAX(pay_date) AS last_payment_date 
              FROM loaninstallments 
              WHERE loan_id = ?";

    // Prepare the statement
    $stmt = $db->prepare($query);
    if ($stmt === false) {
        throw new Exception("Failed to prepare statement: " . $db->error);
    }

    // Bind the loan_id parameter
    $stmt->bind_param("i", $loanid);

    // Execute the query
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    // Fetch a single record
    $installment = $result->fetch_assoc();

    // Close the statement
    $stmt->close();

    // Return the single installment record or null if not found
    return $installment ? $installment : null;
}
    
    public static function loanInstallment($id)
    {
        $db = Database::getConnection();
        if ($db === null) {
            throw new Exception("Database connection failed");
        }
        $query = "SELECT * from loaninstallments
              where status = ? and user_id =?
             ";
        $stmt = $db->prepare($query);
        $status = "Paid";
        $stmt->bind_param("si", $status,$id);
        $stmt->execute();
        $result = $stmt->get_result();
        $installments = [];
        while ($data = $result->fetch_assoc()) {
            $installments[] = $data;
        }
        $stmt->close();
        return $installments;
    }

    public static function userLoanInstllments()
    {
        try {
            $id = $_SESSION['user_id'];
            $db = Database::getConnection();
            if ($db === null) {
                throw new Exception("Database connection failed");
            }
            $query = "SELECT l.*,u.*,li.*,li.status as inStatus 
            FROM loans l 
                  INNER JOIN loaninstallments li 
                  ON li.loan_id = l.id 
                  inner join users u on u.id = li.user_id
                  where li.user_id =?
                 ";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $installments = [];
            while ($data = $result->fetch_assoc()) {
                $installments[] = $data;  // Push the entire associative array
            }

            $stmt->close();
            return $installments;
        } catch (Exception $e) {
            error_log("Error updating loan: " . $e->getMessage());
            return "Error: " . $e->getMessage();
        }

    }
}