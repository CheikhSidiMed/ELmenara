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

    $sql = "INSERT INTO etudiants_certified (full_name, NNI, birth_city, birth_date, phone, photo, type)
                VALUES
            ('$full_name', '$NNI', '$birth_city', '$birth_date', '$phone', '$photoUrl', '$type')";

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
                $sql = "UPDATE etudiants_certified SET full_name='$nom', nni='$nni', phone='$phone', birth_city='$birth_city', birth_date='$birth_date', type='$type', photo='$new_photo_name' WHERE id=$id";
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
        $sql = "UPDATE etudiants_certified SET full_name='$nom', nni='$nni', phone='$phone', birth_city='$birth_city', birth_date='$birth_date', type='$type' WHERE id=$id";
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



$totalEtudiants = $conn->query("SELECT COUNT(*) AS total FROM etudiants_certified")->fetch_assoc()['total'];


$result = $conn->query("SELECT type, COUNT(*) as count FROM etudiants_certified GROUP BY type");
$etudiantsParNiveau = [];
while ($row = $result->fetch_assoc()) {
    $etudiantsParNiveau[$row['type']] = $row['count'];
}


$niveauColors = ['نافع' => '#007bff', 'ورش' => '#28a745', 'قالون' => '#281745', 'حفص' => '#dc3545'];

