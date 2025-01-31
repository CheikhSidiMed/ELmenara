<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connection.php';

session_start();

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error: User is not logged in.']);
    header("Location: home.php");
    exit;
}


if (!isset($_POST['selected_students']) || empty($_POST['selected_students'])) {
    echo "<div style='color: red;'>⚠️ لم يتم اختيار أي طالب.</div>";
    exit;
}

if (!isset($_SESSION['dept_students'])) {
    $_SESSION['dept_students'] = [];
}

$currentTime = time(); 

// تنظيف الطلاب الذين تمت معالجتهم قبل أكثر من 10 دقائق
foreach ($_SESSION['dept_students'] as $id => $timestamp) {
    if ($currentTime - $timestamp > 600) {
        unset($_SESSION['dept_students'][$id]);
    }
}

$selectedStudents = $_POST['selected_students'];

foreach ($selectedStudents as $studentData) {
    // تقسيم البيانات المستلمة من خانة الاختيار
    [$student_id, $remaining_amount, $unpaid_months] = explode('|', $studentData);
    $student_id = intval($student_id);
    $remaining_amount = floatval($remaining_amount);
    $unpaid_months = array_filter(explode(',', $unpaid_months));

    if (isset($_SESSION['dept_students'][$student_id])) {
        echo "<div style='margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 8px; background-color: #e6f7e6;'>
        <span style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>
            ✅ تم إرسال الرسالة إلى الطالب : <strong style='color: #007bff;'>$student_id</strong>
        </span>
    </div>";
        continue;
    }

    // جلب بيانات الطالب من قاعدة البيانات
    $query = $conn->prepare("
        SELECT s.student_name, s.phone, a.whatsapp_phone 
        FROM students s 
        LEFT JOIN agents a ON s.agent_id = a.agent_id 
        WHERE s.id = ?
    ");
    $query->bind_param("i", $student_id);
    $query->execute();
    $result = $query->get_result();

    if ($result && $student = $result->fetch_assoc()) {
        $student_name = htmlspecialchars($student['student_name'], ENT_QUOTES, 'UTF-8');
        $phone = !empty($student['phone']) && $student['phone'] != '0' 
            ? $student['phone'] 
            : $student['whatsapp_phone'];

        if (!empty($phone)) {
            // بناء الرسالة
            $unpaidMonthsText = !empty($unpaid_months) ? implode(', ', $unpaid_months) : '';
            $remainingAmountText = $remaining_amount > 0 ? "المبلغ المتبقي: $remaining_amount." : '';
            $message = "
            السلام عليكم ورحمة الله تعالى وبركاته
            
            🛑 *تذكير*
            
            *السيد الوكيل - المحترم، شريكنا في العملية التعليمية،*  
            تحية طيبة وبعد،  
            تود إدارة الحسابات تذكيركم بضرورة الإسراع في دفع الرسوم الشهرية للطالب(ة):  
            *: $student_name*  
            *الأشهر غير المدفوعة: $unpaidMonthsText*  
            
            وذلك لضمان استمرار العملية التعليمية بكل سلاسة. 
            
            🔴 *طرق الدفع المتاحة:*  
            - الدفع المباشر  
            - التطبيقات البنكية: بنكيلي/مصرفي/السداد/بيم بانك (26056959)
            
            *مع خالص التقدير والاحترام،*  
            إدارة الحسابات
            ";
            $encodedMessage = urlencode($message);
            $whatsappUrl = "https://wa.me/222$phone?text=$encodedMessage";

            // عرض زر الإرسال
            echo "
            <div style='margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; display: flex; justify-content: space-between; align-items: center;'>
                <span style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>
                    📩 رسالة جاهزة للطالب : <strong style='color: #007bff;'>$student_name</strong>
                </span>
                <a href=\"$whatsappUrl\" target=\"_blank\" 
                    onclick=\"markAsProcessed($student_id)\"
                    style='display: inline-block; padding: 8px 12px; background-color: #25D366; color: #fff; text-decoration: none; border-radius: 5px; font-family: Arial, sans-serif; font-size: 14px;'>
                    📤 إرسال إلى $phone
                </a>
            </div>";
        } else {
            // رسالة في حالة عدم وجود رقم هاتف
            echo "
            <div style='margin-bottom: 15px; padding: 15px; border: 1px solid red; border-radius: 8px; background-color: #FFCCCC;'>
                <span style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>
                    ⚠️ لا يوجد رقم هاتف متاح للطالب : <strong style='color: #007bff;'>$student_name</strong>
                </span>
            </div>";
        }
    } else {
        echo "<div style='color: orange;'>⚠️ الطالب غير موجود برقم ID: $student_id</div>";
    }

    // تسجيل الطالب كمُعالج
}
?>



<script>
function markAsProcessed(studentId) {
    fetch('dept_mark_processed.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ student_id: studentId })
    })
    .then(response => {
        if (response.ok) {
            location.reload(); // Recharger la page pour mettre à jour l'affichage
        } else {
            alert('خطأ أثناء معالجة الطالب.');
        }
    })
    .catch(error => {
        console.error('Erreur réseau :', error);
    });
}
</script>


