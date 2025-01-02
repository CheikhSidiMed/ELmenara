<?php
include 'db_connection.php';

$term = $_GET['term'];

$employees = [];
$search_query = "
    SELECT e.full_name, e.phone, e.balance, j.job_name
    FROM employees e
    JOIN jobs j ON e.job_id = j.id
    WHERE e.full_name LIKE ? OR e.phone LIKE ?
";

$stmt = $conn->prepare($search_query);
$search_term = '%' . $term . '%';
$stmt->bind_param('ss', $search_term, $search_term);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($employees);