?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title> 📚  أرشيف الحفاظِ</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/sweetalert2.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap');
        
        body {
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
            direction: rtl;
            text-align: right;
        }
        h1 {
            text-align: center;
            color: #343a40;
        }
        .container {
            max-width: 94%;
            margin: auto;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
        }
        .box {
            min-width: 240px;
            max-width: 340px;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: white;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out;
        }
        .box:hover {
            transform: scale(1.05);
        }
        .total { background-color: #17a2b8; }
        .licence { background-color: #007bff; }
        .master { background-color: #28a745; }
        .doctorat { background-color: #281745; }
        .doctorat1 { background-color: #dc3545; }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        th, td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .floating-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #0d6efd;
            color: white;
            font-size: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border: none;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .floating-button:hover {
            background-color: #0b5ed7;
            transform: scale(1.1);
        }

        .floating-button:active {
            transform: scale(0.9);
        }
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 12px; /* Padding for the icon */
            border: 2px solid #007bff;
            border-radius: 8px;
            font-size: 16px;
            color: #333;
            background-color: #f9f9f9;
            outline: none;
            transition: border-color 0.3s ease;
            margin-bottom: 5px;
        }

        .search-box input:focus {
            border-color: #007bff;
        }

        .search-box::after {
            content: "\f002"; /* FontAwesome search icon */
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: #007bff;
            pointer-events: none;

        }



        /* Responsive table */
        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            .box {
                min-width: 180px;
                padding: 20px;
                border-radius: 10px;
                margin-bottom: 20px;
                flex-wrap: wrap;
                color: white;
                text-align: center;
                font-size: 16px;
                font-weight: bold;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                transition: transform 0.3s ease-in-out;
            }
        }
        .tbl {
            overflow-x: auto;
            width: 100%;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="row mb-5">
            <h1 class="col">📚  أرشيف الحفاظِ</h1>
            <button type="button" class="btn btn-primary col-12 col-md-1" onclick="window.location.href='../home.php'">
             الصفحة الرئيسية
            </button>
        </div>
        <div class="stats">
            <div class="box total">
                <h2><?php echo $totalEtudiants; ?></h2>
                <p>إجمالي الحفاظ</p>
            </div>
            <div class="box licence">
                <h2><?php echo isset($etudiantsParNiveau['نافع']) ? $etudiantsParNiveau['نافع'] : 0; ?></h2>
                <p>نافع</p>
            </div>
            <div class="box master">
                <h2><?php echo isset($etudiantsParNiveau['ورش']) ? $etudiantsParNiveau['ورش'] : 0; ?></h2>
                <p>ورش</p>
            </div>
            <div class="box doctorat1">
                <h2><?php echo isset($etudiantsParNiveau['قالون']) ? $etudiantsParNiveau['قالون'] : 0; ?></h2>
                <p>قالون</p>
            </div>
            <div class="box doctorat">
                <h2><?php echo isset($etudiantsParNiveau['حفص']) ? $etudiantsParNiveau['حفص'] : 0; ?></h2>
                <p>حفص</p>
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
                        <th>محل الميلاد</th>
                        <th>تاريخ الميلاد</th>
                        <th>القرءاة</th>
                        <th>الإجراء</th>
                    </tr>
                </thead>
                <tbody id="sTableBody">
                    <?php
                    $students = $conn->query("SELECT id, full_name, phone, NNI, birth_date, birth_city, photo, type FROM etudiants_certified");
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
                                        background-color: #007bff; color: white;
                                        font-size: 25px; font-weight: bold;
                                        border-radius: 50%; border: 3px solid blue;
                                    ">
                                        <?= strtoupper(mb_substr($student['full_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['phone']); ?></td>
                            <td><?php echo htmlspecialchars($student['NNI']); ?></td>
                            <td><?php echo htmlspecialchars($student['birth_date']); ?></td>
                            <td><?php echo htmlspecialchars($student['birth_city']); ?></td>
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
                                         '<?= $student['type']; ?>')">تعديل</button>
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
        function fillEditForm(studentId, studentFullName, studentPhone, studentNNI, studentDate, studentCD, studentPh, studentTy) {
            document.getElementById("editStudentId").value = studentId;
            document.getElementById("edit_full_name").value = studentFullName;
            document.getElementById("edit_phone").value = studentPhone;
            document.getElementById("edit_NNI").value = studentNNI;
            document.getElementById("edit_birth_date").value = studentDate;
            document.getElementById("edit_birth_city").value = studentCD;
            document.getElementById("edit_photo").value = studentPh;
            document.getElementById("edit_type").value = studentTy;
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
                        <div class="mb-3">
                            <label for="edit_full_name" class="form-label">الإسم الكامل</label>
                            <input type="text" class="form-control" id="edit_full_name" name="edit_full_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_NNI" class="form-label">الرقم الوطني</label>
                            <input type="text" class="form-control" id="edit_NNI" name="edit_NNI" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_phone" class="form-label">رقم الهاتف</label>
                            <input type="text" class="form-control" id="edit_phone" name="edit_phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_birth_city" class="form-label">محل الميلاد</label>
                            <input type="text" class="form-control" id="edit_birth_city" name="edit_birth_city" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_birth_date" class="form-label">تاريخ الميلاد</label>
                            <input type="date" class="form-control" id="edit_birth_date" name="edit_birth_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_photo" class="form-label">الصورة</label>
                            <input type="file" class="form-control" id="edit_photo" name="edit_photo"  accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label for="edit_type" class="form-label">القرءاة</label>
                            <select class="form-select" id="edit_type" name="edit_type" required>
                                <option value="نافع">نافع</option>
                                <option value="ورش">ورش</option>
                                <option value="قالون">قالون</option>
                                <option value="حفص">حفص</option>
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
                        <div class="mb-3">
                            <input type="text" class="form-control" name="birth_city" placeholder="محل الميلاد" required>
                        </div>
                        <div class="mb-3">
                            <input type="date" class="form-control" name="birth_date" placeholder="تاريخ الميلاد" required>
                        </div>
                        <div class="mb-3">
                            <input type="file" class="form-control" name="photo"  placeholder="الصورة" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <select class="form-select" name="type" required>
                                <option value="">-- القرءاة --</option>
                                <option value="نافع">نافع</option>
                                <option value="ورش">ورش</option>
                                <option value="قالون">قالون</option>
                                <option value="حفص">حفص</option>
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
