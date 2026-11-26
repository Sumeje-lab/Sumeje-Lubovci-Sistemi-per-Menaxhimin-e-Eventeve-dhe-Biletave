
<?php
session_start();
require_once "db.php";

date_default_timezone_set("Europe/Belgrade");

// Vetëm administratori mund të krijojë evente.
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION["user_role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Nuk keni leje për të krijuar evente. Kjo mundësi është vetëm për administratorin.");
}

// Temat e lejuara
$allowedThemes = [
    "pastel-floral",
    "dark",
    "ocean-blue",
    "sage-green"
];

$currentTheme = $_SESSION["theme"] ?? "pastel-floral";

if (!in_array($currentTheme, $allowedThemes, true)) {
    $currentTheme = "pastel-floral";
}

// Funksioni për sigurinë e HTML
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// Token CSRF për formularin e temës
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Ikona SVG e lules
function flowerIcon($class = "") {
    return '
    <svg class="' . h($class) . '" viewBox="0 0 100 100"
         xmlns="http://www.w3.org/2000/svg"
         aria-hidden="true">
        <g fill="none" stroke="currentColor"
           stroke-width="3" stroke-linecap="round"
           stroke-linejoin="round">
            <ellipse cx="50" cy="25" rx="10" ry="22"/>
            <ellipse cx="50" cy="75" rx="10" ry="22"/>
            <ellipse cx="25" cy="50" rx="22" ry="10"/>
            <ellipse cx="75" cy="50" rx="22" ry="10"/>
            <ellipse cx="32" cy="32" rx="10" ry="22"
                     transform="rotate(-45 32 32)"/>
            <ellipse cx="68" cy="32" rx="10" ry="22"
                     transform="rotate(45 68 32)"/>
            <ellipse cx="32" cy="68" rx="10" ry="22"
                     transform="rotate(45 32 68)"/>
            <ellipse cx="68" cy="68" rx="10" ry="22"
                     transform="rotate(-45 68 68)"/>
            <circle cx="50" cy="50" r="7" fill="currentColor"/>
        </g>
    </svg>';
}

$message = "";
$messageType = "error";

