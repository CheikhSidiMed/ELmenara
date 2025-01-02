<?php
// Include the database connection file
include 'db_connection.php';

// Get the POST data from the form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accountNumber = $_POST['Nbr'];
    $accountName = $_POST['Nom'];
    $category = $_POST['Category'];

    // Prepare the SQL insert query (account_balance is not included and will default to 0)
    $sql = "INSERT INTO expense_accounts (account_number, account_name, category, account_balance) 
            VALUES (?, ?, ?, 0)";  // Account balance is set to 0 by default

    // Prepare the statement
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error preparing SQL: ' . $conn->error]);
        exit;
    }

    // Bind the parameters
    $stmt->bind_param('iss', $accountNumber, $accountName, $category);

    // Execute the statement
    if ($stmt->execute()) {
        // Return success message
        echo json_encode(['success' => true, 'message' => 'تم إضافة الحساب بنجاح']);
    } else {
        // Return error message
        echo json_encode(['success' => false, 'message' => 'خطأ أثناء إضافة الحساب: ' . $conn->error]);
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>
