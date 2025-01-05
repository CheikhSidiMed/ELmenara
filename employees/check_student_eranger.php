


<?php
include('db_connection.php');

if (isset($_POST['phone'])) {
    $searsh = $_POST['phone'];

    // Query to search agents with a phone number that starts with the input
    $query = "SELECT id, name FROM students_etrang 
              WHERE phone LIKE '$searsh%' 
              OR id LIKE '$searsh%' 
              OR NNI LIKE '$searsh%' 
              OR wh LIKE '$searsh%' 
              OR name LIKE '$searsh%'
              LIMIT 5";
    $result = $conn->query($query);

    $matches = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $matches[] = [
                'id' => $row['id'],
                'name' => $row['name']
            ];
        }
    }

    echo json_encode(['matches' => $matches]);
} else {
    echo json_encode(['matches' => []]);
}
?>
