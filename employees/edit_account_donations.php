<?php
// Include database connection
include 'db_connection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $account_id = $_POST['edit_account_id'];
    $account_name = $_POST['edit_account_name'];
    $category = $_POST['edit_category'];
    $account_balance = $_POST['edit_account_balance'];

    // Validate inputs (basic validation)
    if (!empty($account_id) && !empty($account_name) && !empty($category) && is_numeric($account_balance)) {
        // Update query
        $sql = "UPDATE donate_accounts 
                SET account_name = ?, 
                    category = ?, 
                    account_balance = ? 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssdi', $account_name, $category, $account_balance, $account_id);

        // Execute the query
        if ($stmt->execute()) {
            // Redirect back with success message
            header('Location: manage_donations.php?message=Account updated successfully');
            exit;
        } else {
            // Redirect back with error message
            header('Location: manage_donations.php?error=Failed to update account');
            exit;
        }
    } else {
        // Redirect back with validation error
        header('Location: manage_donations.php?error=Invalid input data');
        exit;
    }
}

// Redirect if accessed directly
header('Location: manage_donations.php');
exit;
?>
