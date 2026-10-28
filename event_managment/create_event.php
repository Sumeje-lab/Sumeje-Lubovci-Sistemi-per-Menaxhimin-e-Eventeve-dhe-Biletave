
<?php
session_start();
require_once "db.php";

// Vetëm administratori mund të krijojë evente.
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION["user_role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Nuk keni leje për të krijuar evente. Kjo mundësi është vetëm për administratorin.");
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

            // Kontrollojmë nëse administratori ekziston.
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

            // Ruajmë eventin dhe numrin fillestar të biletave.
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
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <div class="header-content">
        <h1>Sistemi për Menaxhimin e Eventeve dhe Biletave</h1>

        <nav>
            <a href="index.php">Kryefaqja</a>
            <a href="create_event.php">Krijo event</a>
            <a href="reservations.php">Rezervimet e mia</a>
            <a href="logout.php">Dil nga llogaria</a>
        </nav>
    </div>
</header>

<main class="container">
    <section class="form-card">
        <div class="section-heading">
            <h1>Regjistro event të ri</h1>
            <p class="muted">
                Plotëso të dhënat për eventin që dëshiron të regjistrosh.
            </p>
        </div>

        <?php if ($message !== ""): ?>
            <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, "UTF-8") ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="form-grid">

            <div class="field">
                <label for="title">Titulli i eventit *</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="200"
                    value="<?= htmlspecialchars($title, ENT_QUOTES, "UTF-8") ?>"
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
                            value="<?= htmlspecialchars($item, ENT_QUOTES, "UTF-8") ?>"
                            <?= $category === $item ? "selected" : "" ?>
                        >
                            <?= htmlspecialchars($item, ENT_QUOTES, "UTF-8") ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="description">Përshkrimi</label>
                <textarea
                    id="description"
                    name="description"
                    rows="4"
                ><?= htmlspecialchars($description, ENT_QUOTES, "UTF-8") ?></textarea>
            </div>

            <div class="field">
                <label for="location">Lokacioni *</label>
                <input
                    type="text"
                    id="location"
                    name="location"
                    maxlength="200"
                    value="<?= htmlspecialchars($location, ENT_QUOTES, "UTF-8") ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="event_date">Data dhe ora e eventit *</label>
                <input
                    type="datetime-local"
                    id="event_date"
                    name="event_date"
                    value="<?= htmlspecialchars($eventDate, ENT_QUOTES, "UTF-8") ?>"
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
                    value="<?= htmlspecialchars($price, ENT_QUOTES, "UTF-8") ?>"
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
                    value="<?= htmlspecialchars($totalTickets, ENT_QUOTES, "UTF-8") ?>"
                    required
                >
            </div>

            <div class="actions">
                <button type="submit" class="button">
                    Ruaj eventin
                </button>

                <a href="index.php" class="button secondary">
                    Anulo
                </a>
            </div>
        </form>
    </section>
</main>

<footer>
    <p>Sistemi për Menaxhimin e Eventeve dhe Biletave</p>
</footer>

</body>
</html>
