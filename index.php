<?php 
require 'db.php'; 

$stmt = $pdo->query("SELECT * FROM cheques ORDER BY created_at DESC LIMIT 1");
$cheque = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cheque Processing</title>
    
    <!-- ✅ Google Font: Inter (Modern, Easy to Read) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    
    <!-- ✅ Link to External CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!--  Include Header -->
    <?php include 'header.php'; ?>

    <div class="container">
        <h1>Cheque Processing</h1>

        <?php if (isset($_GET['success'])): ?>
            <p class="success-msg">✅ Cheque uploaded successfully!</p>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" action="process.php" onsubmit="showLoading()">
    <label class="custom-file-label">
        <input type="file" name="file" class="custom-file-input" accept=".pdf,.jpg,.jpeg,.png" required onchange="updateFileName(this)">
        <span>Select a File</span>
    </label>
    <span id="file-name"></span>
    <button type="submit">Upload</button>
</form>

<!-- 🔵 Loading Animation (Initially Hidden) -->
<div id="loading" class="loading-container">
    <div class="loading-spinner"></div>
    <p>Processing... Please wait.</p>
</div>

<script>
function updateFileName(input) {
    document.getElementById('file-name').textContent = input.files[0] ? "📂 " + input.files[0].name : "";
}

// 🔄 Show Loading Animation When Upload Starts
function showLoading() {
    document.getElementById('loading').style.display = "flex";
}
</script>


        <h2>Latest Cheque</h2>
        <?php if ($cheque && isset($cheque['cheque_number'])): ?>
            <div class="cheque-card">
                <img src="<?= htmlspecialchars($cheque['image_path']) ?>" alt="Cheque Image">
                <div class="cheque-details">
                    <p><strong>Date:</strong> <?= htmlspecialchars($cheque['date']) ?></p>
                    <p><strong>Bank:</strong> <?= htmlspecialchars($cheque['bank_name']) ?></p>
                    <p><strong>Amount:</strong> ₱<?= htmlspecialchars($cheque['amount']) ?></p>
                    <p><strong>Cheque No.:</strong> <?= htmlspecialchars($cheque['cheque_number']) ?></p>
                    <p><strong>BRSTN Code:</strong> <?= htmlspecialchars($cheque['brstn_code']) ?></p>
                </div>
            </div>
        <?php else: ?>
            <p class="no-cheque">No cheques uploaded yet.</p>
        <?php endif; ?>
    </div>

    <script>
        function updateFileName(input) {
            document.getElementById('file-name').textContent = input.files[0] ? "📂 " + input.files[0].name : "";
        }
    </script>
</body>
</html>
