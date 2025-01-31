<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


// Get data for the CSV
$transactions = json_decode($_POST['transactions'], true);

// Set headers for download
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=transaction_report.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Output column headers
fputcsv($output, ['Date', 'Description', 'Debit', 'Credit', 'Balance']);

// Output transaction data
foreach ($transactions as $transaction) {
    fputcsv($output, [$transaction['transaction_date'], $transaction['transaction_description'], $transaction['debit'], $transaction['credit'], $transaction['balance']]);
}

fclose($output);
exit();
