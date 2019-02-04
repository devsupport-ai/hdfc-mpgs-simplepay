<?php
error_reporting(0);
$dbConfig = array();

$dbConfig["hostname"] = "{{=it.hostname}}";
$dbConfig["username"] = "{{=it.username}}";
$dbConfig["password"] = "{{=it.password}}";
$dbConfig["database"] = "{{=it.database}}";
$dbConfig["transaction_table_name"] = "{{=it.transactionsTableName}}";
$dbConfig["transaction_id_column_name"] = "{{=it.transactionIdColumnName}}";
$dbConfig["amount_column_name"] = "{{=it.amountColumnName}}";

$transactionId = $_POST["transaction_id"];
$amount = $_POST["amount"];

function checkValidString($str)
{
    return !!$str && strlen($str) > 2 && strstr($str, ' ') === false;
}

foreach ($dbConfig as $item) {
    if (!checkValidString($item)) {
        exit("audit script not configured properly");
    }
}

if (!$transactionId || !$amount) {
    exit("invalid request");
}

$checks = ["has_duplicates", "has_recorded_transaction", "amount_matches"];

$response = [
    "has_duplicates" => true,
    "has_recorded_transaction" => false,
    "amount_matches" => false,
    "result" => "fail",
    "message" => ""
];

function matches($amount1, $amount2)
{
    $c = $amount2 * 100;
    return $amount1 == $amount2 || $amount1 == $c;
}

$mysqli = new mysqli($dbConfig["hostname"], $dbConfig["username"], $dbConfig["password"], $dbConfig["database"]);

/* check connection */
if ($mysqli->connect_errno) {
    $response["message"] = "Failed to connect to the database";
    printf(json_encode($response));
    exit();
}

/* Select queries return a resultset */
$query = "select count(*) as count from (select " . $dbConfig["transaction_id_column_name"] . " from " . $dbConfig["transaction_table_name"] . " group by " . $dbConfig["transaction_id_column_name"] . " having count(*) > 1) non_unique";
if ($result = $mysqli->query($query)) {
    $row = $result->fetch_array();
    if ($row["count"] == 0) {
        $response["has_duplicates"] = false;
    } else {
        $response["message"] = $row["count"] . " duplicate transactions found";
    }
    /* free result set */
    $result->close();
} else {
    $response["message"] = "Failed to fire uniqueness check query";
}

$query = "select " . $dbConfig["transaction_id_column_name"] . ", " . $dbConfig["amount_column_name"] . " as amount from " . $dbConfig["transaction_table_name"] . " where " . $dbConfig["transaction_id_column_name"] . " = '" . $transactionId . "'";
if ($result = $mysqli->query($query, MYSQLI_USE_RESULT)) {
    $row = $result->fetch_array();
    $response["has_recorded_transaction"] = true;

    if (matches($row["amount"], $amount)) {
        $response["amount_matches"] = true;
    } else {
        $response["message"] = "Amount of the recorded transaction did not match";
    }

    $result->close();
} else {
    $response["message"] = "Failed to fire transaction validation query";
}

if (!$response["has_duplicates"] && $response["has_recorded_transaction"] && $response["amount_matches"]) {
    $response["result"] = "pass";
}

printf(json_encode($response));
$mysqli->close();
exit();
