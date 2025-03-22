<?php
include '../db_connection.php'; // Include your database connection

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: ../home.php");
    exit;
}


// Check if the session variable 'userid' is set
if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../../index.php'; </script>";
    exit();
}

// Access session variables safely
$userid = $_SESSION['userid'];
$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null;


$query = "SELECT class_id, class_name FROM classes WHERE branch_id = 22";
$classResult = $conn->query($query);

if ($classResult === false) {
    die("Error fetching levels: " . $conn->error);
}

$jours = [
    "Saturday"  => "السبت",
    "Sunday"    => "الأحد",
    "Monday"    => "الإثنين",
    "Tuesday"   => "الثلاثاء",
    "Wednesday" => "الأربعاء",
    "Thursday"  => "الخميس",
    "Friday"    => "الجمعة"
];


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $phone = $_POST['phone'];
    $name = $_POST['name'];
    $phone_2 = $_POST['phone_2'];
    $profession = $_POST['profession'];
    $whatsapp_phone = $_POST['whatsapp_phone'];
    $phone_count = -1;
    // Check if the phone numbers already exist
    $check_sql = "SELECT COUNT(*) FROM agents WHERE phone = ? OR phone_2 = ? OR whatsapp_phone = ?";
    $stmt_v = $conn->prepare($check_sql);
    $stmt_v->bind_param("sss", $phone, $phone_2, $whatsapp_phone);
    $stmt_v->execute();
    $stmt_v->bind_result($phone_count);
    $stmt_v->fetch();
    $stmt_v->close();

    // If any phone number already exists, prevent insert
    if ($phone_count > 0) {
        $response = array('success' => false, 'message' => 'رقم الهاتف موجود بالفعل');
    } else {
        $sql = "INSERT INTO agents (phone, agent_name, phone_2, profession, whatsapp_phone)
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $phone, $name, $phone_2, $profession, $whatsapp_phone);
        
        if ($stmt->execute()) {
            $response = array('success' => true, 'message' => 'تمت إضافة الوكيل بنجاح');
        } else {
            $response = array('success' => false, 'message' => 'حدث خطأ: ' . $conn->error);
        }
        
        $stmt->close();
    }

    $conn->close();
}

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل التلاميذ</title>
    <link rel="stylesheet" href="../css/all.min.css" />
    <link rel="stylesheet" href="../css/bootstrap-4-5-2.min.css" />
    <link rel="stylesheet" href="css/css.css" />
