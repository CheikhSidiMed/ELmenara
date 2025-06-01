<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['userid'])) {
    echo "<script type='text/javascript'> document.location = '../../index.php'; </script>";
    exit();
}

include '../db_connection.php';

$sql = "SELECT year_name FROM academic_years ORDER BY start_date DESC LIMIT 1";
$result = $conn->query($sql);

$last_year = "";
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_year = $row['year_name'];
}

$student_id = $_POST['student_id'] ?? '';
$status = $_POST['status'] ?? '';
$note = $_POST['note'] ?? '';

$sql = "SELECT s.student_name, b.branch_name, l.level_name, c.class_name,
            COALESCE(a.whatsapp_phone, s.phone) AS whatsapp_number
        FROM students s
        LEFT JOIN branches b ON s.branch_id = b.branch_id
        LEFT JOIN levels l ON s.level_id = l.id
        LEFT JOIN classes c ON s.class_id = c.class_id
        LEFT JOIN agents a ON s.agent_id = a.agent_id
        WHERE s.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$stmt->bind_result($student_name, $branch_name, $level_name, $class_name, $whatsapp_number);
$stmt->fetch();

$stmt->close();
$conn->close();

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إفادة تحديد المستوى عند الخروج</title>
    <link rel="stylesheet" href="../css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link rel="stylesheet" href="../css/expoArab.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            /* font-family: 'Tajawal', sans-serif; */
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            color: #333;
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(90deg, #1a3a4c 0%, #2c5f7c 100%);
            color: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        header h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
        }
        
        .app-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .receipt {
            padding: 30px;
            position: relative;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 120px;
            color: rgba(26, 58, 76, 0.05);
            z-index: 0;
            pointer-events: none;
            font-weight: bold;
        }
        
        .receipt-header {
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .header-image {
            width: 100%;
            height: auto;
            border-bottom: 2px solid #007b5e;
            margin-bottom: 10px;
        }
        .header-imag {
            width: 100%;
            height: auto;
        }
        
        .summary-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding: 10px 15px;
            border: 1px solid #007b5e;
            border-radius: 8px;
            background: #e6f7f2;
            position: relative;
            z-index: 1;
        }
        
        .summary-container div {
            font-weight: bold;
            color: #007b5e;
            font-size: 18px;
        }
        
        .title-container {
            text-align: center;
            margin: 2px 0;
            position: relative;
            z-index: 1;
        }
        
        .underlined-title {
            font-size: 28px;
            font-weight: bold;
            color: #1a3a4c;
            text-decoration: underline;
            text-decoration-color: #007b5e;
            text-decoration-thickness: 2px;
            padding: 1px;
            display: inline-block;
        }
        
        .info-container {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
            position: relative;
            z-index: 1;
        }
        
        .info-container div {
            flex: 1;
            text-align: right;
            font-weight: bold;
            color: #1a3a4c;
            padding: 2px 15px;
            border-bottom: 1px dashed #d1d9e0;
            font-size: 18px;
        }
        
        .info-container .value {
            color: #2c5f7c;
            font-weight: normal;
            margin-right: 10px;
            display: inline-block;
            min-width: 200px;
            border-bottom: 1px solid #a0aec0;
            padding-bottom: 1px;
        }
        
        .notes-container {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #3a86ff;
            position: relative;
            z-index: 1;
        }
        
        .reminders {
            margin: 25px 0;
            padding: 0 15px;
            position: relative;
            z-index: 1;
        }
        
        .reminders div {
            margin-bottom: 1px;
            padding-right: 25px;
            position: relative;
            color: #000;
            font-size: 16px;
            line-height: 1.6;
            text-align: right;

        }
        
        .reminders div:before {
            content: "•";
            position: absolute;
            right: 0;
            color: #007b5e;
            font-size: 20px;
            margin-left: -10px;
        }
        
        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 30px;
            position: relative;
            z-index: 1;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 14px 28px;
            background: #2c5f7c;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            /* font-family: 'Tajawal', sans-serif; */
            font-size: 18px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }
        
        .btn-print {
            background: linear-gradient(to right, #28a745, #218838);
        }
        
        .btn-whatsapp {
            background: linear-gradient(to right, #25D366, #128C7E);
        }
        
        .btn i {
            margin-left: 10px;
            font-size: 20px;
        }
        
        .footer-image {
            width: 100%;
            height: auto;
            margin-top: 0px;
            border-top: 2px solid #007b5e;
            position: relative;
            z-index: 1;
        }
        
        .signature-area {
            display: flex;
            justify-content: space-between;
            margin-top: 1px;
            position: relative;
            z-index: 1;
        }
        
        .signature-box {
            text-align: center;
            width: 45%;
        }
        
        .signature-line {
            border-bottom: 1px solid #1a3a4c;
            height: 20px;
            margin-bottom: 10px;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .action-buttons, header {
                display: none;
            }
            
            .container {
                width: 100%;
                max-width: 100%;
                padding: 10px;
            }
            
            .app-container {
                box-shadow: none;
                border: none;
            }
            
            .receipt {
                padding: 15px;
            }
            
            @page {
                size: A5;
                margin: 0.5cm;
            }
        }
        
        @media (max-width: 768px) {
            .info-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .signature-area {
                flex-direction: column;
                gap: 30px;
            }
            
            .signature-box {
                width: 100%;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 15px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>محظرة المنارة والرباط</h1>
            <p>إفادة تحديد المستوى عند خروج الطالب</p>
        </header>
        
        <div class="app-container">
            <div class="receipt" id="certificate">
                <div class="watermark">
                    <img src="bk.jpg" alt="Header Image" class="header-imag">
                </div>
                
                <div class="receipt-header">
                    <img src="header.jpg"
                         alt="Header Image" class="header-image">
                </div>
                
                <div class="title-container">
                    <div class="underlined-title">
                        <strong>إفادة تحديد المستوى عند الخروج</strong>
                    </div>
                </div>
                
                <div class="info-container">
                    <div><strong>إسم الطالب (ة):</strong> <span class="value"><?php echo $student_name ?></span></div>
                    <div><strong>القسم:</strong> <span class="value"><?php echo $class_name ?></span></div>
                    <div><strong>الفرع:</strong> <span class="value"><?php echo $branch_name ?></span></div>
                </div>
                
                <div class="info-container">
                    <div><strong>المستوى:</strong> <span class="value"><?php echo $level_name ?></span></div>
                    <div><strong>التاريخ:</strong> <span class="value"><?php echo date('Y-m-d'); ?></span></div>
                </div>
                
                <div class="info-container">
                    <div><strong>الحالة العامة للمحفوظات:</strong> <span class="value"><?php echo $status ?></span></div>
                </div>
                
                <div class="info-container">
                    <div><strong>الملحوظات:</strong> <span class="value"><?php echo $note ?></span></div>
                </div>
                
                <div class="reminders">
                    <div>يرجى تنزيل محفوظات الطالب في غضون 48 ساعة من خروجه المحظرة.</div>
                    <div>يلزم الوكيل بالمحافظة على المحفوظات حتى عودة الطالب للمحظرة ، ويتحمل الوكيل مسؤولية التفريط.</div>
                    <div>الرجاء من وكيل الطالب إحضار هذه الإفادة عند الرجوع إن قدر الله اللقاء والبقاء.</div>
                </div>
                
                <div class="signature-area">
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <div>توقيع وكيل الطالب.</div>
                    </div>
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <div>توقيع الإدارة.</div>
                    </div>
                </div>
                
                <div class="receipt-header">
                    <img src="footer.jpg"
                         alt="Header Image" class="header-image">
                </div>
            </div>
            
            <div class="action-buttons">
                <!-- <button class="btn btn-print" onclick="window.print()">
                    <i class="fas fa-print"></i> طباعة الإفادة
                </button> -->
                
                <button class="btn btn-whatsapp" onclick="shareViaWhatsApp()">
                    <i class="fab fa-whatsapp"></i> مشاركة عبر واتساب
                </button>
            </div>
        </div>
    </div>
    
    <script>
        function shareViaWhatsApp() {
            // Capture the certificate as an image
            html2canvas(document.getElementById('certificate')).then(canvas => {
                // Convert canvas to image data
                const imageData = canvas.toDataURL('image/png');
                
                // Create temporary link to download the image
                const link = document.createElement('a');
                link.href = imageData;
                link.download = 'إفادة_تحديد_المستوى_عند_الخروج.png';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                //  Open WhatsApp with the image
                setTimeout(() => {
                    const whatsappMessage = encodeURIComponent("إفادة تحديد المستوى عند الخروج للطالب");
                    const whatsappNumber = "<?php echo preg_replace('/[^0-9]/', '', $whatsapp_number); ?>"; // clean to keep only numbers
                    window.open(`https://wa.me/222${whatsappNumber}?text=${whatsappMessage}`);
                }, 1000);
            });
        }
        
        // Set current date for display
        // document.querySelector('.info-container .valu:nth-child(2)').textContent = new Date().toLocaleDateString('ar-EG');
    </script>
</body>
</html>