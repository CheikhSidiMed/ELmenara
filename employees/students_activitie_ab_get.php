<?php
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


// Vérifie si 'activity_id' est défini et récupère sa valeur en tant qu'entier
if (isset($_GET['activity_id'])) {
    $activity_id = intval($_GET['activity_id']);

    // Exécute la première requête
    $query1 = "
        SELECT s.id, s.student_name, s.phone, a.whatsapp_phone
        FROM student_activities sa
        JOIN students s ON sa.student_id = s.id
        LEFT JOIN agents a ON s.agent_id = a.agent_id
        WHERE sa.activity_id = $activity_id
    ";
    $result1 = $conn->query($query1);
    // Exécute la seconde requête
    $query2 = "
        SELECT s.id, s.name AS student_name, s.phone, s.wh AS whatsapp_phone
        FROM student_activities sa
        JOIN students_etrang s ON sa.student_id_etrang = s.id
        WHERE sa.activity_id = $activity_id
    ";
    $result2 = $conn->query($query2);

    // Vérifie si les requêtes sont exécutées avec succès
    if ($result1 && $result2) {
        // Combine les résultats des deux requêtes
        $combinedResults = array_merge(
            $result1->fetch_all(MYSQLI_ASSOC),
            $result2->fetch_all(MYSQLI_ASSOC)
        );

        // Renvoie les résultats au format JSON
        echo json_encode($combinedResults);
    } else {
        // Renvoie une erreur si l'une des requêtes échoue
        echo json_encode([
            'error' => 'Failed to execute queries',
            'error_details' => $conn->error,
        ]);
    }
} else {
    // Renvoie une erreur si 'activity_id' n'est pas défini
    echo json_encode([
        'error' => 'Missing activity_id parameter',
    ]);
}

$conn->close();
?>
