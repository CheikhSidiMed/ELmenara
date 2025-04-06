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

// Handle Student Creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $full_name = $conn->real_escape_string($_POST["full_name"]);
    $NNI = $conn->real_escape_string($_POST["NNI"]);
    $birth_city = $conn->real_escape_string($_POST["birth_city"]);
    $birth_date = $conn->real_escape_string($_POST["birth_date"]);
    $type = $conn->real_escape_string($_POST["type"]);
    $phone = $conn->real_escape_string($_POST["phone"]);
    $year = $conn->real_escape_string($_POST["year"]);
    $date = $conn->real_escape_string($_POST["date"]);
    $type_ijaza = $conn->real_escape_string($_POST["type_ijaza"]);

    $photoContent = NULL;
    $photoUrl = '';


    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photo']['tmp_name'];
        $fileName = $_FILES['photo']['name'];
        $fileSize = $_FILES['photo']['size'];
        $fileType = $_FILES['photo']['type'];

        $uploadDir = 'uploads/';

        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $uniqueFileName = uniqid('photos_', true) . '.' . $fileExtension;
        $photoUrl = $uploadDir . $uniqueFileName;
        
        if (move_uploaded_file($fileTmpPath, $photoUrl)) {
            echo "Fichier téléchargé avec succès : " . $photoUrl;
        } else {
            echo "Erreur lors du téléchargement du fichier.";
        }
    } else {
        echo "Erreur dans le téléchargement ou aucun fichier sélectionné.";
    }

    $sql = "INSERT INTO etudiants_certified (full_name, NNI, birth_city, birth_date, phone, photo, type, year, date, type_ijaza)
                VALUES
            ('$full_name', '$NNI', '$birth_city', '$birth_date', '$phone', '$photoUrl', '$type', '$year', '$date', '$type_ijaza')";

    if ($conn->query($sql) === TRUE) {
        $_SESSION['message'] = ['icon' => 'success', 'title' => 'تمت الإضافة بنجاح!', 'text' => 'تمت إضافة الطالب بنجاح.'];
    } else {
        $_SESSION['message'] = ['icon' => 'error', 'title' => 'خطأ!', 'text' => 'حدث خطأ أثناء الإضافة: ' . $conn->error];
    }
    header("Location: student_certf.php");
    exit();
}

