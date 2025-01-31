<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../db_connection.php';
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: ../home.php");
    exit;
}
$search = isset($_GET['term']) ? $_GET['term'] : '';

$sql = "SELECT g.id, g.name, g.phone, g.amount_sponsored, g.balance, g.donate_id, d.account_name, d.account_number
    FROM garants AS g
    LEFT JOIN donate_accounts AS d ON g.donate_id = d.id
    WHERE g.name LIKE ? OR g.phone LIKE ?";
$stmt = $conn->prepare($sql);
$searchTerm = '%' . $search . '%';
$stmt->bind_param('ss', $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$accounts = [];
while ($row = $result->fetch_assoc()) {
    $accounts[] = [
        'id' => $row['id'],
        'value' => $row['name'],
        'label' => $row['phone'] . ' - ' . $row['name'],
        'account_number' => $row['phone'],
        'account_name' => $row['account_name'],
        'name' => $row['name'],
        'category' => $row['name'],
        'amount_sponsored' => $row['amount_sponsored'],
        'account_balance' => $row['balance']
    ];
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($accounts);

