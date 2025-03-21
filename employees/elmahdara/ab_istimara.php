<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: ../home.php");
    exit;
}
$user_id = $_SESSION['userid'];


$selectedClass = isset($_GET['class']) ? $_GET['class'] : '';
$fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$toDate = isset($_GET['to_date']) ? $_GET['to_date'] : '';




$query = "SELECT class_id, class_name FROM classes WHERE branch_id = 22";
$classResult = $conn->query($query);

if ($classResult === false) {
    die("Error fetching levels: " . $conn->error);
}


// Fetch student data from the database, including agent phone number
$sql = "SELECT s.id, s.student_name, a.du, a.au, a.num_ab_ac, a.num_ab_no,
               COALESCE(ag.whatsapp_phone, s.phone) AS whatsapp_phone
        FROM students s
        JOIN user_branch ub ON s.branch_id = ub.branch_id AND ub.user_id = ?
        LEFT JOIN ab_mahraa a ON a.student_id = s.id
        LEFT JOIN agents ag ON ag.agent_id = s.agent_id
        WHERE s.branch_id = 22 AND s.is_active = 0 ";

// Ajouter les conditions de filtrage
$params = [$user_id];
$types = "i";

if (!empty($selectedClass)) {
    $sql .= " AND s.class_id = ?";
    $params[] = $selectedClass;
    $types .= "i";
}

if (!empty($fromDate)) {
    $sql .= " AND a.du >= ?";
    $params[] = $fromDate;
    $types .= "s";
}

if (!empty($toDate)) {
    $sql .= " AND a.au <= ?";
    $params[] = $toDate;
    $types .= "s";
}
$sql .= " GROUP BY s.id";


// Exécuter la requête préparée
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بيانات الطلاب</title>
    <link rel="shortcut icon" type="image/png" href="../../images/menar.png">
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/sweetalert2.css">
    <script src="../js/sweetalert2.min.js"></script>
    <link href="../css/bootstrap-icons.css" rel="stylesheet">
    <link href="../fonts/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style1.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap">
    <style>
        @media print {
            /* .navbar { display: none !important; }
            .table { 
                width: 100% !important;
                table-layout: fixed;
            }
            th, td {
                white-space: nowrap !important;
                font-size: 12px !important;
            }
            .table-container {
                overflow: visible !important;
                width: 100% !important;
            } */

            .search-filter-container,
                th:last-child,
                td:last-child {
                    display: none !important;
                }
                
                /* Ajustements du tableau */
                .table {
                    width: 100% !important;
                    table-layout: auto;
                }
                th, td {
                    white-space: normal !important;
                    font-size: 14px !important;
                }
            
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container pb-2">
            <div class="how">
                <a class="nav-link" href="../home.php"><i class="bi bi-house-fill"></i>  الرئيسية</a>
            </div>
            <a class="navbar-brand" href="#"> حصيلة الغياب - مقرأة المنارة والرباط</a>
            <button class="btn btn-danger ms-auto" onclick="generatePDF()">
            <i class="bi bi-file-pdf"></i> حفظ كـ PDF
        </button>
        </div>
    </nav>

    
    <div class="container-full " style="direction: rtl;">
        <h2> حصيلة <span class="is_active">(الغياب)</span></h2>
        <div class="search-filter-container text-center mb-3 row align-items-center">
            <div class="col-md-5 mb-2 mb-md-0">
                <label for="">-</label>
                <input type="text" id="searchInput" class="form-control" placeholder="البحث عن طريق اسم الطالب...">
            </div>
            <form method="GET" class="row" >
            <div class="col-md-6 mb-2 mb-md-0">
                <label for="fromDate">من</label>
                    <input type="date" id="fromDate" name="from_date" class="form-control" value="<?= isset($_GET['from_date']) ? $_GET['from_date'] : '' ?>" onchange="this.form.submit()">
            </div>
                <div class="col-md-6 mb-2 mb-md-0">
                    <label for="toDate">الى</label>
                    <input type="date" id="toDate" name="to_date" class="form-control" value="<?= isset($_GET['to_date']) ? $_GET['to_date'] : '' ?>" onchange="this.form.submit()">
                </div>
            </form>
            <div class="col-md-2">
                <label for="class">-</label>
                <form method="GET" class="d-flex">
                    <select class="form-select" name="class" onchange="this.form.submit()">
                        <option value="">اختر القسم</option>
                        <?php
                        while ($cl = $classResult->fetch_assoc()) {
                            $selected = (isset($_GET['class']) && $_GET['class'] == $cl['class_id']) ? 'selected' : '';
                            echo '<option value="' . $cl['class_id'] . '" ' . $selected . '>' . htmlspecialchars($cl['class_name']) . '</option>';
                        }
                        ?>
                    </select>
                </form>
            </div>
        </div>
        <div class="table-container">
            <table class="table table-bordered">
                <thead>
                <colgroup>
                    <col class="print-column">
                    <col class="print-column">
                    <col class="print-column">
                    <col class="print-column">
                    <col class="print-column">
                    <col class="print-column">
                    <col class="no-print"> <!-- Colonne إجراءات -->
                </colgroup>
                    <tr>
                        <th>الرقم</th>
                        <th>الإسم الكامل</th>
                        <th>من</th>
                        <th>الى</th>
                        <th>الغياب المبرر</th>
                        <th>الغياب الغير مبرر</th>
                        <th style="width: 10%;">إجراءات</th>
                    </tr>
                </thead>
                <tbody id="suspendedStudentsTableBody">
                <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr data-student-id='{$row['id']}'>
                                    <td>{$row['id']}</td>
                                    <td data-field='student_name'>{$row['student_name']}</td>
                                    <td data-field='du'>{$row['du']}</td>
                                    <td data-field='au'>{$row['au']}</td>
                                    <td data-field='num_ab_ac'>{$row['num_ab_ac']}</td>
                                    <td data-field='num_ab_no'>{$row['num_ab_no']}</td>
                                    <td>
                                        <div class='edit-btn-group'>";
                                    
                                echo "<a class='h5 btn btn-sm btn-primary edit-btn' onclick='toggleEditMode(this)'><i class='bi bi-pencil-square'></i> تعديل</a>
                                        <a class='btn btn-sm btn-success save-btn' style='display:none;' onclick='saveStudent(this)'>
                                                <i class='bi bi-save'></i> حفظ
                                            </a>
                                            <a class='btn btn-sm btn-secondary cancel-btn' style='display:none;' onclick='cancelEdit(this)'>
                                                <i class='bi bi-x'></i> إلغاء
                                            </a>
                                        ";

                                    echo "<a class='h5 btn btn-success btn-action' onclick=\"sendWhatsAppMessage(
                                                '{$row['whatsapp_phone']}',
                                                '{$row['student_name']}',
                                                '{$row['du']}',
                                                '{$row['au']}',
                                                '{$row['num_ab_ac']}',
                                                '{$row['num_ab_no']}'
                                            )\">
                                            <i class='bi bi-whatsapp'></i> إرسال
                                        </a>
                                        </div>
                                        </td>
                                    </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='16'>لا يوجد طلاب</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

