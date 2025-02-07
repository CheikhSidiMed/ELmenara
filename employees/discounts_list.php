<?php
// Include database connection
include 'db_connection.php'; // Adjust the path as needed

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}
$user_id = $_SESSION['userid'];

// Query to fetch students with a discount and their class names
$sql = "SELECT s.id, s.student_name, c.class_name, s.fees, s.discount, (s.fees - s.discount) AS remaining 
        FROM students s
        JOIN classes c ON s.class_id = c.class_id
        LEFT JOIN branches b ON s.branch_id = b.branch_id
        JOIN user_branch ub ON b.branch_id = ub.branch_id AND ub.user_id = ($user_id)
        WHERE s.fees > 0.00";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لائحة تخفيضات</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/bootstrap-4-5-2.min.css">
    
    <!-- Font Awesome -->
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="fonts/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/tajawal.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f4f7f6;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            margin-top: 50px;
        }

        h2 {
            margin-bottom: 30px;
            font-weight: bold;
            color: #1a73e8;
            text-align: center;
            position: relative;
        }

        h2::after {
            content: "";
            width: 60px;
            height: 4px;
            background-color: #1a73e8;
            display: block;
            margin: 10px auto;
        }

        .home-btn {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .home-btn .btn {
            font-size: 1.2rem;
            padding: 10px 24px;
            background-color: #1a73e8;
            color: white;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        .home-btn .btn:hover {
            background-color: #155bb5;
        }

        .home-btn .btn i {
            margin-left: 8px;
        }

        .table {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }

        .table thead {
            background-color: #1a73e8;
            color: #fff;
            font-weight: bold;
        }

        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }

        .table tbody tr {
            transition: background-color 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: #f0f8ff;
        }

        .btn {
            border-radius: 8px;
            padding: 8px 16px;
            transition: background-color 0.3s ease;
        }

        .btn-warning {
            background-color: #ff9800;
            color: white;
        }

        .btn-warning:hover {
            background-color: #e68900;
        }

        .modal-header {
            background-color: #1a73e8;
            color: white;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        .modal-content {
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        .form-control {
            border-radius: 6px;
            padding: 12px;
            font-size: 1rem;
            border: 1px solid #ddd;
        }

        .form-control:focus {
            box-shadow: 0 0 8px rgba(26, 115, 232, 0.3);
            border-color: #1a73e8;
        }

        .btn-primary {
            background-color: #1a73e8;
            border-color: #1a73e8;
            transition: background-color 0.3s ease;
        }
        .search-box input {
            border: 2px solid #155bb5;
            padding: 10px;
            border-radius: 5px;
            width: 100%;
        }

        .btn-primary:hover {
            background-color: #155bb5;
            border-color: #155bb5;
        }

        .modal-footer .btn {
            border-radius: 8px;
            padding: 10px 16px;
        }
        .header-row {
            display: flex;
            align-items: center; /* Aligns items vertically in the center */
            justify-content: space-between; /* Distributes space between items */
            margin-bottom: 1rem; /* Optional spacing below the row */
        }

        .header-row h2 {
            margin: 0; /* Removes default margin from heading */
        }

        .header-row .home-btn .btn {
            padding: 0.5rem 1rem; /* Adjusts padding for button */
            border-radius: 5px; /* Optional border radius for rounded button */
        }

        .close {
            color: #fff;
            opacity: 1;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>

    <div class="container"  style="direction: rtl;">
        <div class="header-row">
            <h2>لائحة تخفيضات</h2>
            <div class="home-btn">
                <a href="home.php" class="btn">
                <i class="bi bi-house-gear-fill"></i> الرئيسية
                </a>
            </div>
        </div>

        <div class="search-box mb-4">
            <input type="text" id="searchInput" class="form-control" placeholder="البحث عن طريق اسم الطالب...">
        </div>

        <table class="table table-bordered table-striped mt-4">
            <thead class="thead-dark">
                <tr>
                    <th>الاسم الكامل</th>
                    <th>القسم</th>
                    <th>الرسوم</th>
                    <th>الخصم</th>
                    <th>المتبقى</th>
                    <th>تعديل</th>
                </tr>
            </thead>
            <tbody  id="suspendedStudentsTableBody">
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['student_name']}</td>
                                <td>{$row['class_name']}</td>
                                <td>{$row['fees']}</td>
                                <td>{$row['discount']}</td>
                                <td>{$row['remaining']}</td>
                                <td>
                                    <button class='btn btn-warning btn-sm' data-toggle='modal' data-target='#editModal' 
                                    data-id='{$row['id']}' data-name='{$row['student_name']}' 
                                    data-fees='{$row['fees']}' data-discount='{$row['discount']}'>تعديل الخصم</button>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center'>لا يوجد تخفيضات</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Discount Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">تعديل الخصم</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editForm" method="POST" action="update_discount.php">
                        <input type="hidden" id="id" name="id">
                        <div class="form-group">
                            <label for="student_name">الاسم الكامل</label>
                            <input type="text" class="form-control" id="student_name" name="student_name" readonly>
                        </div>
                        <div class="form-group">
                            <label for="fees">الرسوم</label>
                            <input type="number" class="form-control" id="fees" name="fees" readonly>
                        </div>
                        <div class="form-group">
                            <label for="discount">الخصم الجديد</label>
                            <input type="number" class="form-control" id="discount" name="discount" required>
                        </div>
                        <div class="form-group">
                            <label for="remaining">المتبقى</label>
                            <input type="number" class="form-control" id="remaining" name="remaining" readonly>
                        </div>
                        <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="js/jquery-3.5.1.slim.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap-4.5.2.min.js"></script>
    
    <script>
        // Populate the modal with data when edit button is clicked
        $('#editModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var id = button.data('id');
            var student_name = button.data('name');
            var fees = button.data('fees');
            var discount = button.data('discount');

            var modal = $(this);
            modal.find('.modal-body #id').val(id);
            modal.find('.modal-body #student_name').val(student_name);
            modal.find('.modal-body #fees').val(fees);
            modal.find('.modal-body #discount').val(discount);
            modal.find('.modal-body #remaining').val(fees - discount);
        });

        // Update the remaining amount when discount is changed
        $('#discount').on('input', function () {
            var fees = $('#fees').val();
            var discount = $(this).val();
            $('#remaining').val(fees - discount);
        });
    </script>

    
<script src="js/jquery-3.5.1.min.js"></script>
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
</body>
</html>


