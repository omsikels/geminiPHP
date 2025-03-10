<?php
require 'db.php';  // ✅ Connect to your MySQL database
require 'vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
if (!$apiKey) {
    die("❌ ERROR: Missing API Key. Check your `.env` file.");
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

function extract_cheque_details($imagePath) {
    global $apiKey;

    $imageData = base64_encode(file_get_contents($imagePath));
    $prompt = "Extract cheque details from this cheque image with high accuracy. 
    Return the response as a structured JSON object with the following exact fields:

    {
        'accountName': 'Full name of the account holder',
        'date': 'Cheque issuance date in YYYY-MM-DD format',
        'chequeNumber': 'Unique cheque number',
        'accountNumber': 'Complete bank account number',
        'bankName': 'Full name of the bank',
        'amountInNumbers': 'Exact numeric value of the cheque amount (e.g., 5312.14)',
        'brstn': 'Bank Routing Symbol Transit Number (BRSTN)'
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
        die("❌ cURL Error: " . curl_error($ch));
    }

    curl_close($ch);
    $json = json_decode($response, true);

    $rawText = $json["candidates"][0]["content"]["parts"][0]["text"] ?? "{}";
    $cleanJson = trim(str_replace(["```json", "```"], "", $rawText));

    // ✅ Fix JSON format (Convert single quotes to double quotes)
    $cleanJson = preg_replace("/'([^']+)'/", '"$1"', $cleanJson);

    $parsedData = json_decode($cleanJson, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die("❌ ERROR: Invalid JSON format. Raw Response:\n<pre>$cleanJson</pre>");
    }

    // 🔥 Fix Amount Formatting
    $parsedData['amountInNumbers'] = preg_replace('/[^0-9.]/', '', $parsedData['amountInNumbers'] ?? '');

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
    
    $extractedData = extract_cheque_details($filePath);


    if (!empty($extractedData)) {
        $stmt = $pdo->prepare("INSERT INTO cheques (date, cheque_number, bank_name, amount, brstn_code, image_path) 
                               VALUES (:date, :cheque_number, :bank_name, :amount, :brstn_code, :image_path)");

        $stmt->execute([
            ':date' => ($extractedData['date']) ?? null, // ✅ Prevent NULL error
            ':cheque_number' => $extractedData['chequeNumber'] ?? null,
            ':bank_name' => $extractedData['bankName'] ?? null,
            ':amount' => $extractedData['amountInNumbers'] ?? null,
            ':brstn_code' => $extractedData['brstn'] ?? null,
            ':image_path' => $filePath
        ]);

        // ✅ Redirect back to index.php after successful upload
        header("Location: index.php?success=1");
        exit();
    }
}
?>
