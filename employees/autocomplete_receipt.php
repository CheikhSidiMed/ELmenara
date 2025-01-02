<?php
// Include the database connection file
include 'db_connection.php';






$query = $_GET['query'] ?? '';

if ($query) {
    $stmt = $conn->prepare("
        SELECT DISTINCT student_id AS suggestion 
        FROM receipts 
        WHERE student_id LIKE ? 

        UNION

        SELECT DISTINCT agent_name AS suggestion 
        FROM agents 
        WHERE agent_id LIKE ? OR agent_name LIKE ? OR phone LIKE ? 

        UNION

        SELECT DISTINCT student_name AS suggestion 
        FROM students 
        WHERE id LIKE ? OR student_name LIKE ? OR phone LIKE ? 

        UNION

        SELECT DISTINCT receipt_date AS suggestion 
        FROM receipts 
        WHERE receipt_date LIKE ? 

        LIMIT 10;

    ");
    $likeQuery = '%' . $query . '%';
    $stmt->bind_param('ssssssss', $likeQuery, $likeQuery, $likeQuery, $likeQuery, $likeQuery, $likeQuery, $likeQuery, $likeQuery);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        echo "<div class='autocomplete-suggestion' onclick=\"setInput('" . htmlspecialchars($row['suggestion'], ENT_QUOTES) . "')\">" . htmlspecialchars($row['suggestion']) . "</div>";
    }
}
?>
