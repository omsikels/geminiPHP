<?php
require 'db.php';  // ✅ Connect to your MySQL database
require 'vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
if (!$apiKey) {
    die("\u274c ERROR: Missing API Key. Check your `.env` file.");
}

define('UPLOADS_DIR', 'uploads'); // ✅ Store images in "uploads" folder
if (!file_exists(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0777, true);
}

function save_uploaded_file($file) {
    $filePath = UPLOADS_DIR . "/" . time() . "_" . basename($file["name"]);
    move_uploaded_file($file["tmp_name"], $filePath);
    return $filePath;
}

function extract_online_banking_details($imagePath) {
    global $apiKey;

    $imageData = base64_encode(file_get_contents($imagePath));
    $prompt = "Extract online banking transaction details with high accuracy. 
    Return the response as a structured JSON object with the following exact fields:

    {
        'bankName': 'Full name of the bank',
        'date': 'Transaction date in YYYY-MM-DD format',
        'particulars': 'Transaction details (can be empty)',
        'amount': 'Exact numeric value of the transaction amount (e.g., 5312.14)',
        'referenceNumber': 'Unique reference number of the transaction'
    }

    Ensure:
    1 The date format is strictly YYYY-MM-DD (e.g., 2025-12-31).
    2 The amount is only numbers (e.g., 5312.14) and does not include currency symbols.
    3 If any field is missing, return an empty string instead of skipping it.";

    $postData = [
        "model" => "gemini-1.5-pro",
        "contents" => [
            [
                "role" => "user",
                "parts" => [
                    ["text" => $prompt],
                    ["inline_data" => ["mime_type" => "image/jpeg", "data" => $imageData]]
                ]
            ]
        ]
    ];

    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key=$apiKey");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_CAINFO, "C:\\xampp\\php\\extras\\ssl\\cacert.pem");

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        die("\u274c cURL Error: " . curl_error($ch));
    }

    curl_close($ch);
    $json = json_decode($response, true);

    $rawText = $json["candidates"][0]["content"]["parts"][0]["text"] ?? "{}";
    $cleanJson = trim(str_replace(["```json", "```"], "", $rawText));

    // ✅ Fix JSON format (Convert single quotes to double quotes)
    $cleanJson = preg_replace("/'([^']+)'/", '"$1"', $cleanJson);

    $parsedData = json_decode($cleanJson, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die("\u274c ERROR: Invalid JSON format. Raw Response:\n<pre>$cleanJson</pre>");
    }

    // 🔥 Fix Amount Formatting
    $parsedData['amount'] = preg_replace('/[^0-9.]/', '', $parsedData['amount'] ?? '');

    // 🔥 Convert Date to YYYY-MM-DD
    if (!empty($parsedData['date']) && preg_match('/(\d{2})[-\/](\d{2})[-\/](\d{4})/', $parsedData['date'], $matches)) {
        $day = $matches[1];
        $month = $matches[2];
        $year = $matches[3];

        // ✅ Ensure date is formatted correctly
        $parsedData['date'] = "$year-$month-$day";
    }

    return is_array($parsedData) ? $parsedData : [];
}

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    $filePath = save_uploaded_file($_FILES["file"]);
    
    $extractedData = extract_online_banking_details($filePath);

    if (!empty($extractedData)) {
        $stmt = $pdo->prepare("INSERT INTO onlinebanking (bank_name, date, particulars, amount, reference_number, image_path) 
                       VALUES (:bank_name, :date, :particulars, :amount, :reference_number, :image_path)");

        $stmt->execute([
            ':date' => $extractedData['date'] ?? null,
            ':bank_name' => $extractedData['bankName'] ?? null,
            ':particulars' => $extractedData['particulars'] ?? null,
            ':amount' => $extractedData['amount'] ?? null,
            ':reference_number' => $extractedData['referenceNumber'] ?? null,
            ':image_path' => $filePath
        ]);

        // ✅ Redirect back to online_banking_extractor.php after successful upload
        header("Location: online_banking_extractor.php?success=1");
        exit();
    }
}
?>