</head>
<body>
    <!-- Direct Form Display -->
    <div class="form-container">
        <div class="form-header d-flex justify-content-between align-items-center">
            <h1 id="studentRegistrationLabe">تسجيل التلاميذ</h1>
            <button class="btn btn-success home" onclick="window.location.href='../home.php'">  الصفحة الرئيسية</button>
        </div>

        <div class="form-body">
            <form id="studentRegistrationForm" enctype="multipart/form-data" method="POST">
                    <!-- Section 1: تعريف الوكيل -->
                <div id="agentSection" class="form-row">
                    <div class="form-group col-md-6 mt-4" id="agentPhoneGroup">
                        <h3 class="col-12">تعريف الوكيل(ة)</h3>
                        <div class="input-group">
                            <input type="text" class="form-control" id="agentPhone" name="agentPhone" placeholder="أدخل رقم الاسم,الهاتف" required autocomplete="off">
                            <input type="hidden" class="form-control" id="agentId" name="agentId">
                            <input type="hidden" class="form-control" id="agentP" name="agentP">
                            <div id="agentDropdown" class="dropdown-menu" style="display: none;"></div>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-success" id="searchAgentButton">بحث</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="noAgentCheckbox" name="noAgentCheckbox" onclick="toggleAgentPhone()">
                            <label class="form-check-label" for="noAgentCheckbox">بدون وكيل(ة)</label>
                        </div>
                    </div>

                    <div class="form-group col-md-12">
                        <div class="card" id="agentInfoCard" style="display: none;">
                            <div>
                                <h2 class="card-title text-start text-success mb-3" id="agentName">
                                    اسم الوكيل(ة):
                                </h2>
                            </div>
                            <h3 class="card-text text-end alert-light bg-white border border-success rounded p-2 mb-3">
                                <strong>الطلاب المرتبطين</strong>
                            </h3>
                            <div id="relatedStudentsList" class="list-group list-group-flush">
                                <!-- Liste des étudiants sera ajoutée ici -->
                            </div>
                        </div>
                    </div>

                    <div class="section-separator"></div>
                        <div class="form-group col-md-4">
                            <label for="studentName">الاسم الكامل</label>
                            <input type="text" class="form-control" id="studentName" name="studentName" placeholder="أدخل االاسم الكامل" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="gender">الجنس</label>
                            <select class="form-control" id="gender" name="gender" required>
                                <option value="">اختر الجنس</option>
                                <option value="ذكر">ذكر</option>
                                <option value="أنثى">أنثى</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="birthDate">تاريخ الميلاد</label>
                            <input type="date" class="form-control" id="birthDate" name="birthDate">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="birthPlace">مكان الميلاد</label>
                            <input type="text" class="form-control" id="birthPlace" name="birthPlace" placeholder="أدخل مكان الميلاد" >
                        </div>
                        <div class="form-group col-md-4" id="studentPhoneContainer" style="display: none;">
                            <label for="studentphone">رقم هاتف تلميذ</label>
                            <input type="text" class="form-control" id="studentphone" name="studentphone" placeholder="أدخل رقم الهاتف">
                        </div>
                        <div class="section-separator"></div>
                        <div class="form-group col-md-4">
                            <label for="class">القسم </label>
                            <select class="form-control" id="class" name="class" required>
                                <option value="">اختر القسم</option>
                                <?php
                                    while ($cl = $classResult->fetch_assoc()) {
                                        echo '<option value="' . $cl['class_id'] . '">' . htmlspecialchars($cl['class_name']) . '</option>';
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="start"> البداية</label>
                            <input type="text" class="form-control" id="start" name="start" placeholder=" البداية" >
                        </div>
                        <div class="form-group col-md-4">
                            <label for="rewaya">الرواية </label>
                            <input type="text" class="form-control" id="rewaya" name="rewaya" placeholder="الرواية" >
                        </div>
                        <div class="form-group col-md-4">
                            <label for="elmoutoune">المتون التجويدية </label>
                            <input type="text" class="form-control" id="elmoutoune" name="elmoutoune" placeholder="المتون التجويدية" >
                        </div>
                        <div class="form-group col-md-4">
                            <label for="days">الأيام الماسبة</label>
                            <select class="form-control" id="days" name="days[]" multiple required>
                                <option value=""> --الأيام الماسبة--</option>
                                <?php
                                    foreach ($jours as $key => $jour) {
                                        echo '<option value="'.$jour.'">'.$jour.'</option>';
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="tdate"> التوقيت</label>
                            <select class="form-control" id="tdate" name="tdate" required>
                                <option value="">اختر التوقيت</option>
                                <option value="ذكر">الرابعة مساء</option>
                                <option value="أنثى">التاسعة مساء</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="date_din">تاريخ الإلتحاق</label>
                            <input type="date" class="form-control" id="date_din" name="date_din" required>
                        </div>
                        
                        <div class="form-group col-md-4">
                            <label for="studentPhoto">الصورة</label>
                            <input type="file" class="form-control" id="studentPhoto" name="studentPhoto" >
                        </div>
                        
                    </div>

                    <div class="section-separator"></div>
                    <div id="paymentNatureSection" class="form-row">
                        <h5 class="col-12">طبيعة التسديد</h5>
                        <div class="form-group col-md-6 d-flex justify-content-between">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="paymentNature" id="naturalPayment" value="طبيعي" required Checked>
                                <label class="form-check-label" for="naturalPayment">طبيعي</label>
                            </div>

                            <?php if ($role_id == 1): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="paymentNature" id="exemptPayment" value="معفى" required>
                                    <label class="form-check-label" for="exemptPayment">معفى</label>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="section-separator"></div>
                    <!-- Section for الرسوم الشهرية -->
                    <div id="monthlyFeesSection" class="form-row">
                        <h5 class="col-12">الرسوم الشهرية</h5>
                        <div class="form-group col-md-4">
                            <label for="fees">الرسوم</label>
                            <input type="number" class="form-control" id="fees" name="fees" placeholder="أدخل الرسوم" min="0" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="discount">الخصم</label>
                            <input type="number" class="form-control" id="discount" name="discount" placeholder="أدخل الخصم" min="0">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="remaining">المتبقي </label>
                            <input type="number" class="form-control" id="remaining" name="remaining" placeholder="أدخل المتبقي " min="0" readonly>
                        </div>
                    </div>
                    
                    <div class="form-footer text-right">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='../home.php';">إغلاق</button>
                        <button type="submit" class="btn btn-success">حفظ</button>
                    </div>
                </div>
            </form>
        </div>
    </div>


    
    <!-- Agent Form HTML (Hidden) -->
    <div id="agentFormContent" style="display: none;">
        <button id="closeFormButton" class="btn btn-secondary" style="display: none;" onclick="closeForm()">إغلاق</button>

        <form id="AgentForm" method="POST">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="phone">الهاتف</label>
                    <input type="text" class="form-control form-control-lg" id="phone" name="phone" placeholder="الهاتف" pattern="\d{8}" maxlength="8" required>
                    <small class="form-text text-muted">أدخل 8 أرقام فقط.</small>
                </div>
                <div class="form-group col-md-6">
                    <label for="name">الاسم</label>
                    <input type="text" class="form-control form-control-lg" id="name" name="name" placeholder="أدخل الاسم" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="phone_2">2 الهاتف</label>
                    <input type="text" class="form-control form-control-lg" id="phone_2" name="phone_2" placeholder="الهاتف" pattern="\d{8}" maxlength="8" required>
                    <small class="form-text text-muted">أدخل 8 أرقام فقط.</small>
                </div>
                <div class="form-group col-md-6">
                    <label for="profession">المهنة</label>
                    <input type="text" class="form-control form-control-lg" id="profession" name="profession" placeholder="المهنة">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col">
                    <label for="whatsapp_phone">رقم هاتف الواتس اب</label>
                    <input type="text" class="form-control form-control-lg" id="whatsapp_phone" name="whatsapp_phone" placeholder="الهاتف" pattern="\d{8}" maxlength="8" required>
                    <small class="form-text text-muted">أدخل 8 أرقام فقط.</small>
                </div>
            </div>
            <button type="submit" class="btn btn-success btn-lg btn-block">حفظ</button>
        </form>
    </div>


    <script>
        function closeForm() {
            const formContent = document.getElementById("agentFormContent");
            if (formContent) {
                formContent.style.display = "block";
            }
        }
    </script>


    <script>
            
        document.getElementById('studentRegistrationForm').addEventListener('submit', function (event) {
            event.preventDefault();
            let formData = new FormData(this);

            fetch('bac_add_student.php', {
                method: 'POST',
                body: formData,
            })
                .then(response => response.text())
                .then(text => {
                    try {
                        let jsonString = text.substring(text.indexOf('{'));
                        let json = JSON.parse(jsonString);

                        Swal.fire({
                            icon: json.success ? 'success' : 'error',
                            title: json.success ? 'تم التسجيل بنجاح' : 'حدث خطأ',
                            text: json.message + (json.success ? '\nID: ' + json.student_id : ''),
                            confirmButtonText: 'حسنًا',
                        }).then((result) => {
                            if (json.success && json.Url && result.isConfirmed) {
                                window.open(json.Url, '_blank');
                            }
                        });
                        if (json.success) this.reset();
                    } catch (error) {
                        console.error('Erreur de parsing JSON:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'حدث خطأ غير متوقع',
                            text: 'يرجى المحاولة مرة أخرى لاحقًا',
                            confirmButtonText: 'حسنًا',
                        });
                    }
                })
                .catch(error => {
                    console.error('Erreur de requête:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'حدث خطأ متوقع',
                        text: 'يرجى المحاولة مرة أخرى لاحقًا',
                        confirmButtonText: 'حسنًا',
                    });
                });
        });

    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var response = <?php echo json_encode($response); ?>;
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '',
                    text: response.message,

                    confirmButtonText: 'موافق'
                }).then(function() {
                    
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: '',
                    text: response.message

                }).then(function() {
                    // Optionally redirect or do something after error message
                    // window.location.href = 'some_other_page.php';
                });
            }
        });
    </script>

    
    <script src="../js/jquery-3.5.1.min.js"></script>
    <link rel="stylesheet" href="../css/sweetalert2.css">
    <script src="../js/sweetalert2.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>



