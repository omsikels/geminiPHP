<?php
require 'db.php';  // ✅ Connect to MySQL
require 'vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
if (!$apiKey) {
    die("❌ ERROR: Missing API Key. Check `.env` file.");
}

define('UPLOADS_DIR', 'uploads');
if (!file_exists(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0777, true);
}

// ✅ Save uploaded file
function save_uploaded_file($file) {
    $filePath = UPLOADS_DIR . "/" . time() . "_" . basename($file["name"]);
    move_uploaded_file($file["tmp_name"], $filePath);
    return $filePath;
}

// ✅ Extract details from Bank Receipt
function extract_receipt_details($imagePath) {
    global $apiKey;

    $imageData = base64_encode(file_get_contents($imagePath));
    $prompt = "Extract bank receipt details with high accuracy. 
    Return a JSON object with:
    {
        'bank_terminal': 'Bank terminal name',
        'transaction_type': 'Transaction type (e.g., Purchase, Refund)',
        'amount': 'Transaction amount (e.g., 1234.56)'
    }
    Ensure:
    - Amount is strictly numeric (e.g., 1234.56)
    - No currency symbols
    - Missing values should be empty strings.";

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

// 🔥 Convert single quotes to double quotes for valid JSON
$cleanJson = preg_replace("/'([^']+)'/", '"$1"', $cleanJson);

$parsedData = json_decode($cleanJson, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("❌ ERROR: Invalid JSON format. Raw Response:\n<pre>$cleanJson</pre>");
}


    // ✅ Ensure amount is correctly formatted
    $parsedData['amount'] = preg_replace('/[^0-9.]/', '', $parsedData['amount'] ?? '');

    return is_array($parsedData) ? $parsedData : [];
}

// ✅ Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    $filePath = save_uploaded_file($_FILES["file"]);
    $extractedData = extract_receipt_details($filePath);

    if (!empty($extractedData)) {
        $stmt = $pdo->prepare("INSERT INTO creditCard (bank_terminal, transaction_type, amount, image_path) 
                               VALUES (:bank_terminal, :transaction_type, :amount, :image_path)");

        $stmt->execute([
            ':bank_terminal' => $extractedData['bank_terminal'] ?? null,
            ':transaction_type' => $extractedData['transaction_type'] ?? null,
            ':amount' => $extractedData['amount'] ?? null,
            ':image_path' => $filePath
        ]);

        //Redirect to `credit_extractor.php` after successful upload
        header("Location: credit_extractor.php?success=1");
        exit();
    }
}
?>
