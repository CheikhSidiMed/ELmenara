<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connection.php';
session_start();

if (!isset($_SESSION['userid'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    exit;
}

header('Content-Type: application/json'); // On définit le Content-Type pour le JSON

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupération des données
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $class_id = $_POST['class'] ?? 0;
    $branches = $_POST['branches'];

    // Préparation de l'insertion de l'utilisateur
    $stmt = $conn->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Erreur préparation: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("ssi", $username, $password, $role);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        // Préparation de l'insertion dans user_branch
        $br_us_stmt = $conn->prepare("INSERT INTO user_branch (branch_id, class_id, user_id) VALUES (?, ?, ?)");
        if (!$br_us_stmt) {
            echo json_encode(['success' => false, 'message' => 'Erreur préparation user_branch: ' . $conn->error]);
            exit;
        }
        foreach ($branches as $branch_id) {
            $br_us_stmt->bind_param("iii", $branch_id, $class_id, $user_id);
            if (!$br_us_stmt->execute()) {
                echo json_encode(['success' => false, 'message' => 'Erreur insertion user_branch: ' . $br_us_stmt->error]);
                exit;
            }
        }
        $br_us_stmt->close(); // Fermer une seule fois
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur insertion user: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}
?>