$title = "";
$category = "";
$description = "";
$location = "";
$eventDate = "";
$price = "";
$totalTickets = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $eventDate = trim($_POST["event_date"] ?? "");
    $priceInput = trim($_POST["price"] ?? "");
    $ticketsInput = trim($_POST["total_tickets"] ?? "");

    $price = $priceInput;
    $totalTickets = $ticketsInput;

    $validDate = false;
    $dateObject = false;

    if ($eventDate !== "") {
        $dateObject = DateTime::createFromFormat(
            "Y-m-d\TH:i",
            $eventDate
        );

        $validDate = $dateObject
            && $dateObject->format("Y-m-d\TH:i") === $eventDate;
    }

    if (
        $title === "" ||
        $category === "" ||
        $location === "" ||
        !$validDate ||
        $priceInput === "" ||
        $ticketsInput === ""
    ) {
        $message = "Ju lutem plotësoni të gjitha fushat e detyrueshme.";

    } elseif ($dateObject <= new DateTime()) {
        $message = "Gabim! Nuk mund të krijoni një event me datë dhe orë të kaluar.";

    } elseif (!is_numeric($priceInput) || (float)$priceInput < 0) {
        $message = "Çmimi duhet të jetë numër pozitiv ose zero.";

    } elseif (
        !ctype_digit($ticketsInput) ||
        (int)$ticketsInput < 1
    ) {
        $message = "Numri i biletave duhet të jetë së paku 1.";

    } else {
        try {
            $adminId = (int)$_SESSION["user_id"];

            $stmt = $pdo->prepare(
                "SELECT id FROM users WHERE id = ? AND role = 'admin'"
            );
            $stmt->execute([$adminId]);

            if (!$stmt->fetch()) {
                throw new Exception(
                    "Llogaria nuk ka leje administratori."
                );
            }

            $formattedDate = str_replace("T", " ", $eventDate);
            $priceValue = round((float)$priceInput, 2);
            $ticketCount = (int)$ticketsInput;

            $stmt = $pdo->prepare(
                "INSERT INTO events
                (
                    title,
                    description,
                    category,
                    location,
                    event_date,
                    price,
                    total_tickets,
                    available_tickets,
                    created_by
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $title,
                $description,
                $category,
                $location,
                $formattedDate,
                $priceValue,
                $ticketCount,
                $ticketCount,
                $adminId
            ]);

            header("Location: index.php?event_created=1");
            exit;

        } catch (PDOException $e) {
            $message = "Gabim gjatë ruajtjes së eventit. Kontrolloni të dhënat e databazës.";

        } catch (Exception $e) {
            $message = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Krijo event të ri</title>
    <link rel="stylesheet" href="style.css?v=3">
</head>

<body class="auth-page create-event-page" data-theme="<?= h($currentTheme) ?>">

<header class="site-header">
    <div class="header-content">

        <a href="index.php" class="brand">
            <?= flowerIcon("brand-flower") ?>
            <span>evente<span class="brand-amp">&</span>bileta</span>
        </a>

        <nav>
            <a href="index.php">Kryefaqja</a>

            <a href="expired_events.php">Eventet e skaduara</a>

            <a href="create_event.php" class="active">Krijo event</a>

            <a href="reservations.php">Rezervimet e mia</a>

            <a href="logout.php">Dil nga llogaria</a>

            <form action="change_theme.php" method="POST" class="theme-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= h($_SESSION["csrf_token"]) ?>"
                >

                <select
                    name="theme"
                    onchange="this.form.submit()"
                    aria-label="Zgjidh temën e faqes"
                >
                    <option value="pastel-floral" <?= $currentTheme === "pastel-floral" ? "selected" : "" ?>>
                        Pastel Floral
                    </option>

                    <option value="dark" <?= $currentTheme === "dark" ? "selected" : "" ?>>
                        Dark
                    </option>

                    <option value="ocean-blue" <?= $currentTheme === "ocean-blue" ? "selected" : "" ?>>
                        Ocean Blue
                    </option>

                    <option value="sage-green" <?= $currentTheme === "sage-green" ? "selected" : "" ?>>
                        Sage Green
                    </option>
                </select>

            </form>
        </nav>

    </div>
</header>

<main class="container event-create-container">

    <section class="form-card event-create-card">

        <div class="event-flower">
            <?= flowerIcon("brand-flower") ?>
        </div>

        <div class="section-heading">
            <span class="pastel-label">Hapësira e administratorit</span>

            <h1>Regjistro event të ri</h1>

            <p class="muted">
                Plotëso të dhënat dhe krijo një event të ri
                në sistemin tonë.
            </p>
        </div>

        <?php if ($message !== ""): ?>
            <div class="message <?= h($messageType) ?>">
                <?= h($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="form-grid pastel-event-form">

            <div class="field">
                <label for="title">Titulli i eventit *</label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="200"
                    placeholder="Shkruaj titullin e eventit"
                    value="<?= h($title) ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="category">Kategoria *</label>

                <select id="category" name="category" required>
                    <option value="">Zgjidh kategorinë</option>

                    <?php
                    $categories = [
                        "Koncert",
                        "Konferencë",
                        "Sport",
                        "Kulturë",
                        "Teknologji",
                        "Tjetër"
                    ];
                    ?>

                    <?php foreach ($categories as $item): ?>
                        <option
                            value="<?= h($item) ?>"
                            <?= $category === $item ? "selected" : "" ?>
                        >
                            <?= h($item) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div class="field full-width">
                <label for="description">Përshkrimi i eventit</label>

                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    placeholder="Shkruaj disa informata rreth eventit..."
                ><?= h($description) ?></textarea>
            </div>

            <div class="field">
                <label for="location">Lokacioni *</label>

                <input
                    type="text"
                    id="location"
                    name="location"
                    maxlength="200"
                    placeholder="P.sh. Prishtinë"
                    value="<?= h($location) ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="event_date">Data dhe ora *</label>

                <input
                    type="datetime-local"
                    id="event_date"
                    name="event_date"
                    min="<?= h(date("Y-m-d\TH:i")) ?>"
                    value="<?= h($eventDate) ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="price">Çmimi për biletë (€) *</label>

                <input
                    type="number"
                    id="price"
                    name="price"
                    min="0"
                    step="0.01"
                    placeholder="0.00"
                    value="<?= h($price) ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="total_tickets">Numri total i biletave *</label>

                <input
                    type="number"
                    id="total_tickets"
                    name="total_tickets"
                    min="1"
                    step="1"
                    placeholder="P.sh. 100"
                    value="<?= h($totalTickets) ?>"
                    required
                >
            </div>

            <div class="actions event-actions">

                <button type="submit" class="button auth-button">
                    <span>✿</span> Ruaj eventin
                </button>

                <a href="index.php" class="button secondary">
                    Anulo
                </a>

            </div>

        </form>

        <p class="event-form-note">
            Fushat me simbolin (*) janë të detyrueshme.
        </p>

    </section>

</main>

<footer class="site-footer">
    <p>
        <?= flowerIcon("brand-flower") ?>
        Sistemi për Menaxhimin e Eventeve dhe Biletave
        <?= flowerIcon("brand-flower") ?>
    </p>
</footer>

</body>
</html>
