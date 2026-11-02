
<?php
session_start();

$message = "";
$petType = "";
$symptoms = "";
$photoPath = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $petType = trim($_POST["pet_type"] ?? "");
    $symptoms = trim($_POST["symptoms"] ?? "");

    $allowedPets = ["Dog", "Cat", "Bird", "Other"];

    if (!in_array($petType, $allowedPets, true) || $symptoms === "") {
        $message = "Please select a pet type and describe the symptoms.";
    } else {
        // Preliminary guidance only; this is not an AI diagnosis.
        
$text = strtolower($symptoms);

$rules = [
    [
        "keywords" => [
            "difficulty breathing", "cannot breathe",
            "heavy bleeding", "seizure", "unconscious",
            "poison", "poisoned", "unable to stand",
            "collapsed", "collapse"
        ],
        "message" => "URGENT: These signs may indicate a serious emergency. Contact a veterinarian or emergency veterinary clinic immediately. Do not wait for online guidance or give human medicines."
    ],
    [
        "keywords" => [
            "vomiting", "vomit", "throwing up"
        ],
        "message" => "Vomiting: Make a note of how often it occurs and whether your pet can keep water down. Do not force food or give human medication. Repeated vomiting, blood, weakness, or inability to keep water down needs prompt veterinary attention."
    ],
    [
        "keywords" => [
            "diarrhea", "loose stool", "loose motion"
        ],
        "message" => "Digestive concern: Monitor your pet's stool and hydration. Keep clean water available. Contact a veterinarian if diarrhea continues, or sooner if there is blood, repeated vomiting, weakness, or your pet is very young."
    ],
    [
        "keywords" => [
            "itching", "scratching", "itchy", "fleas"
        ],
        "message" => "Skin irritation: Check gently for redness, parasites, or irritated areas without scratching the skin. Avoid applying human creams or unapproved products. Contact a veterinarian if the itching is severe, persistent, or the skin is damaged."
    ],
    [
        "keywords" => [
            "not eating", "loss of appetite", "refusing food",
            "no appetite"
        ],
        "message" => "Reduced appetite: Note when your pet last ate and whether it is drinking and behaving normally. Do not force-feed. Contact a veterinarian if your pet refuses food, especially if it is a young animal, or if other symptoms are present."
    ],
    [
        "keywords" => [
            "limping", "limp", "paw pain", "leg pain"
        ],
        "message" => "Possible limb discomfort: Restrict running and jumping, and allow your pet to rest safely. Do not manipulate the affected leg or give human painkillers. Severe pain, swelling, inability to bear weight, or worsening symptoms require prompt veterinary care."
    ],
    [
        "keywords" => [
            "wound", "cut", "injury", "injured"
        ],
        "message" => "Possible injury: Keep your pet calm and avoid touching the injured area unnecessarily. Do not apply unapproved chemicals or medicines. Deep wounds, severe pain, or bleeding require urgent veterinary attention."
    ],
    [
        "keywords" => [
            "cough", "coughing", "sneezing", "runny nose"
        ],
        "message" => "Respiratory symptoms: Keep your pet comfortable and note when the symptoms started. Arrange veterinary advice if symptoms persist or worsen. Difficulty breathing is an emergency."
    ],
    [
        "keywords" => [
            "eye redness", "red eye", "eye discharge",
            "watery eyes", "swollen eye"
        ],
        "message" => "Eye concern: Prevent rubbing if possible and do not use human eye drops. Eye pain, cloudiness, swelling, or persistent discharge should be assessed promptly by a veterinarian."
    ],
    [
        "keywords" => [
            "not drinking", "refusing water",
            "dehydrated", "dehydration"
        ],
        "message" => "Possible hydration concern: Keep fresh water available, but do not force your pet to drink. Contact a veterinarian promptly, particularly if your pet is weak, vomiting, or not urinating normally."
    ]
];

$message = "";
$matched = false;

foreach ($rules as $rule) {
    foreach ($rule["keywords"] as $keyword) {
        if (strpos($text, $keyword) !== false) {
            $message = $rule["message"];
            $matched = true;
            break 2;
        }
    }
}

if (!$matched) {
    $message = "We could not match these symptoms to a specific guidance category. Please describe what you observed, when it started, and any changes in your pet's behaviour. This tool cannot diagnose illness. Contact a veterinarian if you are concerned, and seek urgent care for severe symptoms.";
}
        }
    }  


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetNova | Pet Health Helper</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="section">
        <h1>Pet Health Helper</h1>
        <p>
            Describe your pet's symptoms to receive preliminary guidance.
            This tool does not provide a diagnosis.
        </p>

        <?php if ($message !== ""): ?>
            <div class="feature-form" role="status">
                <p>
                    <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
                </p>
            </div>
        <?php endif; ?>

        <form class="feature-form" method="POST" action="pet_health.php">
            <label for="pet_type">Pet Type</label>
            <select id="pet_type" name="pet_type" required>
                <option value="">Select pet type</option>
                <?php foreach (["Dog", "Cat", "Bird", "Other"] as $pet): ?>
                    <option value="<?= $pet ?>"
                        <?= $petType === $pet ? "selected" : "" ?>>
                        <?= $pet ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="symptoms">Describe Symptoms</label>
            <textarea
                id="symptoms"
                name="symptoms"
                rows="5"
                maxlength="2000"
                placeholder="Describe what you have noticed..."
                required
            ><?= htmlspecialchars($symptoms, ENT_QUOTES, "UTF-8") ?></textarea>

            <button type="submit">Get Basic Guidance</button>
        </form>

        <p>
            <strong>Important:</strong> Difficulty breathing, heavy bleeding,
            seizures, suspected poisoning, or collapse require urgent
            veterinary attention. Do not wait for an online response.
        </p>

        <p><a href="index.html">Back to Home</a></p>
    </main>
</body>
</html>
