<?php
// Include the database connection
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


// Get the search query from the GET request
$search = isset($_GET['term']) ? $_GET['term'] : '';

// Prepare and execute the SQL query to fetch matching accounts
$sql = "SELECT id, account_number, account_name, category, account_balance FROM offer_accounts WHERE account_name LIKE ? OR account_number LIKE ?";
$stmt = $conn->prepare($sql);
$searchTerm = '%' . $search . '%';
$stmt->bind_param('ss', $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the matching accounts
$accounts = [];
while ($row = $result->fetch_assoc()) {
    $accounts[] = [
        'id' => $row['id'], // Include the primary key account_id
        'value' => $row['account_name'],
        'label' => $row['account_number'] . ' - ' . $row['account_name'],
        'account_number' => $row['account_number'],
        'account_name' => $row['account_name'],
        'category' => $row['category'],
        'account_balance' => $row['account_balance']
    ];
}

// Close the connection
$stmt->close();
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($accounts);