<script>
    $(document).ready(function() {
        $('#searchAgentButton').on('click', function() {
            var agentPhone = $('#agentPhone').val();
            $('#phone').text(agentPhone);

            if (agentPhone.length > 0) {
                $.ajax({
                    url: '../check_agent_phone_search.php',
                    type: 'POST',
                    data: { phone: agentPhone },
                    dataType: 'json',
                    success: function(response) {
                        if (response.exists) {
                            $('#agentName').text('اسم الوكيل(ة):' +  ' ' + response.agent_name);
                            $('#agentId').val(response.agent_id);
                            $('#agentP').val(response.agentP);
                            $('#relatedStudentsList').empty();
                            // Create a table for displaying related students
                            var tableHtml = '<div class="table-responsive tbl"><table class="table table-bordered"><thead><tr><th  scope="col">الفرع</th><th  scope="col">الفصل</th><th  scope="col">اسم الطالب</th><th  scope="col">#</th></tr></thead><tbody></div>';

                            // Populate the table with student data
                            response.students.forEach(function(student) {
                                tableHtml += '<tr>' +
                                    '<td>' + student.branch + '</td>' + // الفرع
                                    '<td>' + student.class + '</td>' + // الفصل
                                    '<td>' + student.name + '</td>' +  // اسم الطالب
                                    '<td>' + student.id + '</td>' +    // ID
                                    '</tr>';
                            });

                            tableHtml += '</tbody></table>';


                            // Append the constructed table to the relatedStudentsList
                            $('#relatedStudentsList').append(tableHtml);

                            // Show the agent info card
                            $('#agentInfoCard').show();
                        } else {
                            Swal.fire({
                                title: 'تسجيل وكيل جديد',
                                html: document.getElementById('agentFormContent').innerHTML,
                                showConfirmButton: false,
                                showCloseButton: true,
                                allowOutsideClick: true,
                                closeButtonText: 'إغلاق',
                                width: '600px'
                            });
                        }
                    },
                    error: function() {
                        console.error('Error occurred during the AJAX call.');
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'يرجى إدخال رقم الهاتف للبحث',
                    confirmButtonText: 'موافق'
                });
            }
        });

        
    });
</script>


<script>
    $(document).ready(function() {
        $('#agentPhone').on('input', function() {
            var agentPhone = $(this).val();

            if (agentPhone.length > 0) {
                $.ajax({
                    url: '../check_agent_phone.php',
                    type: 'POST',
                    data: { phone: agentPhone },
                    dataType: 'json',
                    success: function(response) {
                        var $dropdown = $('#agentDropdown');
                        $dropdown.empty(); // Clear any previous results

                        if (response.matches && response.matches.length > 0) {
                            response.matches.forEach(function(agent) {
                                var agentItem = $('<a>', {
                                    class: 'dropdown-item agent-item',
                                    href: '#',
                                    text: agent.name,
                                    'data-agent-id': agent.id
                                });
                                $dropdown.append(agentItem);
                            });
                            $dropdown.show(); // Show dropdown
                        } else {
                            $dropdown.hide(); // Hide dropdown if no matches
                        }
                    },
                    error: function() {
                        console.error('Error occurred during the AJAX call.');
                    }
                });
            } else {
                $('#agentDropdown').hide(); // Hide if input is empty
            }
        });

        // Event for selecting an agent from the dropdown
        $(document).on('click', '.agent-item', function(e) {
            e.preventDefault();
            var selectedAgentName = $(this).text();
            $('#agentPhone').val(selectedAgentName); // Set selected name
            $('#agentDropdown').hide(); // Hide dropdown
        });

        // Hide dropdown when clicking outside of it
        $(document).click(function(event) {
            if (!$(event.target).closest('#agentPhone, #agentDropdown').length) {
                $('#agentDropdown').hide();
            }
        });
    });
</script>


    <script>
        $(document).ready(function() {
            $('#noAgentCheckbox').change(function() {
                $('#agentPhoneGroup').toggle(!this.checked);
            });
            $('#agentPhoneGroup').toggle(!$('#noAgentCheckbox').is(':checked'));
        });
    </script>


<script src="../js/jquery.min.js"></script>




    <!-- Bootstrap JS and dependencies -->
    <script src="../js/jquery-3.5.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.4.0.0.min.js"></script>

    <script>
        window.addEventListener('load', function() {
            $('#fees').val(1000);
            $('#discount').val(0);
            $('#remaining').val(1000);
        });

        $('#studentRegistrationModal').on('shown.bs.modal', function () {
            var fees = parseFloat(document.getElementById('fees').value) || 1000;
            var discount = 0;
            var remaining = fees - discount;
            document.getElementById('remaining').value = remaining.toFixed(2);
        });

        document.getElementById('fees').addEventListener('input', calculateRemaining);
        document.getElementById('discount').addEventListener('input', calculateRemaining);

        function calculateRemaining() {
            var fees = parseFloat(document.getElementById('fees').value) || 0;
            var discount = parseFloat(document.getElementById('discount').value) || 0;
            var remaining = fees - discount;
            document.getElementById('remaining').value = remaining.toFixed(2);
        }


        document.getElementById('naturalPayment').addEventListener('change', toggleMonthlyFeesSection);
        document.getElementById('exemptPayment').addEventListener('change', toggleMonthlyFeesSection);

        function toggleMonthlyFeesSection() {
            var exemptPayment = document.getElementById('exemptPayment').checked;
            var monthlyFeesSection = document.getElementById('monthlyFeesSection');
            var feesInput = document.getElementById('fees');
            var discountInput = document.getElementById('discount');
            var remainingInput = document.getElementById('remaining');

            if (exemptPayment) {
                monthlyFeesSection.style.display = 'none';
                feesInput.value = '';
                discountInput.value = '';
                remainingInput.value = '';
                feesInput.disabled = true;
                discountInput.disabled = true;
                remainingInput.disabled = true;
            } else {
                monthlyFeesSection.style.display = 'flex';
                feesInput.disabled = false;
                discountInput.disabled = false;
                remainingInput.disabled = false;
            }
        }

        window.addEventListener('load', function() {
            toggleMonthlyFeesSection();

        });

        document.getElementById('noAgentCheckbox').addEventListener('change', toggleAgentPhone);

        function toggleAgentPhone() {
            var noAgentCheckbox = document.getElementById('noAgentCheckbox').checked;
            var agentPhoneGroup = document.getElementById('agentPhoneGroup');
            var agentPhone = document.getElementById('agentPhone');
            var studentPhoneGroup = document.getElementById('studentPhoneGroup');
            var studentPhone = document.getElementById('studentPhone');

            if (noAgentCheckbox) {
                agentPhoneGroup.style.display = 'none';
                agentPhone.disabled = true;
                agentPhone.value = '';

                studentPhoneGroup.style.display = 'block';
                studentPhone.required = true;
            } else {
                agentPhoneGroup.style.display = 'block';
                agentPhone.disabled = false;
                studentPhoneGroup.style.display = 'none';
                studentPhone.required = false;
                studentPhone.value = '';
            }
        }
        window.addEventListener('load', function() {

            toggleAgentPhone();
        });

    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const noAgentCheckbox = document.getElementById('noAgentCheckbox');
            const studentPhoneContainer = document.getElementById('studentPhoneContainer');
            const studentPhoneInput = document.getElementById('studentphone');

            function toggleStudentPhoneField() {
                if (noAgentCheckbox.checked) {
                    studentPhoneContainer.style.display = 'block';
                    studentPhoneInput.setAttribute('required', 'required');
                } else {
                    studentPhoneContainer.style.display = 'none';
                    studentPhoneInput.removeAttribute('required');
                    studentPhoneInput.value = '';
                }
            }

            toggleStudentPhoneField();
            noAgentCheckbox.addEventListener('change', toggleStudentPhoneField);
        });
    </script>




    
    <script src="../js/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="../css/jquery-ui.min.css">

    <script>
        $(document).ready(function() {
            $('#name_student').autocomplete({
            source: function(request, response) {
                $.ajax({
                url: '../fetchh.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    term: request.term
                },
                success: function(data) {
                    response(data);
                }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                $('#name_student').val(ui.item.value);
            }
            });
        });
    </script>

    <script>
        document.getElementById('agentPhone').addEventListener('input', function() {
            var agentPhone = this.value;

            if (agentPhone.length >= 2) {
                $.ajax({
                    url: '../fetch_agent_info.php',
                    type: 'POST',
                    data: { phone: agentPhone },
                    success: function(response) {
                        var data = JSON.parse(response);
                        
                        if (data.success) {
                            document.getElementById('agentName').textContent = 'اسم الوكيل: ' + data.agent_name;
                            var relatedStudentsList = document.getElementById('relatedStudentsList');
                            relatedStudentsList.innerHTML = '';
                            data.related_students.forEach(function(student) {
                                var listItem = document.createElement('li');
                                listItem.textContent = student;
                                relatedStudentsList.appendChild(listItem);
                            });

                            document.getElementById('agentInfoCard').style.display = 'block';
                        } else {
                            document.getElementById('agentName').textContent = 'لم يتم العثور على وكيل.';
                            document.getElementById('relatedStudentsList').innerHTML = '';
                            document.getElementById('agentInfoCard').style.display = 'block';
                        }
                    },
                    error: function() {
                        document.getElementById('agentName').textContent = 'حدث خطأ أثناء جلب معلومات الوكيل.';
                        document.getElementById('relatedStudentsList').innerHTML = '';
                        document.getElementById('agentInfoCard').style.display = 'block';
                    }
                });
            } else {
                document.getElementById('agentInfoCard').style.display = 'none';
                document.getElementById('agentName').textContent = '';
                document.getElementById('relatedStudentsList').innerHTML = '';
            }
        });
    </script>


</body>
</html>

<?php $classResult->free(); $conn->close(); ?>