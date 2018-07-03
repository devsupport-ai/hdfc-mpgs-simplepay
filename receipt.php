<?php

$errorMessage = "";
$errorCode = "";
$gatewayCode = "";
$result = "";

$tmpArray = array();

// [Snippet] howToDecodeResponse - start
// $response is defined in process.php as the server response
$responseArray = json_decode($response, TRUE);
// [Snippet] howToDecodeResponse - end

// either a HTML error was received
// or response is a curl error
if ($responseArray == NULL) {
    print("JSON decode failed. Please review server response (enable debug in configuration.php).");
    die();
}

// [Snippet] howToParseResponse - start
if (array_key_exists("result", $responseArray))
    $result = $responseArray["result"];
// [Snippet] howToParseResponse - end

// Form error string if error is triggered
if ($result == "FAIL") {
    if (array_key_exists("reason", $responseArray)) {
        $tmpArray = $responseArray["reason"];

        if (array_key_exists("explanation", $tmpArray)) {
            $errorMessage = rawurldecode($tmpArray["explanation"]);
        } else if (array_key_exists("supportCode", $tmpArray)) {
            $errorMessage = rawurldecode($tmpArray["supportCode"]);
        } else {
            $errorMessage = "Reason unspecified.";
        }

        if (array_key_exists("code", $tmpArray)) {
            $errorCode = "Error (" . $tmpArray["code"] . ")";
        } else {
            $errorCode = "Error (UNSPECIFIED)";
        }
    }
} else {
    if (array_key_exists("response", $responseArray)) {
        $tmpArray = $responseArray["response"];
        if (array_key_exists("gatewayCode", $tmpArray))
            $gatewayCode = rawurldecode($tmpArray["gatewayCode"]);
        else
            $gatewayCode = "Response not received.";
    }
}

?>
<!-- 	The following is a simple HTML page to display the response to the transaction.
      This should never be used in your integration -->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<link rel="stylesheet" type="text/css" href="assets/paymentstyle.css"/>
<head>
    <title>Receipt</title>
    <meta http-equiv="Content-Type" content="text/html, charset=iso-8859-1">
</head>
<body>
<br/>
<center><h3>Receipt Page</h3></center>
<br/><br/>

<table width="60%" align="center" cellpadding="5" border="0">
    <?php
    // echo HTML displaying Error headers if error is found
    if ($errorCode != "" || $errorMessage != "") {
        ?>
        <tr class="title">
            <td colspan="2" height="25"><P><strong>&nbsp;Error Response</strong></P></td>
        </tr>
        <tr>
            <td align="right" width="50%"><strong><i><?= $errorCode ?>: </i></strong></td>
            <td width="50%"><?= $errorMessage ?></td>
        </tr>
        <?php
    } else {
        ?>
        <tr class="title">
            <td colspan="2" height="25"><P><strong>&nbsp;<?= $gatewayCode ?></strong></P></td>
        </tr>
        <tr>
            <td align="right" width="50%"><strong><i>Result: </i></strong></td>
            <td width="50%"><?= $result ?></td>
        </tr>
        <?php
    }
    ?>
</table>


<br/><br/>
</body>
</html>