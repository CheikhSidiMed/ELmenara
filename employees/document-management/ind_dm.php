<?php
include '../db_connection.php';
$documents = $conn->query("SELECT * FROM documents ORDER BY uploaded_at DESC");

$successUpload = isset($_GET['success']) && $_GET['success'] === '1';
$successDelete = isset($_GET['deleted']) && $_GET['deleted'] === '1';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>إدارة الوثائق</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body { font-family: 'Cairo', sans-serif; }
    .swal2-container { z-index: 2000; }
    .search-box {
      margin-bottom: 20px;
    }
  </style>
</head>
<body class="bg-light">
  <div class="container mt-5">
    <div class="card shadow rounded">
      <div class="card-body">
        <h3 class="mb-4 text-primary text-center">📁 نظام إدارة الوثائق</h3>

        <form action="upload.php" method="POST" enctype="multipart/form-data" class="row g-3 mb-4">
          <div class="col-md-6">
            <input type="text" name="title" class="form-control" placeholder="عنوان الوثيقة" required>
          </div>
          <div class="col-md-4">
            <input type="file" name="document" class="form-control" required>
          </div>
          <div class="col-md-2 d-grid">
            <button class="btn btn-success" type="submit">تحميل</button>
          </div>
        </form>

        <input type="text" id="searchInput" class="form-control search-box" placeholder="🔎 ابحث عن وثيقة...">

        <table class="table table-striped table-hover text-center" id="documentsTable">
          <thead class="table-primary">
            <tr>
              <th>عنوان الوثيقة</th>
              <th>رابط الملف</th>
              <th>تاريخ التحميل</th>
              <th>الإجراء</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = $documents->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><a href="uploads/<?= $row['filename'] ?>" target="_blank">عرض</a></td>
                <td><?= $row['uploaded_at'] ?></td>
                <td>
                  <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $row['id'] ?>)">🗑️ حذف</button>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>

        <form id="deleteForm" method="GET" action="delete.php" style="display: none;">
          <input type="hidden" name="id" id="deleteId">
        </form>
      </div>
    </div>
  </div>

<?php if ($successUpload): ?>
<script>
  Swal.fire({
    icon: 'success',
    title: 'تم التحميل بنجاح',
    text: 'تمت إضافة الوثيقة إلى النظام.',
    confirmButtonText: 'حسناً'
  });
</script>
<?php endif; ?>

<?php if ($successDelete): ?>
<script>
  Swal.fire({
    icon: 'success',
    title: 'تم الحذف بنجاح',
    text: 'تم حذف الوثيقة بنجاح.',
    confirmButtonText: 'موافق'
  });
</script>
<?php endif; ?>

<script>
// 🔍 Filtrage par recherche
document.getElementById("searchInput").addEventListener("keyup", function() {
  const search = this.value.toLowerCase();
  const rows = document.querySelectorAll("#documentsTable tbody tr");

  rows.forEach(row => {
    const title = row.querySelector("td:first-child").textContent.toLowerCase();
    row.style.display = title.includes(search) ? "" : "none";
  });
});

// 🗑️ Confirmation suppression
function confirmDelete(id) {
  Swal.fire({
    title: 'هل أنت متأكد؟',
    text: "لن تتمكن من استعادة هذه الوثيقة!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'نعم، احذفها',
    cancelButtonText: 'إلغاء'
  }).then((result) => {
    if (result.isConfirmed) {
      document.getElementById("deleteId").value = id;
      document.getElementById("deleteForm").submit();
    }
  });
}
</script>
</body>
</html>