<script src="../js/jquery-3.5.1.min.js"></script>
<script>
    $(document).ready(function() {
        $('#searchInput').on('input', function() {
            const value = $(this).val().toLowerCase();
            $('#suspendedStudentsTableBody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().includes(value));
            });
        });
    });
</script>

<script>
    function toggleEditMode(button) {
        const row = button.closest('tr');
        row.classList.add('edit-mode');
        
        row.querySelector('.edit-btn').style.display = 'none';
        row.querySelector('.btn-action').style.display = 'none';
        row.querySelector('.save-btn').style.display = 'inline-block';
        row.querySelector('.cancel-btn').style.display = 'inline-block';

        row.querySelectorAll('td[data-field]').forEach(td => {
            const field = td.dataset.field;
            const value = td.innerText;
            
            let input;
            if(field === 'du' || field === 'au') {
                input = `<input type="date" class="form-control" value="${value}">`;
            } else {
                input = `<input type="text" class="form-control" value="${value}">`;
            }
            
            td.innerHTML = input;
        });
    }

    function cancelEdit(button) {
        const row = button.closest('tr');
        row.classList.remove('edit-mode');
        
        row.querySelector('.edit-btn').style.display = 'inline-block';
        row.querySelector('.btn-action').style.display = 'inline-block';
        row.querySelector('.save-btn').style.display = 'none';
        row.querySelector('.cancel-btn').style.display = 'none';
        location.reload();
    }

    function saveStudent(button) {
        const row = button.closest('tr');
        const studentId = row.dataset.studentId;
        const data = {};

        row.querySelectorAll('td[data-field]').forEach(td => {
            const field = td.dataset.field;
            const input = td.querySelector('input, select');
            
            data[field] = input.value;
        });
        console.log(data);


        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: 'هل تريد حفظ التعديلات؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'نعم، احفظ التغييرات',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'update_abs.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        student_id: studentId,
                        ...data
                    },
                    success: function(response) {
                        console.log(response);
                        if(response.success) {
                            Swal.fire('تم الحفظ!', 'تم تحديث بيانات الطالب بنجاح', 'success');
                            row.querySelectorAll('td[data-field]').forEach(td => {
                                const field = td.dataset.field;
                                td.innerHTML = data[field];
                            });
                            cancelEdit(button);
                        } else {
                            Swal.fire('خطأ!', response.error || 'فشل في تحديث البيانات', 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('خطأ!', 'حدث خطأ في الاتصال بالخادم', 'error');
                    }
                });
            }
        });
    }


    function sendWhatsAppMessage(phoneNumber, name, du, au, numAbAc, numAbNo) {
    if (!phoneNumber) {
        alert("لا يوجد رقم واتساب لهذا الطالب!");
        return;
    }

    const message = `🛑 حصيلة الغياب من ${du} إلى ${au}

        اسم الطالبة: ${name}

        عدد الغياب المبرر: ${numAbAc}
        عدد الغياب غير المبرر: ${numAbNo}

        ❌ ملاحظة: الغياب ممنوع عمومًا.
        ✅ كثرة الغياب المبرر تؤثر على نتيجة الامتحان.
        ✅ 3 غيابات غير مبررة تؤدي إلى تعليق الدراسة فورًا.`;

    const encodedMessage = encodeURIComponent(message);
    const whatsappUrl = `https://wa.me/${phoneNumber}?text=${encodedMessage}`;

    window.open(whatsappUrl, '_blank');
}

