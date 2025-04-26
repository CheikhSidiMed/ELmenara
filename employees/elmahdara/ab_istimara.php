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
$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
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
    <img src="imgs/header.png" height="170" width="100%" alt="" srcset="">
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
            <form method="GET" class="row align-items-end">
                <div class="col-md-4 mb-2 mb-md-0">
                    <label for="fromDate">من</label>
                    <input type="date" id="fromDate" name="from_date" class="form-control" 
                        value="<?= isset($_GET['from_date']) ? $_GET['from_date'] : '' ?>" 
                        onchange="this.form.submit()">
                </div>

                <div class="col-md-4 mb-2 mb-md-0">
                    <label for="toDate">الى</label>
                    <input type="date" id="toDate" name="to_date" class="form-control" 
                        value="<?= isset($_GET['to_date']) ? $_GET['to_date'] : '' ?>" 
                        onchange="this.form.submit()">
                </div>

                <div class="col-md-3 mb-2 mb-md-0">
                    <label for="class">القسم</label>
                    <select class="form-select" name="class" onchange="this.form.submit()">
                        <option value="">اختر القسم</option>
                        <?php
                        while ($cl = $classResult->fetch_assoc()) {
                            $selected = (isset($_GET['class']) && $_GET['class'] == $cl['class_id']) ? 'selected' : '';
                            echo '<option value="' . $cl['class_id'] . '" ' . $selected . '>' . htmlspecialchars($cl['class_name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-1">
                    <a href="<?= strtok($_SERVER["REQUEST_URI"], '?'); ?>" class="btn btn-outline-secondary d-flex justify-content-center align-items-center" 
                        style="width: 48px; height: 48px;"
                        title="إعادة تعيين">
                        <i class="bi bi-arrow-clockwise fs-3" style="font-size: 24px;"></i>
                    </a>
                </div>
            </form>


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
                    <col class="no-print">
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
                        foreach ($rows as $row) {
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
    <img src="imgs/footer.png" height="170" width="100%" alt="" srcset="">

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
  const rows = <?= json_encode($rows, JSON_UNESCAPED_UNICODE); ?>;
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
function generatePDF() {
  const arabicHeaders = ['الرقم', 'الإسم الكامل', 'من', 'الى', 'الغياب المبرر', 'الغياب الغير مبرر'];
  const tableData = rows;

  // Create container with RTL support
  const container = document.createElement('div');
  container.style.direction = 'rtl';
  container.style.fontFamily = "'Tajawal', Arial, sans-serif";
  container.style.padding = '20px';
  container.style.textAlign = 'right';
  container.style.maxWidth = '800px';
  container.style.margin = '0 auto';

  // Add CSS styles with page-break protection
  const style = document.createElement('style');
  style.textContent = `
    @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap');
    .report-title {
      color: #007b5e;
      margin: 15px 0;
      font-size: 24px;
      font-weight: 700;
    }
    .report-date {
      color: #555;
      margin-bottom: 20px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
      page-break-inside: auto;
    }
    tr {
      page-break-inside: avoid;
      page-break-after: auto;
    }
    th {
      background-color: #007b5e;
      color: white;
      padding: 10px;
      font-weight: 500;
    }
    td {
      padding: 8px;
      border: 1px solid #ddd;
    }
    tr:nth-child(even) {
      background-color: #f9f9f9;
    }
    // .footer {
    //   margin-top: 30px;
    //   text-align: center;
    //   font-size: 12px;
    //   color: #666;
    //   page-break-before: always;
    // }
    // .footer-img {
    //   width: 100%;
    //   max-width: 600px;
    //   margin: 20px auto 0;
    //   display: block;
    // }
  `;
  container.appendChild(style);

  // Header image
  const headerImg = document.createElement('img');
  headerImg.src = 'imgs/header.png';
  headerImg.style.width = '100%';
  headerImg.style.maxWidth = '900px';
  headerImg.style.margin = '0 auto 15px';
  headerImg.style.display = 'block';
  container.appendChild(headerImg);

  // Report title
  const title = document.createElement('h1');
  title.className = 'report-title';
  title.textContent = 'تقرير الغياب';
  container.appendChild(title);

  // Report date
  const date = document.createElement('div');
  date.className = 'report-date';
  date.textContent = ' تاريخ التقرير: ' + new Date().toLocaleDateString('ar-EG');
  container.appendChild(date);

  // Table with row protection
  const table = document.createElement('table');

  // Table head
  const thead = document.createElement('thead');
  const headRow = document.createElement('tr');
  arabicHeaders.forEach(header => {
    const th = document.createElement('th');
    th.textContent = header;
    headRow.appendChild(th);
  });
  thead.appendChild(headRow);
  table.appendChild(thead);

  // Table body with row protection
  const tbody = document.createElement('tbody');
  tableData.forEach(row => {
    const tr = document.createElement('tr');
    tr.style.pageBreakInside = 'avoid';
    
    const cells = [
      row.id,
      row.student_name,
      row.du,
      row.au,
      row.num_ab_ac,
      row.num_ab_no
    ];
    cells.forEach(cell => {
      const td = document.createElement('td');
      td.textContent = cell;
      if (['id', 'num_ab_ac', 'num_ab_no'].includes(cell)) {
        td.style.textAlign = 'center';
      }
      tr.appendChild(td);
    });
    tbody.appendChild(tr);
  });
  table.appendChild(tbody);
  container.appendChild(table);

  // Footer section (forced to new page)
  const footer = document.createElement('div');
  footer.className = 'footer';
  
  const footerImg = document.createElement('imgB');
  footerImg.src = 'imgs/footer.png';
  footerImg.style.width = '100%';
  headerImg.style.maxWidth = '900px';
//   footerImg.style.margin = '0 auto 15px';
  footerImg.style.display = 'block';
  footer.appendChild(footerImg);
  
  const footerText = document.createElement('p');
  footerText.textContent = 'شكراً لاستخدامكم نظام إدارة الغياب';
  footer.appendChild(footerText);
  
  container.appendChild(footer);

  // PDF generation options with page break control
  const opt = {
    margin: [15, 15, 15, 15],
    filename: `تقرير_الغياب_${new Date().toLocaleDateString('ar-EG')}.pdf`,
    image: {
      type: 'jpeg',
      quality: 1
    },
    html2canvas: {
      scale: 3,
      letterRendering: true,
      useCORS: true,
      scrollY: 0,
      logging: true
    },
    jsPDF: {
      unit: 'mm',
      format: 'a4',
      orientation: 'portrait',
      hotfixes: ["px_scaling"]
    },
    pagebreak: {
      mode: ['avoid-all', 'css', 'legacy'],
      before: '.footer',
      avoid: 'tr'
    }
  };

  // Generate PDF
  html2pdf()
    .set(opt)
    .from(container)
    .save()
    .then(() => {
      console.log('PDF généré avec succès');
    })
    .catch(err => {
      console.error('Erreur lors de la génération du PDF:', err);
    });
}
</script>


</body>
</html>

<?php
$conn->close();
?>


