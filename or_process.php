<?php
require 'db.php';  // Connect to MySQL database
require 'vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
if (!$apiKey) {
    die("❌ ERROR: Missing API Key. Check your `.env` file.");
}

define('UPLOADS_DIR', 'uploads'); 
if (!file_exists(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0777, true);
}

function save_uploaded_file($file) {
    $filePath = UPLOADS_DIR . "/" . time() . "_" . basename($file["name"]);
    move_uploaded_file($file["tmp_name"], $filePath);
    return $filePath;
}

function extract_or_details($imagePath) {
    global $apiKey;

    $imageData = base64_encode(file_get_contents($imagePath));
    $prompt = "Extract the sales invoice number (red-colored) and the total amount from this image. 
    Return the response as a structured JSON object with the following exact fields:
    {
        'salesInvoiceNumber': 'Red-colored invoice number',
        'amount': 'Total amount in numbers (e.g., 12345.67)'
    }
    Ensure the amount is numeric and does not include currency symbols.";

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
    $cleanJson = preg_replace("/'([^']+)'/", '"$1"', $cleanJson);

    $parsedData = json_decode($cleanJson, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die("❌ ERROR: Invalid JSON format. Raw Response:\n<pre>$cleanJson</pre>");
    }

    return is_array($parsedData) ? $parsedData : [];
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    $filePath = save_uploaded_file($_FILES["file"]);
    $extractedData = extract_or_details($filePath);

    if (!empty($extractedData)) {
        $stmt = $pdo->prepare("INSERT INTO or_data (sales_invoice, amount, image_path) 
                               VALUES (:sales_invoice_number, :amount, :image_path)");

        $stmt->execute([
            ':sales_invoice_number' => $extractedData['salesInvoiceNumber'] ?? null,
            ':amount' => $extractedData['amount'] ?? null,
            ':image_path' => $filePath
        ]);

        header("Location: or_extractor.php?success=1");
        exit();
    }
}
?>