// Handle Student Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_student'])) {
    $id = intval($_POST["student_id"]);
    $nom = $conn->real_escape_string($_POST["edit_full_name"]);
    $nni = $conn->real_escape_string($_POST["edit_NNI"]);
    $phone = $conn->real_escape_string($_POST["edit_phone"]);
    $birth_city = $conn->real_escape_string($_POST["edit_birth_city"]);
    $birth_date = $conn->real_escape_string($_POST["edit_birth_date"]);
    $type = $conn->real_escape_string($_POST["edit_type"]);
    $_date = $conn->real_escape_string($_POST["edit_date"]);
    $ijaza = $conn->real_escape_string($_POST["edit_type_ijaza"]);
    $_year = $conn->real_escape_string($_POST["edit_year"]);

    // Vérifier si un fichier a été uploadé
    if (!empty($_FILES["edit_photo"]["name"])) {
        $photo_name = $_FILES["edit_photo"]["name"];
        $photo_tmp = $_FILES["edit_photo"]["tmp_name"];
        $photo_size = $_FILES["edit_photo"]["size"];
        $photo_error = $_FILES["edit_photo"]["error"];

        // Vérifier si l'image est valide
        $allowed_ext = ["jpg", "jpeg", "png", "gif"];
        $photo_ext = strtolower(pathinfo($photo_name, PATHINFO_EXTENSION));

        if (in_array($photo_ext, $allowed_ext) && $photo_error === 0) {
            $new_photo_name = "student_" . $id . "_" . time() . "." . $photo_ext;
            $photo_path = "uploads/" . $new_photo_name; // Assurez-vous que le dossier 'uploads/' existe

            // Récupérer l'ancienne image pour la supprimer
            $sql_select = "SELECT photo FROM etudiants_certified WHERE id = $id";
            $result = $conn->query($sql_select);
            if ($result && $row = $result->fetch_assoc()) {
                $old_photo = $row['photo'];
                if (!empty($old_photo) && file_exists("uploads/" . $old_photo)) {
                    unlink("uploads/" . $old_photo); // Supprimer l'ancienne image
                }
            }

            // Déplacer la nouvelle image
            if (move_uploaded_file($photo_tmp, $photo_path)) {
                $sql = "UPDATE etudiants_certified SET full_name='$nom', nni='$nni', date='$_date', type_ijaza='$ijaza', year='$_year', phone='$phone', birth_city='$birth_city', birth_date='$birth_date', type='$type', photo='$new_photo_name' WHERE id=$id";
            } else {
                $_SESSION['message'] = ['icon' => 'error', 'title' => 'خطأ!', 'text' => 'فشل تحميل الصورة.'];
                header("Location: student_certf.php");
                exit();
            }
        } else {
            $_SESSION['message'] = ['icon' => 'error', 'title' => 'خطأ!', 'text' => 'نوع الصورة غير مدعوم أو هناك مشكلة في التحميل.'];
            header("Location: student_certf.php");
            exit();
        }
    } else {
        // Si aucune image n'est uploadée, ne pas modifier la colonne 'photo'
        $sql = "UPDATE etudiants_certified SET full_name='$nom', nni='$nni', phone='$phone', date='$_date', type_ijaza='$ijaza', year='$_year', birth_city='$birth_city', birth_date='$birth_date', type='$type' WHERE id=$id";
    }

    if ($conn->query($sql) === TRUE) {
        $_SESSION['message'] = ['icon' => 'success', 'title' => 'تم التحديث بنجاح!', 'text' => 'تم تعديل بيانات الطالب بنجاح.'];
    } else {
        $_SESSION['message'] = ['icon' => 'error', 'title' => 'خطأ!', 'text' => 'حدث خطأ أثناء التحديث: ' . $conn->error];
    }
    header("Location: student_certf.php");
    exit();
}


// Handle Student Deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_student'])) {
    $id = intval($_POST["student_id"]);

    $sql = "DELETE FROM etudiants_certified WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        $_SESSION['message'] = ['icon' => 'success', 'title' => 'تم الحذف بنجاح!', 'text' => 'تم حذف الطالب بنجاح.'];
    } else {
        $_SESSION['message'] = ['icon' => 'error', 'title' => 'خطأ!', 'text' => 'حدث خطأ أثناء الحذف: ' . $conn->error];
    }
    header("Location: student_certf.php");
    exit();
}



$totalEtudiants_m = $conn->query("SELECT COUNT(*) AS total FROM etudiants_certified WHERE type_ijaza='مجازي'")->fetch_assoc()['total'];
$totalEtudiants_h = $conn->query("SELECT COUNT(*) AS total FROM etudiants_certified WHERE type_ijaza='حافظ'")->fetch_assoc()['total'];


$result = $conn->query("SELECT type, COUNT(*) as count FROM etudiants_certified GROUP BY type");
$etudiantsParNiveau = [];
while ($row = $result->fetch_assoc()) {
    $etudiantsParNiveau[$row['type']] = $row['count'];
}


$niveauColors = ['نافع' => '#dc3545', 'ورش' => '#281745', 'نافع' => '#28a745', 'حفص' => '#017B6A'];

?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title> 📚  أرشيف الحفاظِ</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/sweetalert2.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/css.css">
</head>
<body>
<div id="navbar-container"></div>

<script>
    fetch("navbar.html")
        .then(response => response.text())
        .then(data => {
            document.getElementById("navbar-container").innerHTML = data;
        })
        .catch(error => console.error("Erreur lors du chargement du menu :", error));
