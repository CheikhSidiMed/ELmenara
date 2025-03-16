<?php
include '../db_connection.php';

$conn->set_charset('utf8mb4');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = $_POST['student_id'];
    $du = $_POST['du'];
    $au = $_POST['au'];
    $numAbAc = $_POST['num_ab_ac'];
    $numAbNo = $_POST['num_ab_no'];

    try {
        // Vérifier si l'entrée existe déjà
        $checkSql = "SELECT id FROM ab_mahraa WHERE student_id = ? AND du = ? AND au = ?";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param('iss', $studentId, $du, $au);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();

        if ($exists) {
            // Mise à jour de l'existant
            $updateSql = "UPDATE ab_mahraa SET num_ab_ac = ?, num_ab_no = ? WHERE student_id = ? AND du = ? AND au = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param('iiiss', $numAbAc, $numAbNo, $studentId, $du, $au);
        } else {
            // Insertion d'une nouvelle ligne
            $insertSql = "INSERT INTO ab_mahraa (student_id, du, au, num_ab_ac, num_ab_no) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insertSql);
            $stmt->bind_param('issii', $studentId, $du, $au, $numAbAc, $numAbNo);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur SQL: ' . $stmt->error]);
        }
        $stmt->close();

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
