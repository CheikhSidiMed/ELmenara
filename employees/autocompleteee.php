<?php
// Include database connection
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


$term = $_GET['term'] ?? ''; // Get the search term from the URL, default to an empty string if not set

// Prepare SQL to search by name, phone, or phone_2
$sql = "
    SELECT 
        agent_name, 
        phone 
    FROM 
        agents 
    WHERE 
        agent_name LIKE ? 
        OR phone LIKE ? 
        OR phone_2 LIKE ? 
    ORDER BY phone ASC 
    LIMIT 10
";
$stmt = $conn->prepare($sql);
$likeTerm = '%' . $term . '%';
$stmt->bind_param('sss', $likeTerm, $likeTerm, $likeTerm);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    $agents = [];
    while ($row = $result->fetch_assoc()) {
        // Include agents with the same phone
        $agents[] = [
            'label' => $row['agent_name'] . ' (' . $row['phone'] . ')',
            'value' => $row['phone']
        ];
    }

    echo json_encode($agents);

} else {
    // Return an empty array if the query fails
    echo json_encode([]);
}

// Close the prepared statement and connection
$stmt->close();
$conn->close();
?>
