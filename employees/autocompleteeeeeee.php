<?php
include 'db_connection.php';

$term = isset($_GET['term']) ? $_GET['term'] : '';

$sql = "SELECT id, full_name, phone, subscription_date FROM employees WHERE full_name LIKE ? OR  phone LIKE ? OR id_number LIKE ?";
$stmt = $conn->prepare($sql);
$like_term = "%" . $term . "%";
$stmt->bind_param("sss", $like_term, $like_term, $like_term);
$stmt->execute();
$result = $stmt->get_result();

$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = [
        'id' => $row['id'],
        'label' => $row['full_name'],
        'value' => $row['full_name'], // Display the name in the search box
        'phone' => $row['phone'],
        'subscription_date' => $row['subscription_date']
    ];
}

echo json_encode($employees);
?>
