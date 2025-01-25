<?php


require '../vendor/autoload.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

class StripePayment
{
    private static $apiKey = 'sk_test_51QjrnHEtuCL1PUfK5KYJEfWivuvBYbTgrsyYuaDXkzqLLa7zFvKqf1O4xkL9m3FsJ5DQNsG5c93mKrHL4kNsAl7n00kaKosrU2';

    private static $currency = 'GBP'; 

    public static function createOrder($amount, $returnUrl, $cancelUrl, $Name, $Description, $metadata = [])
    {
        \Stripe\Stripe::setApiKey(self::$apiKey);

        // Configure the return and cancel URLs
        $current_url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $parsed_url = parse_url($current_url);
        // $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . '/SafeFund/';

        $base_url = sprintf(
            "%s://%s%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'],
            '/'
        );
        
        $success_url = $base_url . 'paymentsuccess/repaymentsuccess.php?session_id={CHECKOUT_SESSION_ID}&loan_id=' . $loanId;

        $returnUrl = $base_url . $returnUrl;
        $cancelUrl = $base_url . $cancelUrl;

        try {
            $checkout_session = \Stripe\Checkout\Session::create([
                "mode" => "payment",
                "locale" => "en",
                "payment_method_types" => ["card"],
                'customer_email' => $_SESSION['user_email'],
                "success_url" => $success_url,
                "cancel_url" => $cancelUrl,
                "line_items" => [
                    [
                        "price_data" => [
                            "currency" => self::$currency,
                            "product_data" => [
                                "name" => $Name,
                                "description" => $Description,
                            ],
                            "unit_amount" => $amount * 100,
                        ],
                        "quantity" => 1,
                    ]
                ],
                "metadata" => $metadata,
            ]);

            $session_token = $checkout_session->id;
            unset($_SESSION["payment session id"]);
            $_SESSION["payment session id"] = $session_token;
            http_response_code(303);
            header('Location:' . $checkout_session->url);
            exit();

        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Error creating Stripe order: " . $e->getMessage());
            var_dump($e);
            exit;
        }
    }

    public static function captureOrder()
    {
        // Use the 'b' API key for capturing order
        \Stripe\Stripe::setApiKey(self::$apiKey);

        // Retrieve the session ID from the session storage
        $sessionId = $_SESSION["payment session id"];

        try {
            // Retrieve the checkout session using the session ID
            $session = \Stripe\Checkout\Session::retrieve($sessionId);

            // Check if the payment was successful
            if ($session->payment_status == 'paid') {
                // Log session details for debugging or record-keeping
                file_put_contents('stripe_capture_order_response.log', print_r($session, true));

                return array("status" => true, "session" => $session);
            } else {
                return array("status" => false);
            }
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Log the error message for debugging purposes
            error_log("Error capturing Stripe order: " . $e->getMessage());
            var_dump($e);
            exit;
        }
    }

    public static function transferFunds($amount, $destinationAccountId, $currency = 'GBP')
    {
        // Use the 'b' API key for transferring funds
        \Stripe\Stripe::setApiKey(self::$apiKey);
    
        try {
            // Ensure that the destination account is a connected account
            // Initiate a transfer from your platform's Stripe account to the connected account
            $transfer = \Stripe\Transfer::create([
                'amount' => $amount * 100, // Amount to transfer in the smallest currency unit (e.g., cents)
                'currency' => $currency, // Currency of the transfer
                'destination' => $destinationAccountId, // The connected account's ID
                'transfer_group' => 'payment_intent_transfer', // Optional, a unique identifier for the transfer group
            ]);
    
            // Log the successful transfer for debugging purposes
            file_put_contents('stripe_transfer_response.log', print_r($transfer, true));
    
            return true; // Return true on success
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Log the error message for debugging purposes
            error_log("Error transferring funds: " . $e->getMessage());
            
            return false; // Return false if an error occurs
        }
    }
    
}
