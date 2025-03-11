<?php 
require 'db.php'; 

$stmt = $pdo->query("SELECT * FROM bankcheck ORDER BY created_at DESC LIMIT 1");
$check = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Check Processing</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Include Header -->
    <?php include 'header.php'; ?>

    <div class="container">
        <h1>Bank Check Processing</h1>

        <?php if (isset($_GET['success'])): ?>
            <p class="success-msg">✅ Check uploaded successfully!</p>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" action="bank_check_process.php" onsubmit="showLoading()">
            <label class="custom-file-label">
                <input type="file" name="file" class="custom-file-input" accept=".jpg,.jpeg,.png" required onchange="updateFileName(this)">
                <span>Select a File</span>
            </label>
            <span id="file-name"></span>
            <button type="submit">Upload</button>
        </form>

        <div id="loading" class="loading-container">
            <div class="loading-spinner"></div>
            <p>Processing... Please wait.</p>
        </div>

        <script>
        function updateFileName(input) {
            document.getElementById('file-name').textContent = input.files[0] ? "📂 " + input.files[0].name : "";
        }
        function showLoading() {
            document.getElementById('loading').style.display = "flex";
        }
        </script>

        <h2>Latest Bank Check</h2>
        <?php if ($check): ?>
            <div class="check-card">
                <img src="<?= htmlspecialchars($check['image_path']) ?>" alt="Check Image">
                <div class="check-details">
                    <p><strong>Bank Name:</strong> <?= htmlspecialchars($check['bank_name']) ?></p>
                    <p><strong>Date:</strong> <?= htmlspecialchars($check['date']) ?></p>
                    <p><strong>Particulars:</strong> <?= htmlspecialchars($check['particulars'] ?? 'N/A') ?></p>
                    <p><strong>Amount:</strong> ₱<?= htmlspecialchars($check['amount']) ?></p>
                    <p><strong>Reference Number:</strong> <?= htmlspecialchars($check['reference_number']) ?></p>
                </div>
            </div>
        <?php else: ?>
            <p class="no-check">No bank checks uploaded yet.</p>
        <?php endif; ?>
    </div>

</body>
</html>