</script>


    <div class="container">

        <div class="stats">
            <div class="box total">
                <h2><?php echo $totalEtudiants_h; ?></h2>
                <p>إجمالي الحفاظ</p>
            </div>
            <div class="box licence">
                <h2><?php echo isset($etudiantsParNiveau['حفص']) ? $etudiantsParNiveau['حفص'] : 0; ?></h2>
                <p>حفص</p>
            </div>
            <div class="box master">
                <h2><?php echo isset($etudiantsParNiveau['نافع']) ? $etudiantsParNiveau['نافع'] : 0; ?></h2>
                <p>نافع</p>
            </div>
            <div class="box doctorat1">
                <h2><?php echo isset($etudiantsParNiveau['ورش']) ? $etudiantsParNiveau['ورش'] : 0; ?></h2>
                <p>ورش</p>
            </div>
            <div class="box total">
                <h2><?php echo $totalEtudiants_m; ?></h2>
                <p>إجمالي المجازين</p>
            </div>
        </div>

        <hr>
        <div class="search-box mb-1 mt-4">
            <input type="text" id="searchInput" class="form-control" placeholder="البحث عن طريق اسم...">
        </div>
        <div class="table-responsive tbl">
            <table>
                <thead>
                    <tr>
                        <th>الصورة</th>
                        <th>الإسم الكامل</th>
                        <th>الرقم الوطني</th>
                        <th>رقم الهاتف</th>
                        <th>العام الدراسي </th>
                        <th>بتاريخ </th>
                        <th>تاريخ الميلاد</th>
                        <th>محل الميلاد</th>
                        <th>النوع</th>
                        <th>القرءاة</th>
                        <th>الإجراء</th>
                    </tr>
                </thead>
                <tbody id="sTableBody">
                    <?php
                    $students = $conn->query("SELECT * FROM etudiants_certified");
                    while ($student = $students->fetch_assoc()):
                    ?>
                        <tr>
                            <td style="text-align: center;">
                                <?php if (!empty($student['photo']) && file_exists('uploads/' . $student['photo'])): ?>
                                    <img src="<?= 'uploads/' . $student['photo']; ?>" alt="Photo" width="80" height="80" style="border-radius: 50%; border: 3px solid blue; object-fit: cover; margin-right: -20px;">
                                <?php else: ?>
                                    <div style="
                                        width: 75px; height: 75px;
                                        display: flex; align-items: center; justify-content: center;
                                        background-color: #017B6A; color: white;
                                        font-size: 25px; font-weight: bold;
                                        border-radius: 50%; border: 3px solid blue;
                                    ">
                                        <?= strtoupper(mb_substr($student['full_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['NNI']); ?></td>
                            <td><?php echo htmlspecialchars($student['phone']); ?></td>
                            <td><?php echo htmlspecialchars($student['year']); ?></td>
                            <td><?php echo htmlspecialchars($student['date']); ?></td>
                            <td><?php echo htmlspecialchars($student['birth_date']); ?></td>
                            <td><?php echo htmlspecialchars($student['birth_city']); ?></td>
                            <td style="color: #281745; font-weight: bold;">
                                <?php echo htmlspecialchars($student['type_ijaza']); ?>
                            </td>
                            <td style="color: <?php echo $niveauColors[$student['type']]; ?>; font-weight: bold;">
                                <?php echo htmlspecialchars($student['type']); ?>
                            </td>
                            <td>
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editStudentModal"
                                    onclick="fillEditForm(<?= $student['id']; ?>,
                                         '<?= $student['full_name']; ?>',
                                         '<?= $student['phone']; ?>',
                                         '<?= $student['NNI']; ?>',
                                         '<?= $student['birth_date']; ?>',
                                         '<?= $student['birth_city']; ?>',
                                         '<?= $student['photo']; ?>',
                                         '<?= $student['type']; ?>',
                                         '<?= $student['date']; ?>',
                                         '<?= $student['type_ijaza']; ?>',
                                         '<?= $student['year']; ?>')">تعديل</button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="student_id" value="<?= $student['id']; ?>">
                                    <button type="submit" name="delete_student" class="btn btn-danger btn-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <button type="button" class="floating-button pb-2" data-bs-toggle="modal" data-bs-target="#ajoutEtudiantModal">+</button>

    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/sweetalert2.min.js"></script>

    <script>
        function fillEditForm(studentId, studentFullName, studentPhone, studentNNI, studentDate, studentCD, studentPh, studentTy,
            editDate, editTIjaza, editYear
        ) {
            console.log('ndqnnqinrnn ---- ', editDate);
            document.getElementById("editStudentId").value = studentId;
            document.getElementById("edit_full_name").value = studentFullName;
            document.getElementById("edit_phone").value = studentPhone;
            document.getElementById("edit_NNI").value = studentNNI;
            document.getElementById("edit_birth_date").value = studentDate;
            document.getElementById("edit_birth_city").value = studentCD;
            // document.getElementById("edit_photo").value = studentPh;
            document.getElementById("edit_type").value = studentTy;
            document.getElementById("edit_date").value = editDate;
            document.getElementById("edit_type_ijaza").value = editTIjaza;
            document.getElementById("edit_year").value = editYear;

            if (studentPh) {
                document.getElementById("edit_photo").src = 'uploads/' + studentPh;
            }
        }
    </script>

    <script>
        function toggleMenu() {
            document.querySelector('.nav-links').classList.toggle('show');
        }
    </script>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStudentModalLabel">تعديل بيانات الطالب</h5>
                    <button type="button" class="btn-close ms-0 bg-success text-black" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <!-- Form to Edit Student -->
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" id="editStudentId" name="student_id">
                        <div class="">
                            <label for="edit_full_name" class="form-label">الإسم الكامل</label>
                            <input type="text" class="form-control" id="edit_full_name" name="edit_full_name" required>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <label for="edit_NNI" class="form-label">الرقم الوطني</label>
                                <input type="text" class="form-control" id="edit_NNI" name="edit_NNI" required>
                            </div>
                            <div class="col-6">
                                <label for="edit_phone" class="form-label">رقم الهاتف</label>
                                <input type="text" class="form-control" id="edit_phone" name="edit_phone" required>
                            </div>
                        </div>
                        <div class="">
                            <label for="edit_birth_city" class="form-label">محل الميلاد</label>
                            <input type="text" class="form-control" id="edit_birth_city" name="edit_birth_city" required>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <label for="edit_birth_date">تاريخ الميلاد</label>
                                <input type="date" class="form-control" id="edit_birth_date" name="edit_birth_date" required>
                            </div>
                            <div class="col-6">
                                <label for="edit_date">بتاريخ </label>
                                <input type="date" class="form-control" id="edit_date" name="edit_date" required>
                            </div>
                        </div>
                        <div class="">
                            <label for="edit_photo" class="form-label">الصورة</label>
                            <input type="file" class="form-control" id="edit_photo" name="edit_photo"  accept="image/*">
                        </div>
                        <div class="row">
                            <div class="col-6">
                            <label for="edit_type" class="form-label">القرءاة</label>
                                <select class="form-select" id="edit_type" name="edit_type" required>
                                    <option value="نافع">نافع</option>
                                    <option value="حفص">حفص</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="edit_type_ijaza" class="form-label">النوع</label>
                                <select class="form-select" id="edit_type_ijaza" name="edit_type_ijaza" required>
                                    <option value="مجازي">مجازي</option>
                                    <option value="حافظ">حافظ</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_year" class="form-label">السنة</label>
                            <select class="form-select" id="edit_year" name="edit_year" required>
                                <option value="">-- السنة --</option>
                                <option value="2016-2017">2016-2017</option>
                                <option value="2017-2018">2017-2018</option>
                                <option value="2018-2019">2018-2019</option>
                                <option value="2019-2020">2019-2020</option>
                                <option value="2020-2021">2020-2021</option>
                                <option value="2021-2022">2021-2022</option>
                                <option value="2022-2023">2022-2023</option>
                                <option value="2023-2024">2023-2024</option>
                                <option value="2024-2025">2024-2025</option>
                                <option value="2025-2026">2025-2026</option>
                                <option value="2026-2027">2026-2027</option>
                                <option value="2027-2028">2027-2028</option>
                                <option value="2028-2029">2028-2029</option>
                                <option value="2029-2030">2029-2030</option>
                                <option value="2030-2031">2030-2031</option>
                                <option value="2031-2032">2031-2032</option>
                                <option value="2032-2033">2032-2033</option>
                            </select>
                        </div>
                        <button type="submit" name="edit_student" class="btn btn-success">تحديث البيانات</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Pop-up (Modale Bootstrap) -->
    <div class="modal fade" id="ajoutEtudiantModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">إضافة طالب</h5>
                    <button type="button" class="btn-close ms-0 bg-success text-black" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <input type="text" class="form-control" name="full_name" placeholder="الإسم الكامل" required>
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" name="NNI" placeholder="الرقم الوطني" required>
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" name="phone" placeholder="رقم الهاتف" required>
                        </div>
                        <div class="mb-1">
                            <input type="text" class="form-control" name="birth_city" placeholder="محل الميلاد" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label for="">تاريخ الميلاد</label>
                                <input type="date" class="form-control" name="birth_date" required>
                            </div>
                            <div class="col-6">
                                <label for="">بتاريخ </label>
                                <input type="date" class="form-control" name="date" required>
                            </div>
                        </div>
                            <input type="file" class="form-control" name="photo"  placeholder="الصورة" accept="image/*">
                        <div class="mb-3">
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <select class="form-select" name="type" required>
                                    <option value="">-- القرءاة --</option>
                                    <option value="نافع">نافع</option>
                                    <option value="حفص">حفص</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select class="form-select" name="type_ijaza" required>
                                    <option value="">-- النوع --</option>
                                    <option value="مجازي">مجازي</option>
                                    <option value="حافظ">حافظ</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <select class="form-select" name="year" required>
                                <option value="">-- السنة --</option>
                                <option value="2016-2017">2016-2017</option>
                                <option value="2017-2018">2017-2018</option>
                                <option value="2018-2019">2018-2019</option>
                                <option value="2019-2020">2019-2020</option>
                                <option value="2020-2021">2020-2021</option>
                                <option value="2021-2022">2021-2022</option>
                                <option value="2022-2023">2022-2023</option>
                                <option value="2023-2024">2023-2024</option>
                                <option value="2024-2025">2024-2025</option>
                                <option value="2025-2026">2025-2026</option>
                                <option value="2026-2027">2026-2027</option>
                                <option value="2027-2028">2027-2028</option>
                                <option value="2028-2029">2028-2029</option>
                                <option value="2029-2030">2029-2030</option>
                                <option value="2030-2031">2030-2031</option>
                                <option value="2031-2032">2031-2032</option>
                                <option value="2032-2033">2032-2033</option>
                            </select>
                        </div>
                        <button type="submit" name="add_student" class="btn btn-success">إضافة</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php
        if (isset($_SESSION['message'])) {
            echo "<script>
                Swal.fire({
                    icon: '" . $_SESSION['message']['icon'] . "',
                    title: '" . $_SESSION['message']['title'] . "',
                    text: '" . $_SESSION['message']['text'] . "',
                    confirmButtonText: 'موافق'
                });
            </script>";
            unset($_SESSION['message']);
        }
    ?>

    <script src="../js/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#searchInput').on('input', function() {
                const value = $(this).val().toLowerCase();
                $('#sTableBody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().includes(value));
                });
            });
        });
    </script>


</body>
</html>
