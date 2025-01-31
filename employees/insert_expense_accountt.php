<?php
// Include database connection
include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}


// Fetch the next account number
$nextAccountNumber = 6201; // Default starting number
$sql = "SELECT MAX(account_number) AS last_account_number FROM expense_accounts"; // Adjust table name if needed
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lastAccountNumber = $row['last_account_number'];
    if (!is_null($lastAccountNumber)) {
        $nextAccountNumber = $lastAccountNumber + 1;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فتح حساب مصاريف</title>
    <link rel="stylesheet" href="css/bootstrap-4-5-2.min.css">
    <link rel="stylesheet" href="css/sweetalert2.css">
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Amiri', serif;
            background-color: #f8f9fa;
            direction: rtl;
        }
        .container {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 800px;
            margin: 40px auto;
        }
        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #343a40;
            margin-bottom: 20px;
        }
        label {
            font-weight: 600;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
            border: none;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center">فتح حساب مصاريف</h1>
        <form id="ExpenseAccountForm">
            <div class="form-group">
                <label for="Nbr">رقم الحساب</label>
                <input
                    type="number"
                    class="form-control"
                    id="Nbr"
                    name="Nbr"
                    value="<?php echo $nextAccountNumber; ?>"
                    min="0"
                    placeholder="أدخل رقم الحساب"
                    readonly
                    required
                >
            </div>
            <div class="form-group">
                <label for="Nom">اسم الحساب</label>
                <input type="text" class="form-control" id="Nom" name="Nom" placeholder="أدخل اسم الحساب" required>
            </div>
            <div class="form-group">
                <label for="Category">الفئة</label>
                <input type="text" class="form-control" id="Category" name="Category" placeholder="أدخل الفئة" required>
            </div>
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary">حفظ</button>
                <a href="home.php" class="btn btn-secondary">الصفحة الرئيسية</a>
            </div>
        </form>
    </div>

    <!-- Include SweetAlert2 -->
    <script src="js/sweetalert2.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script>
        document.getElementById('ExpenseAccountForm').addEventListener('submit', function (e) {
            e.preventDefault(); // Prevent the form from submitting traditionally

            // Get form data
            var formData = new FormData(document.getElementById('ExpenseAccountForm'));

            // Send AJAX request to insert_expense_account.php
            fetch('insert_expense_account.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم بنجاح',
                        text: data.message,
                        confirmButtonText: 'موافق'
                    }).then(() => {
                        document.getElementById('ExpenseAccountForm').reset();
                        document.getElementById('Nbr').value = parseInt(document.getElementById('Nbr').value) + 1;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: data.message,
                        confirmButtonText: 'موافق'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'حدث خطأ أثناء معالجة الطلب.',
                    confirmButtonText: 'موافق'
                });
                console.error('There was a problem with the fetch operation:', error);
            });
        });
    </script>
</body>
</html>