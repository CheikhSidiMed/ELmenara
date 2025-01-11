<?php
include 'db_connection.php';

$activity_id = intval($_GET['activity_id']);

// Vérifier si l'activité existe
$result = $conn->query("SELECT session FROM activities WHERE id = $activity_id");
// Vérifier les erreurs de requête
if (!$result) {
    echo json_encode(['error' => 'Erreur de requête SQL : ' . $conn->error]);
    exit;
}


$data = $result->fetch_assoc();
if (!$data) {
    echo json_encode(['error' => 'Aucune donnée trouvée pour cette activité']);
    exit;
}

// Récupérer le nombre de sessions
$sessionCount = intval($data['session']);
if ($sessionCount <= 0) {
    echo json_encode(['error' => 'Nombre de sessions invalide']);
    exit;
}

$sessions = range(1, $sessionCount);

// Retourner les données en JSON
echo json_encode($sessions);
?>
