<?php

/* Main controller page

1. Create 1 MerchantConfiguration object for each merchant ID
2. Create 1 Parser object
3. Call Parser object FormRequest method to form the request that will be sent to the payment server
4. Parse the formed reqest to SendTransaction method to attempt to send the transaction to the payment server
5. Store the received transaction response in a variable
6. Include receipt page which will output the response HTML and parse the server response

*/

include "payment_configuration.php";
include "payment_connection.php";

$amount = 500;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = "PUT";
    $orderId = 'order-' . bin2hex(openssl_random_pseudo_bytes(8));
    $transactionId = 'transaction-' . bin2hex(openssl_random_pseudo_bytes(8));
    $customUri .= "/order/" . $orderId;
    $customUri .= "/transaction/" . $transactionId;
    $merchantObj = new Merchant($configArray);
    $parserObj = new Parser($merchantObj);

    $expiry = $_POST['card_expiry'];
    $expiryParts = explode("/", $expiry);
    $cardMonth = $expiryParts[0];
    $cardYear = $expiryParts[1];

    $formData = [];
    $formData["sourceOfFunds"] = [
        "type" => "CARD",
        "provided" => [
            "card" => [
                "number" => $_POST["card_number"],
                "expiry" => [
                    "month" => $cardMonth,
                    "year" => $cardYear,
                ],
                "securityCode" => $_POST["card_cvv"],
            ]
        ]
    ];
    $formData["transaction"] = [
        "amount" => $amount,
        "currency" => "INR",
    ];
    $formData["order"] = [
        "reference" => $orderId,
    ];
    $formData["apiOperation"] = "PAY";

    $request = $parserObj->ParseRequest($formData);

} else {
    die("invalid request");
}

// form transaction request

// if no post received from HTML page (parseRequest returns "" upon receiving an empty $_POST)
if ($request == "")
    die();

// print the request pre-send to server if in debug mode
// this is used for debugging only. This would not be used in your integration, as DEBUG should be set to FALSE
if ($merchantObj->GetDebug())
    echo $request . "<br/><br/>";

// forms the requestUrl and assigns it to the merchantObj gatewayUrl member
// returns what was assigned to the gatewayUrl member for echoing if in debug mode
$requestUrl = $parserObj->FormRequestUrl($merchantObj, $customUri);

// this is used for debugging only. This would not be used in your integration, as DEBUG should be set to FALSE
if ($merchantObj->GetDebug())
    echo $requestUrl . "<br/><br/>";


// attempt sending of transaction
// $response is used in receipt page, do not change variable name
$response = $parserObj->SendTransaction($merchantObj, $request, $method);

// print response received from server if in debug mode
// this is used for debugging only. This would not be used in your integration, as DEBUG should be set to FALSE
if ($merchantObj->GetDebug()) {
    // replace the newline chars with html newlines
    $response = str_replace("\n", "<br/>", $response);
    echo $response . "<br/><br/>";
    die();
}


// the receipt page is included and displayed here.
// in your integration, you would most likely also want process the transaction response, and make appropriate updates
// you can see how to parse and retrieve the results and other fields in the transaction at the top of payment_receipt.php
include "payment_receipt.php";

?>