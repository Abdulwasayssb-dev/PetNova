
<?php
session_start();

require_once "C:/xampp/gemini_config.php";

$message = "";
$aiResult = "";
$symptoms = "";
$petType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $petType = trim($_POST["pet_type"] ?? "");
    $symptoms = trim($_POST["symptoms"] ?? "");

    $allowedPets = ["Dog", "Cat", "Bird", "Other"];

    if (!in_array($petType, $allowedPets, true)) {
        $message = "Please select a valid pet type.";
    } elseif ($symptoms === "" || strlen($symptoms) > 2000) {
        $message = "Please describe the symptoms (maximum 2000 characters).";
    } elseif (!isset($_FILES["pet_photo"]) ||
              $_FILES["pet_photo"]["error"] !== UPLOAD_ERR_OK) {
        $message = "Please upload a pet photo.";
    } elseif ($_FILES["pet_photo"]["size"] > 5 * 1024 * 1024) {
        $message = "Image size must be 5 MB or less.";
    } else {

        $tmpFile = $_FILES["pet_photo"]["tmp_name"];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpFile);

        $allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        if (!in_array($mimeType, $allowedTypes, true)) {
            $message = "Only JPG, PNG, and WebP images are allowed.";
        } elseif (!extension_loaded("curl")) {
            $message = "PHP cURL is not enabled. Enable the curl extension in XAMPP.";
        } else {

            $imageData = base64_encode(file_get_contents($tmpFile));

            $prompt = "
You are PetNova's preliminary pet-care information assistant.

Pet type: $petType

Owner's symptom description:
$symptoms

Review the supplied pet image and symptom description.

Provide:
1. Visible observations from the image only.
2. Possible general explanations, clearly labelled as possibilities.
3. Safe next steps for the pet owner.
4. Whether veterinary attention is recommended.

Important safety rules:
- Never claim to diagnose a disease from an image.
- Do not claim certainty about an illness or injury.
- Do not recommend human medicines or medication doses.
- If there are signs of severe pain, breathing difficulty,
  heavy bleeding, collapse, suspected poisoning, or another
  emergency, advise immediate veterinary attention.
- If the image is unclear, say that you cannot assess it.
- Be careful, simple, and understandable.
";

            $payload = [
                "contents" => [
                    [
                        "parts" => [
                            [
                                "text" => $prompt
                            ],
                            [
                                "inline_data" => [
                                    "mime_type" => $mimeType,
                                    "data" => $imageData
                                ]
                            ]
                        ]
                    ]
                ],
                "generationConfig" => [
                    "temperature" => 0.3,
                    "maxOutputTokens" => 700
                ]
            ];

            $model = "gemini-3.8-flash";

            $apiUrl =
                "https://generativelanguage.googleapis.com/v1beta/models/"
                . $model . ":generateContent?key="
                . urlencode(GEMINI_API_KEY);

            $ch = curl_init($apiUrl);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json"
                ],
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_TIMEOUT => 60
            ]);

          
$apiResponse = false;
$curlError = "";
$httpCode = 0;

$maxAttempts = 3;

for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {

    $apiResponse = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($apiResponse !== false && $httpCode !== 503) {
        break;
    }

    if ($attempt < $maxAttempts) {
        sleep($attempt * 2);
    }
}

curl_close($ch);


            if ($apiResponse === false) {
                $message = "Connection error: " . $curlError;
            } else {

                $result = json_decode($apiResponse, true);

                
if ($httpCode !== 200) {

    error_log("Gemini API error: " . $apiResponse);

    $errorData = json_decode($apiResponse, true);

    $errorMessage = $errorData["error"]["message"]
        ?? "Unknown API error";

    $message = "Gemini API Error (HTTP "
        . $httpCode . "): " . $errorMessage;
} elseif (
                    isset($result["candidates"][0]["content"]["parts"])
                ) {

                    $parts = $result["candidates"][0]["content"]["parts"];

                    foreach ($parts as $part) {
                        if (isset($part["text"])) {
                            $aiResult .= $part["text"] . "\n";
                        }
                    }

                    if ($aiResult === "") {
                        $message = "The AI returned no text. Please try again.";
                    }

                } else {
                    $message = "No usable AI response was received.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>PetNova | AI Pet Health Helper</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<main class="section">

    <h1>PetNova AI Pet Health Helper</h1>

    <p>
        Upload a pet photo and describe its symptoms
        to receive preliminary AI-assisted guidance.
    </p>

    <?php if ($message !== ""): ?>
        <div class="feature-form" role="alert">
            <p>
                <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            </p>
        </div>
    <?php endif; ?>

    <form
        class="feature-form"
        method="POST"
        action="pet_health.php"
        enctype="multipart/form-data"
    >

        <label for="pet_type">Pet Type</label>

        <select id="pet_type" name="pet_type" required>
            <option value="">Select pet type</option>

            <?php foreach (["Dog", "Cat", "Bird", "Other"] as $pet): ?>
                <option
                    value="<?= $pet ?>"
                    <?= $petType === $pet ? "selected" : "" ?>
                >
                    <?= $pet ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="symptoms">Describe Your Pet's Symptoms</label>

        <textarea
            id="symptoms"
            name="symptoms"
            rows="5"
            maxlength="2000"
            placeholder="Describe what you have noticed..."
            required
        ><?= htmlspecialchars($symptoms, ENT_QUOTES, "UTF-8") ?></textarea>

        <label for="pet_photo">Upload Pet Photo</label>

        <input
            type="file"
            id="pet_photo"
            name="pet_photo"
            accept=".jpg,.jpeg,.png,.webp"
            required
        >

        <p>Accepted formats: JPG, PNG, WebP. Maximum size: 5 MB.</p>

        <img
            id="photoPreview"
            alt="Selected pet photo preview"
            style="display:none; max-width:250px; margin-top:15px;"
        >

        <button type="submit">Analyse with AI</button>

    </form>

    <?php if ($aiResult !== ""): ?>

        <section class="feature-form">
            <h2>AI-Assisted Observations</h2>

            <p style="white-space: pre-wrap;"><?=
                htmlspecialchars($aiResult, ENT_QUOTES, "UTF-8")
            ?></p>
        </section>

    <?php endif; ?>

    <p>
        <strong>Important:</strong>
        This feature provides preliminary information, not a
        veterinary diagnosis. AI may make mistakes. For serious
        symptoms or emergencies, contact a veterinarian immediately.
    </p>

    <p><a href="index.html">Back to Home</a></p>

</main>

<script>
document.getElementById("pet_photo").addEventListener("change", function () {

    const preview = document.getElementById("photoPreview");

    if (this.files && this.files[0]) {
        preview.src = URL.createObjectURL(this.files[0]);
        preview.style.display = "block";
    } else {
        preview.style.display = "none";
        preview.removeAttribute("src");
    }
});
</script>

</body>
</html>
