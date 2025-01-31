<?php
ob_clean();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../db_connection.php';


$search = isset($_GET['term']) ? $_GET['term'] : '';

$sql = "SELECT g.id, g.name, g.phone
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
        'label' => $row['phone'] . ' - ' . $row['name']
    ];
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($accounts);

