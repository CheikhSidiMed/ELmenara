<?php
// Start the session and include the database connection
include 'db_connection.php';


session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


// Fetch user data based on the user ID passed in the URL
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Fetch the user data
    $query = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $query->bind_param("i", $user_id);
    $query->execute();
    $result = $query->get_result();
    $user = $result->fetch_assoc();
} else {
    echo "<script>alert('User ID not provided!');</script>";
    echo "<script type='text/javascript'> document.location = 'users.php'; </script>";
    exit();
}

// Update user information
if (isset($_POST['edit_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $class_id = $_POST['class'] ?? 0;
    $branches = isset($_POST['branches']) ? $_POST['branches'] : [];

    // Update user data in the database
    $updateQuery = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
    $updateQuery->bind_param("ssi", $username, $password, $user_id);

    if ($updateQuery->execute()) {

        $conn->query("DELETE FROM user_branch WHERE user_id = $user_id");

        if (!empty($branches)) {
            $stmt = $conn->prepare("INSERT INTO user_branch (user_id, class_id, branch_id) VALUES (?, ?, ?)");
            foreach ($branches as $branch_id) {
                $stmt->bind_param("iii", $user_id, $class_id, $branch_id);
                $stmt->execute();
            }
        }
        $_SESSION['edit_success'] = true;
        header('Location: users.php');
        exit();
    } else {
        echo "<script>alert('حدث خطأ أثناء محاولة تحديث المستخدم.');</script>";
    }
}



$branchesQuery = "SELECT branch_id, branch_name FROM branches";
$branchesResult = $conn->query($branchesQuery);

// Récupérer les branches déjà associées à l'utilisateur
$userBranchesQuery = "SELECT branch_id, class_id FROM user_branch WHERE user_id = ?";
$stmt = $conn->prepare($userBranchesQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userBranchesResult = $stmt->get_result();
$selectedBranches = [];
$selectedclass = 0;

while ($row = $userBranchesResult->fetch_assoc()) {
    $selectedBranches[] = $row['branch_id'];
    $selectedclass = $row['class_id'];
}


$classesResult = [];
if ($selectedBranches && ($selectedclass !== 0)) {
    $classesQuery = "SELECT class_id, class_name
        FROM classes WHERE branch_id = ?";
    $stmt = $conn->prepare($classesQuery);
    $stmt->bind_param("i", $selectedBranches[0]);
    $stmt->execute();
    $classesResult = $stmt->get_result();
    $stmt->close(); // Close the statement after use
}

?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل المستخدم</title>
    <link href="css/bootstrap-5.3.1.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Amiri', serif;
            direction: rtl;
            text-align: right;
            background-color: #f7f7f7;
            padding: 30px;
        }

        .container {
            background-color: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }

        h2 {
            color: #1BA078;
            font-weight: bold;
            font-size: 32px;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-label {
            font-weight: bold;
            color: #1BA078;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #1BA078;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 16px;
            background-color: #f9f9f9;
        }

        .form-control:focus {
            border-color: #14865b;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: #1BA078;
            border: none;
            padding: 10px 30px;
            border-radius: 10px;
            font-size: 18px;
            transition: background-color 0.3s ease;
            display: block;
            width: 100%;
        }

        .btn-primary:hover {
            background-color: #14865b;
        }

        .btn-primary:focus {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>تعديل المستخدم</h2>
        <form id="editUserForm" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">اسم المستخدم</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">كلمة المرور</label>
                <input type="password" class="form-control" id="password" name="password" value="<?= htmlspecialchars($user['password']); ?>" required>
            </div>
            <div class="form-group w-100">
                <label for="branch">الفروع</label>
                <select class="form-control" id="branch" name="branches[]" <?php if ($selectedclass === 0) { ?> multiple <?php } ?> required>
                    <?php if ($branchesResult->num_rows > 0) {
                        while ($row = $branchesResult->fetch_assoc()): ?>
                            <option value="<?= $row['branch_id']; ?>"
                                <?= in_array($row['branch_id'], $selectedBranches) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($row['branch_name']); ?>
                            </option>
                        <?php endwhile;
                    } else {
                        echo "<option value=''>لا يوجد فروع</option>";
                    } ?>
                </select>
                <small class="text-muted text-success">يمكنك اختيار عدة فروع باستخدام Ctrl (Windows) أو Cmd (Mac)</small>
            </div>

            <div class="form-group w-100">
                <?php if ($selectedclass !== 0) { ?>
                <label for="class">الصف</label>
                <select class="form-control" id="class" name="class" required>
                    <option value="">اختر الصف</option>
                    <?php
                    if ($selectedBranches && $classesResult->num_rows > 0) {
                        while($classRow = $classesResult->fetch_assoc()) {
                            echo "<option value='{$classRow['class_id']}'" . ($selectedclass == $classRow['class_id'] ? " selected" : "") . ">{$classRow['class_name']}</option>";
                        }
                    }
                    ?>
                </select>
                <?php } ?>
            </div>
            <button type="submit" name="edit_user" class="btn btn-primary">تحديث المستخدم</button>
        </form>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('change', function (event) {
    if (event.target && event.target.id === 'branch') {
        let branchId = event.target.value;
        let classSelect = document.getElementById('class');

        if (branchId) {
            fetch('get_classe_s.php?branch_id=' + branchId)
                .then(response => response.text())
                .then(data => {
                    console.log("Données reçues:", data);
                    classSelect.innerHTML = data;
                })
                .catch(error => console.error('Erreur:', error));
        } else {
            classSelect.innerHTML = '<option value="">اختر الصف</option>';
        }
    }
});
    </script>
</body>
</html>