</script>

<script src="../js/sweetalert2.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>

// function generatePDF() {
//     const originalElement = document.querySelector('table');
//     const clone = originalElement.cloneNode(true);

//     // Cacher le titre avant la génération du PDF
//     const titleElement = document.querySelector('h2');
//     if (titleElement) {
//         titleElement.style.display = 'none';
//     }

//     // Supprimer les éléments inutiles (dernier bouton d'action)
//     clone.querySelectorAll('th:last-child, td:last-child').forEach(el => el.remove());

//     // Afficher toutes les lignes masquées
//     clone.querySelectorAll('#suspendedStudentsTableBody tr').forEach(row => {
//         row.style.removeProperty('display');
//     });

//     // Inverser l'ordre des colonnes
//     clone.querySelectorAll('tr').forEach(row => {
//         let cells = Array.from(row.children);
//         cells.reverse(); // Inverser l'ordre des cellules
//         row.innerHTML = ''; // Vider la ligne
//         cells.forEach(cell => row.appendChild(cell)); // Réinsérer les cellules dans le nouvel ordre
//     });

//     const opt = {
//         margin: [10, 5, 10, 5],
//         filename: `Absence_Complete_${new Date().toLocaleDateString()}.pdf`,
//         image: { type: 'jpeg', quality: 0.98 },
//         html2canvas: { scale: 2, scrollY: 0, useCORS: true },
//         jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
//     };

//     html2pdf().set(opt).from(clone).save().then(() => {
//         // Réafficher le titre après la génération du PDF
//         if (titleElement) {
//             titleElement.style.display = '';
//         }
//     });
// }

function generatePDF() {
    const originalTable = document.querySelector('table');
    const originalTitle = document.querySelector('h2');

    // Créer un conteneur temporaire
    const tempContainer = document.createElement('div');

    // Cloner et ajouter le titre
    if (originalTitle) {
        const clonedTitle = originalTitle.cloneNode(true);
        tempContainer.appendChild(clonedTitle);
    }

    // Cloner et ajouter le tableau
    const clonedTable = originalTable.cloneNode(true);
    tempContainer.appendChild(clonedTable);

    // Supprimer la dernière colonne (boutons d'action) dans le clone
    clonedTable.querySelectorAll('th:last-child, td:last-child').forEach(el => el.remove());

    // Afficher toutes les lignes masquées
    clonedTable.querySelectorAll('#suspendedStudentsTableBody tr').forEach(row => {
        row.style.removeProperty('display');
    });

    // Appliquer un style pour éviter que les lignes soient coupées
    const style = document.createElement('style');
    style.textContent = `
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border: 1px solid black; }
        tr { page-break-inside: avoid; } /* Empêche la coupure des lignes */
        thead { display: table-header-group; } /* Répète l'entête sur chaque page */
    `;
    tempContainer.appendChild(style);

    const opt = {
        margin: [10, 5, 10, 5],
        filename: `Absence_Complete_${new Date().toLocaleDateString()}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, scrollY: 0, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' } // Format portrait pour mieux gérer les pages
    };

    html2pdf().set(opt).from(tempContainer).save();
}



</script>

</body>
</html>

<?php
$conn->close();
?>


