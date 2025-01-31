<?php
// Database connection
include 'db_connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$startMonth = 10;
$endMonth = 9;
$currentYear = (int)date('Y');
$currentMonth = (int)date('m');

$starAcademicMonths = [];
$endaAcademicMonths = [];
if($currentMonth <= $startMonth){
    for ($month = $startMonth; $month <= 12; $month++) {
        $starAcademicMonths[] = $month;
    }
} else{
    for ($month = $startMonth; $month <= $currentMonth; $month++) {
        $starAcademicMonths[] = $month;
    }
}

if ($currentMonth <= $startMonth) {
    for ($month = 1; $month <= $currentMonth; $month++) {
        $endaAcademicMonths[] = $month;
    }
} else {
    $endaAcademicMonths[] = [];
}
$allAcademicMonths = array_merge($starAcademicMonths, $endaAcademicMonths);


$allMonths = [ 
    'October' => 'أكتوبر',
    'November' => 'نوفمبر',
    'December' => 'ديسمبر',
    'January' => 'يناير',
    'February' => 'فبراير',
    'March' => 'مارس',
    'April' => 'أبريل',
    'May' => 'مايو',
    'June' => 'يونيو',
    'July' => 'يوليو',
    'August' => 'أغسطس',
    'September' => 'سبتمبر'
];

$monthsArabic = [
    1 => 'يناير',
    2 => 'فبراير',
    3 => 'مارس',
    4 => 'أبريل',
    5 => 'مايو',
    6 => 'يونيو',
    7 => 'يوليو',
    8 => 'أغسطس',
    9 => 'سبتمبر',
    10 => 'أكتوبر',
    11 => 'نوفمبر',
    12 => 'ديسمبر'
];

$agent_id = 241;

$sql_students = "SELECT id, registration_date FROM students WHERE agent_id = ?";
$stmt_students = $conn->prepare($sql_students);
$stmt_students->bind_param("i", $agent_id);
$stmt_students->execute();
$result_students = $stmt_students->get_result();
$commonMonths = [];
$students = [];
if ($result_students->num_rows > 0) {
    while ($row = $result_students->fetch_assoc()) {
        $students[] = $row;
    }
}

// Fetch all paid months for all students
$student_ids = array_column($students, 'id');
$student_reg_dates = array_column($students, 'registration_date');
$placeholders = implode(',', array_fill(0, count($student_ids), '?'));
$sql_paid_months = "SELECT student_id, month FROM payments WHERE student_id IN ($placeholders)";
$stmt_paid_months = $conn->prepare($sql_paid_months);
$stmt_paid_months->bind_param(str_repeat('i', count($student_ids)), ...$student_ids);
$stmt_paid_months->execute();
$result_paid_months = $stmt_paid_months->get_result();

$paidMonths_with = [];
while ($row = $result_paid_months->fetch_assoc()) {
    $paidMonths_with[$row['student_id']][] = $row['month'];
}

// Calculate academic months

$finalMonths = [];

$academicMonths = [];
foreach ($students as $student) {
    $registrationYear = (int)date('Y', strtotime($student['registration_date']));
    $registrationMonth = (int)date('m', strtotime($student['registration_date']));
    $academicMonths = ($registrationMonth <= $endMonth) ? $endaAcademicMonths : $starAcademicMonths;
        
    $monthsBefore = []; 
    $monthsBefore1 = []; 
    $monthsBefore2 = []; 

    foreach ($academicMonths as $month) {
        $academicYear = ($month >= $startMonth) ? $currentYear : $currentYear + 1;        
        if ($registrationYear == $academicYear && $month <= $registrationMonth ) {
            $monthsBefore1[] = $monthsArabic[$month];
        }
    }
    if ($registrationMonth < $startMonth) {
        foreach ($starAcademicMonths as $month) {
            $monthsBefore2[] = $monthsArabic[$month];

        }
    } 

    $monthsBefore = array_merge($monthsBefore1, $monthsBefore2);
    $academicMonths[$student['id']] = $monthsBefore;
    echo $student['id']. '   ' . implode(', ', $monthsBefore)  . "<br/>";

    $student_id = $student['id'];
    $monthsBefore = $academicMonths[$student_id] ?? [];
    $paidMonths = $paidMonths_with[$student_id] ?? [];
    echo $student['id']. '   ' . implode(', ', $paidMonths)  . "<br/>";
    echo $student['id']. '   ' . implode(', ', $monthsBefore)  . "<br/>";

    $combinedMonths = array_unique(array_merge($monthsBefore, $paidMonths));
    $finalMonths[$student_id] = $combinedMonths;
}

$commonMonths = array_values(reset($finalMonths)); // Get the first student's months

foreach ($finalMonths as $studentID => $months) {
    $commonMonths = array_intersect($commonMonths, $months);
    if (empty($commonMonths)) {
        break; 
    }
}

echo "Student ID: - Months: " . implode(', ', $commonMonths) . "<br>";


$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        input[readonly] {
            pointer-events: none;
            opacity: 0.6;
            accent-color: red;        }

    </style>
</head>
<body>

<form method="POST" action="test_calculate_due.php">
    <div class="col-6">
        <div class="months-card">
            <div class="section-title">الأشهر</div>
            <div class="months-grid" id="months-grid">
                <?php foreach ($allMonths as $monthKey => $monthName): 
                    $isPaid = in_array($monthName, $commonMonths);
                ?>
                    <div class="month-option">
                        <input type="checkbox" 
                            name="months[]" 
                            value="<?php echo $monthName; ?>" 
                            id="month-<?php echo $monthKey; ?>" 
                            <?php echo $isPaid ? 'checked readonly' : ''; ?>
                            data-month-fee="<?php echo $monthly_fee; ?>" 
                            onclick="updateDueAmount()"/>
                        <label for="month-<?php echo $monthKey; ?>"><?php echo $monthName; ?></label>
                    </div>

                <?php endforeach; ?>
            </div>
        </div>
    </div>
</form>
<div>
    <strong>Montant total dû :</strong> <span id="total-due">0.00 €</span>
</div>


<script>

function updateDueAmount() {
    // Récupérer toutes les cases cochées sauf celles marquées comme readonly
    const checkboxes = document.querySelectorAll('input[name="months[]"]');

    const selectedMonths = Array.from(checkboxes)
        .filter(checkbox => checkbox.checked && !checkbox.hasAttribute('readonly')) // Ignorer les cases readonly
        .map(checkbox => checkbox.value);


    if (selectedMonths.length === 0) {
        document.getElementById('total-due').innerText = "0.00 €";
        return;
    }

    // Effectuer une requête Fetch pour récupérer le montant dû
    fetch('test_calculate_due.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ months: selectedMonths })
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.total_due !== undefined) {
                document.getElementById('total-due').innerText = `${data.total_due.toFixed(2)} €`;
            } else if (data.error) {
                console.error('Erreur serveur:', data.error);
                document.getElementById('total-due').innerText = "Erreur de calcul.";
            } else {
                console.error('Réponse inattendue:', data);
            }
        })
        .catch(error => {
            console.error('Erreur lors de la requête:', error);
            document.getElementById('total-due').innerText = "Erreur réseau.";
        });
}

</script>


</body>
</html>