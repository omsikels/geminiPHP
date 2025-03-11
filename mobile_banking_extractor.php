<?php 
require 'db.php'; 

$stmt = $pdo->query("SELECT * FROM mobilebanking ORDER BY created_at DESC LIMIT 1");
$receipt = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Banking Extractor</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Include Header -->
    <?php include 'header.php'; ?>

    <div class="container">
        <h1>Mobile Banking Extraction</h1>

        <?php if (isset($_GET['success'])): ?>
            <p class="success-msg">✅ Receipt uploaded successfully!</p>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" action="mobile_banking_process.php" onsubmit="showLoading()">
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

        <h2>Latest Mobile Banking Transaction</h2>
        <?php if ($receipt): ?>
            <div class="receipt-card">
                <img src="<?= htmlspecialchars($receipt['image_path']) ?>" alt="Receipt Image">
                <div class="receipt-details">
                    <p><strong>Bank Name:</strong> <?= htmlspecialchars($receipt['bank_name']) ?></p>
                    <p><strong>Date:</strong> <?= htmlspecialchars($receipt['date']) ?></p>
                    <p><strong>Particulars:</strong> <?= htmlspecialchars($receipt['particulars'] ?? 'N/A') ?></p>
                    <p><strong>Amount:</strong> ₱<?= htmlspecialchars($receipt['amount']) ?></p>
                    <p><strong>Confirmation Number:</strong> <?= htmlspecialchars($receipt['confirmation_number']) ?></p>
                </div>
            </div>
        <?php else: ?>
            <p class="no-receipt">No mobile banking transactions uploaded yet.</p>
        <?php endif; ?>
    </div>

</body>
</html>
