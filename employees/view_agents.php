<?php

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../index.php'; </script>";
    exit();
}

include 'db_connection.php';
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بيانات الوكلاء</title>
    <link rel="shortcut icon" type="image/png" href="../images/menar.png">
<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
<link href="css/bootstrap-icons.css" rel="stylesheet">
<link href="fonts/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="css/jquery-base-ui.css">
<link rel="stylesheet" href="../assets/css/styles.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Amiri&display=swap">
<link rel="stylesheet" href="css/sweetalert2.css">

<!-- jQuery -->
<script src="js/jquery-3.5.1.min.js"></script>
<script src="js/jquery-ui.min.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap-4.5.2.min.js"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            direction: rtl;

        }
        .bny{
            padding: 4px 15px !important;
            font-size:  20px !important;
            color: #fff;
        }
        h2 {
            font-family: 'Amiri', serif;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5rem;
            color: #4e73df;
        }
        .container {
            margin-top: 50px;
        }
        .table {
            background-color: #ffffff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }
        .table th {
            background-color: #4e73df;
            color: #ffffff;
        }
        .table tbody tr {
            transition: all 0.3s;
        }
        .table tbody tr:hover {
            background-color: #f1f1f1;
        }
        .search-container input {
            width: 100%;
            padding: 10px;
            border: 2px solid #4e73df;
            border-radius: 8px;
            font-size: 16px;
        }
        .tbl {
            overflow-x: auto;
            width: 100%;
        }
        table {
            min-width: 900px;
            border-collapse: collapse;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <a href="home.php" class="btn btn-secondary"><i class="bi bi-house-door-fill"></i> العودة إلى الصفحة الرئيسية</a>
        <h2 class="mb-4">قائمة الوكلاء</h2>

        <!-- Search Input -->
        <div class="search-container mb-4">
            <input type="text" id="agentSearch" placeholder="البحث عن الوكلاء حسب الاسم أو الهاتف">
        </div>

        <div class="table-responsive tbl">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>رقم</th>
                        <th>الهاتف</th>
                        <th>الإسم</th>
                        <th>الهاتف 2</th>
                        <th>المهنة</th>
                        <th>رقم هاتف الواتس اب</th>
                        <th>تعديل</th>
                    </tr>
                </thead>
                <tbody id="agentTableBody">
                </tbody>
            </table>
        </div>
    </div>




    <script src="js/sweetalert2.min.js"></script>

    <script>
        $(document).ready(function () {
            function fetchAgents(query = '') {
                $.ajax({
                    url: 'search_agents.php',
                    method: 'GET',
                    data: { term: query },
                    success: function (data) {
                        $('#agentTableBody').html(data);
                    }
                });
            }

            fetchAgents();

            // Search for agents when typing in the search box
            $('#agentSearch').on('input', function () {
                const searchTerm = $(this).val();
                fetchAgents(searchTerm);
            });

            $(document).on('click', '.edit-btn', function () {
                const agentId = $(this).data('id');
                
                $.ajax({
                    url: 'get_agent.php',
                    method: 'GET',
                    data: { agent_id: agentId },
                    success: function (data) {
                        const agent = JSON.parse(data);

                        $('#editAgentId').val(agent.id);
                        $('#editAgentName').val(agent.name);
                        $('#editAgentPhone').val(agent.phone);
                        $('#editAgentPhone2').val(agent.phone2);
                        $('#editAgentJob').val(agent.job);
                        $('#editAgentWhatsApp').val(agent.whatsapp);

                        // Show the modal
                        $('#editAgentModal').modal('show');
                    }
                });
            });

            $(document).on('click', '.del-btn', function () {
                const agentId = $(this).data('id');

                Swal.fire({
                    title: "هل أنت متأكد؟",
                    text: "لن تتمكن من التراجع عن هذا!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "نعم، احذف!",
                    cancelButtonText: "إلغاء"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'delete_agent.php',
                            method: 'POST',
                            data: { agent_id: agentId },
                            dataType: "json",
                            success: function (response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: "تم الحذف!",
                                        text: response.success,
                                        icon: "success"
                                    }).then(() => {
                                        location.reload(); // Reload page to reflect changes
                                    });
                                } else {
                                    Swal.fire({
                                        title: "خطأ!",
                                        text: response.error,
                                        icon: "error"
                                    });
                                }
                            },
                            error: function () {
                                Swal.fire({
                                    title: "خطأ!",
                                    text: "حدث خطأ أثناء محاولة الحذف.",
                                    icon: "error"
                                });
                            }
                        });
                    }
                });
            });


            $('#editAgentForm').on('submit', function (event) {
                event.preventDefault();

                const formData = $(this).serialize();  // Collect form data

                $.ajax({
                    url: 'update_agent.php',
                    method: 'POST',
                    data: formData,
                    success: function (response) {
                        const result = JSON.parse(response);
                        
                        if (result.success) {
                            // Close the modal
                            $('#editAgentModal').modal('hide');

                            // Reload the agents' table
                            fetchAgents();
                        } else {
                            alert('Failed to update agent');
                        }
                    }
                });
            });
        });
    </script>

    <!-- Edit Modal -->
<div class="modal fade" id="editAgentModal" tabindex="-1" role="dialog" aria-labelledby="editAgentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAgentModalLabel">تعديل بيانات الوكيل</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editAgentForm">
                    <input type="hidden" id="editAgentId" name="agent_id">
                    
                    <div class="form-group">
                        <label for="editAgentName">الإسم</label>
                        <input type="text" class="form-control" id="editAgentName" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editAgentPhone">الهاتف</label>
                        <input type="text" class="form-control" id="editAgentPhone" name="phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editAgentPhone2">الهاتف 2</label>
                        <input type="text" class="form-control" id="editAgentPhone2" name="phone2">
                    </div>
                    
                    <div class="form-group">
                        <label for="editAgentJob">المهنة</label>
                        <input type="text" class="form-control" id="editAgentJob" name="job" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editAgentWhatsApp">رقم هاتف الواتس اب</label>
                        <input type="text" class="form-control" id="editAgentWhatsApp" name="whatsapp">
                    </div>

                    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>
