<?php
require 'db.php';  // ✅ Connect to MySQL database
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

function extract_bank_check_details($imagePath) {
    global $apiKey;

    $imageData = base64_encode(file_get_contents($imagePath));
    $prompt = "Extract bank check details from this image with high accuracy. 
    Return as a structured JSON object with the following exact fields:

    {
        \"bank_name\": \"Full name of the bank\",
        \"date\": \"Transaction date in YYYY-MM-DD format\",
        \"particulars\": \"Transaction description (if available)\",
        \"amount\": \"Exact numeric value of the transaction amount (e.g., 5312.14)\",
        \"reference_number\": \"Unique check reference number (typically beside the word 'DEPOSIT')\"
    }

    Ensure:
    1. The date format is strictly YYYY-MM-DD.
    2. The amount should contain only numbers (no currency symbols).
    3. If a field is missing, return an empty string instead of skipping it.";
    
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

    if (!isset($json["candidates"][0]["content"]["parts"][0]["text"])) {
        die("❌ ERROR: Unexpected API response format: " . json_encode($json, JSON_PRETTY_PRINT));
    }

    $rawText = $json["candidates"][0]["content"]["parts"][0]["text"] ?? "";
    $cleanJson = trim(str_replace(["```json", "```"], "", $rawText));

    if (empty($cleanJson)) {
        die("❌ ERROR: Empty response from API. Please check the request payload or API response.");
    }

    // ✅ Fix JSON format (Convert single quotes to double quotes)
    $cleanJson = preg_replace("/'([^']+)'/", '"$1"', $cleanJson);

    $parsedData = json_decode($cleanJson, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die("❌ ERROR: Invalid JSON format. Raw Response:\n<pre>" . htmlspecialchars($cleanJson) . "</pre>");
    }

    // 🔥 Fix Amount Formatting
    $parsedData['amount'] = preg_replace('/[^0-9.]/', '', $parsedData['amount'] ?? '');

    // 🔥 Convert Date to YYYY-MM-DD
    if (!empty($parsedData['date']) && preg_match('/(\d{2})[-\/] (\d{2})[-\/] (\d{4})/', $parsedData['date'], $matches)) {
        $parsedData['date'] = "$matches[3]-$matches[2]-$matches[1]";
    }

    // 🔥 Extract Reference Number (if next to "DEPOSIT")
    if (isset($parsedData['reference_number']) && preg_match('/DEPOSIT\s+(\S+)/', $parsedData['reference_number'], $matches)) {
        $parsedData['reference_number'] = $matches[1];
    }

    return is_array($parsedData) ? $parsedData : [];
}

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    $filePath = save_uploaded_file($_FILES["file"]);
    
    $extractedData = extract_bank_check_details($filePath);

    if (!empty($extractedData)) {
        $stmt = $pdo->prepare("INSERT INTO bankcheck (bank_name, date, particulars, amount, reference_number, image_path) 
                               VALUES (:bank_name, :date, :particulars, :amount, :reference_number, :image_path)");

        $stmt->execute([
            ':bank_name' => $extractedData['bank_name'] ?? null,
            ':date' => $extractedData['date'] ?? null,
            ':particulars' => $extractedData['particulars'] ?? null,
            ':amount' => $extractedData['amount'] ?? null,
            ':reference_number' => $extractedData['reference_number'] ?? null,
            ':image_path' => $filePath
        ]);

        header("Location: bank_check_extractor.php?success=1");
        exit();
    }
}
?>