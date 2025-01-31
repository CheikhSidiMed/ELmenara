<?php
include 'db_connection.php';


session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}

$term = isset($_GET['term']) ? $_GET['term'] : '';

// Prepare the query to search agents based on the search term
$sql = "SELECT * FROM agents WHERE agent_name LIKE ? OR phone LIKE ? OR phone_2 LIKE ? OR whatsapp_phone LIKE ?";
$like_term = "%$term%";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssss', $like_term, $like_term, $like_term, $like_term);
$stmt->execute();
$result = $stmt->get_result();

// Generate the HTML for the agent table rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['agent_id']}</td>
                <td>{$row['phone']}</td>
                <td>{$row['agent_name']}</td>
                <td>{$row['phone_2']}</td>
                <td>{$row['profession']}</td>
                <td>{$row['whatsapp_phone']}</td>
                <td>
                    <button class='btn btn-sm btn-primary edit-btn' data-id='{$row['agent_id']}'
                            data-name='{$row['agent_name']}' 
                            data-phone='{$row['phone']}' 
                            data-phone2='{$row['phone_2']}' 
                            data-job='{$row['profession']}' 
                            data-whatsapp='{$row['whatsapp_phone']}'>
                        تعديل
                    </button>
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='7' class='text-center'>لا توجد بيانات</td></tr>";  // Note: colspan is now 7 to include the new column for the Edit button
}

$conn->close();
?>
