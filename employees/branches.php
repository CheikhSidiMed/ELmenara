<?php
// Sample data for branches and sections
$branches = [
    ['id' => 1, 'name' => 'Branch A', 'subscriptions' => 150, 'sections' => [
        ['name' => 'Section A1'],
        ['name' => 'Section A2'],
    ]],
    ['id' => 2, 'name' => 'Branch B', 'subscriptions' => 200, 'sections' => [
        ['name' => 'Section B1'],
        ['name' => 'Section B2'],
    ]],
    ['id' => 3, 'name' => 'Branch C', 'subscriptions' => 120, 'sections' => [
        ['name' => 'Section C1'],
        ['name' => 'Section C2'],
        ['name' => 'Section C3'],
    ]],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branches & Sections</title>
    <!-- Bootstrap CSS -->
    <link href="css/bootstrap-4-5-2.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card-header {
            background-color: #007bff;
            color: white;
        }
        .card-title {
            margin: 0;
        }
        .badge {
            float: right;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">Branches and Sections</h1>
        <?php foreach ($branches as $branch): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title"><?php echo htmlspecialchars($branch['name']); ?></h5>
                    <span class="badge bg-primary"><?php echo htmlspecialchars($branch['subscriptions']); ?> Subscriptions</span>
                </div>
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Sections:</h6>
                    <ul class="list-group">
                        <?php foreach ($branch['sections'] as $section): ?>
                            <li class="list-group-item"><?php echo htmlspecialchars($section['name']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- Bootstrap JS and dependencies -->
    <script src="js/jquery-3.5.1.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>
