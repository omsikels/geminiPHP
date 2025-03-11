<?php 
require 'db.php'; 

$stmt = $pdo->query("SELECT * FROM creditCard ORDER BY created_at DESC LIMIT 1");
$receipt = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Receipt Processing</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Include Header -->
    <?php include 'header.php'; ?>

    <div class="container">
        <h1>Bank Receipt Processing</h1>

        <?php if (isset($_GET['success'])): ?>
            <p class="success-msg">✅ Receipt uploaded successfully!</p>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" action="creditprocess.php" onsubmit="showLoading()">
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

        <h2>Latest Receipt</h2>
        <?php if ($receipt): ?>
            <div class="receipt-card">
                <img src="<?= htmlspecialchars($receipt['image_path']) ?>" alt="Receipt Image">
                <div class="receipt-details">
                    <p><strong>Bank Terminal:</strong> <?= htmlspecialchars($receipt['bank_terminal']) ?></p>
                    <p><strong>Transaction Type:</strong> <?= htmlspecialchars($receipt['transaction_type']) ?></p>
                    <p><strong>Amount:</strong> ₱<?= htmlspecialchars($receipt['amount']) ?></p>
                </div>
            </div>
        <?php else: ?>
            <p class="no-receipt">No receipts uploaded yet.</p>
        <?php endif; ?>
    </div>

</body>
</html>
