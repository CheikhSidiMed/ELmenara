<?php
include 'db_connection.php';
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}



$type = $_GET['type'] ?? '';
$options = [];

if ($type === 'bank_account') {
    $sql = "SELECT account_id, bank_name FROM bank_accounts";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $options[] = [
            'id' => $row['account_id'],
            'name' => $row['bank_name']
        ];
    }
} elseif ($type === 'fund') {
    $sql = "SELECT id, fund_name FROM funds";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $options[] = [
            'id' => $row['id'],
            'name' => $row['fund_name']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($options);
?>
