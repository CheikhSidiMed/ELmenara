<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Connexion à la base de données
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


// Vérification de la réception de employee_id
if (isset($_POST['employee_id'])) {

    $employee_id = intval($_POST['employee_id']);
    $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : ''; 
$end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';     $total_credit = isset($_POST['total_credit']) ? floatval($_POST['total_credit']) : 0.0; // Conversion en float
    $total_debit = isset($_POST['total_debit']) ? floatval($_POST['total_debit']) : 0.0;
    $calculated_balance = isset($_POST['calculated_balance']) ? floatval($_POST['calculated_balance']) : 0.0;

    if ($employee_id <= 0) {
        echo "ID employé invalide.";
        exit;
    }

    $stmt = $conn->prepare("
        SELECT e.full_name, j.job_name, e.subscription_date, e.balance
        FROM employees e
        JOIN jobs j ON e.job_id = j.id
        WHERE e.id = ?
    ");
    $stmt->bind_param('i', $employee_id);
    $stmt->execute();
    $stmt->bind_result($full_name, $job_name, $registration_date, $balance);
    $stmt->fetch();
    $stmt->close();
    
    $header = "
        <div style='text-align: center; margin-bottom: -20px;'>
            <h3>معلومات الموظف</h3>
            <p><strong>الاسم الكامل: </strong>{$full_name}</p>
            <p><strong>الوظيفة: </strong>{$job_name}</p>
            <p><strong>رصيد الحساب: </strong>{$balance}</p>
            <p><strong>تاريخ التسجيل: </strong>" . date('d-m-Y', strtotime($registration_date)) . "</p>
            <p><strong>من: </strong>{$start_date}<strong> -/- إلى: </strong>{$end_date}</p>

        </div>";
    $contents = '';
    $content_s = '';
    $sql = "SELECT DATE_FORMAT(transaction_date, '%d-%m-%Y') as transaction_date, 
    transaction_description, 
    amount, 
    transaction_type
FROM transactions
WHERE employee_id = ?";

if (!empty($start_date) && !empty($end_date)) {
$sql .= " AND transaction_date BETWEEN ? AND ?";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if ($stmt) {
// Liaison des paramètres selon les dates fournies
if (!empty($start_date) && !empty($end_date)) {
    $end_date_plus_one = (new DateTime($end_date))->modify('+1 day')->format('Y-m-d');

$stmt->bind_param('iss', $employee_id, $start_date, $end_date_plus_one);
} else {
$stmt->bind_param('i', $employee_id);
}

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
// Ajout des lignes au tableau
$contents .= "
<tr>
 <td>{$row['transaction_date']}</td>
 <td>{$row['transaction_description']}</td>
 <td>" . ($row['transaction_type'] === 'minus' && $row['amount'] !== null ? number_format((float)$row['amount'], 2) : '') . "</td>
 <td>" . ($row['transaction_type'] === 'plus' && $row['amount'] !== null ? number_format((float)$row['amount'], 2) : '') . "</td>
</tr>";
}

// Résumé des opérations
$content_s .= "
<p><strong>مجموع العمليات: 
</strong> مدين:  <strong>{$total_credit}</strong> ---/---
دائن:  <strong>{$total_debit}</strong> ---/---
الرصيد:  <strong>{$calculated_balance}</strong></p>
";

$stmt->close();
} else {
echo "Erreur dans la préparation de la requête : " . $conn->error;
exit;
}


    // Configuration TCPDF
    require_once('tcpdf/tcpdf.php');
    $pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $lg = [];
    $lg['a_meta_charset'] = 'UTF-8';
    $lg['a_meta_dir'] = 'rtl';
    $lg['a_meta_language'] = 'ar';
    $pdf->setLanguageArray($lg);
    $pdf->setRTL(true);
    $pdf->SetFont('aealarabiya', '', 11);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetTitle("معلومات الموظفين");
    $pdf->SetHeaderData('', '', PDF_HEADER_TITLE, PDF_HEADER_STRING);
    $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
    $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
    $pdf->SetDefaultMonospacedFont('aealarabiya');
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    $pdf->SetMargins(PDF_MARGIN_LEFT, '11', PDF_MARGIN_RIGHT);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetAutoPageBreak(true, 11);

    // Ajouter l'image avec le titre
    $pdf->AddPage();

    // Ajouter l'image (mettre le chemin correct)
    $pdf->Image('../images/header.png', 220, 2, 230);
    
    // Ajouter le titre de l'image
    $pdf->SetFont('aealarabiya', 'B', 14);
    $pdf->SetXY(2, 18); // Positionner le titre près de l'image
    $pdf->Cell(0, 0, ' معاينة الحساب', 0, 1, 'C'); // Titre en arabe

    // Ajouter le reste du contenu
    $content = $header;
    $content .= '
        <h2 align="center">معلومات الموظفين</h2>
        <table border="1" cellspacing="0" cellpadding="2">
            <tr>
                <th >التاريخ</th>
                <th width="240px">بيان العملية</th>
                <th width="82px">مدين</th>
                <th width="82px">دائن</th>
            </tr>';
    $content .= $contents;
    $content .= '</table>';
    $content .= $content_s;

    $pdf->writeHTML($content);
    $pdf->Output('emps.pdf', 'I');
} else {
    echo "Aucun employee_id reçu.";
    exit; // Arrête le script si la valeur n'est pas reçue
}
?>
