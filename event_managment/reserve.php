
<?php
session_start();
require_once "db.php";

// Kontrollojmë nëse përdoruesi është kyçur.
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$message = "";
$messageType = "success";
$event = null;

$eventId = filter_input(INPUT_GET, "event_id", FILTER_VALIDATE_INT);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $eventId = filter_input(INPUT_POST, "event_id", FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_INT);

    // ID merret nga sesioni, jo nga formulari.
    $userId = (int) $_SESSION["user_id"];

    if (!$eventId || $eventId < 1 || !$quantity || $quantity < 1) {
        $message = "Ju lutem kontrolloni të dhënat e rezervimit.";
        $messageType = "error";
    } else {
        try {
            $pdo->beginTransaction();

            // Kontrollojmë eventin dhe biletat e lira.
            $stmt = $pdo->prepare(
                "SELECT id, title, price, available_tickets
                 FROM events
                 WHERE id = ?
                 FOR UPDATE"
            );

            $stmt->execute([$eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$event) {
                throw new Exception("Eventi nuk u gjet.");
            }

            // Kontrollojmë nëse përdoruesi ekziston.
            $stmt = $pdo->prepare(
                "SELECT id FROM users WHERE id = ?"
            );

            $stmt->execute([$userId]);

            if (!$stmt->fetch()) {
                throw new Exception(
                    "Përdoruesi nuk u gjet. Ju lutem kyçuni përsëri."
                );
            }

            // Kontrollojmë numrin e biletave të lira.
            if ((int) $event["available_tickets"] < $quantity) {
                throw new Exception(
                    "Nuk ka mjaftueshëm bileta të lira."
                );
            }

            // Llogarisim çmimin total.
            $totalPrice = (float) $event["price"] * $quantity;

            // Regjistrojmë rezervimin.
            $stmt = $pdo->prepare(
                "INSERT INTO reservations
                 (user_id, event_id, quantity, total_price, status)
                 VALUES (?, ?, ?, ?, 'confirmed')"
            );

            $stmt->execute([
                $userId,
                $eventId,
                $quantity,
                $totalPrice
            ]);

            // Përditësojmë numrin e biletave të lira.
            $stmt = $pdo->prepare(
                "UPDATE events
                 SET available_tickets = available_tickets - ?
                 WHERE id = ?"
            );

            $stmt->execute([$quantity, $eventId]);

            // Përfundojmë transaksionin.
            $pdo->commit();

            $message = "Rezervimi u realizua me sukses! Çmimi total: "
                . number_format($totalPrice, 2) . " €";

            // Rifreskojmë të dhënat e eventit.
            $stmt = $pdo->prepare(
                "SELECT id, title, price, available_tickets
                 FROM events
                 WHERE id = ?"
            );

            $stmt->execute([$eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e instanceof PDOException) {
                $message = "Ndodhi një gabim në databazë gjatë rezervimit.";
            } else {
                $message = $e->getMessage();
            }

            $messageType = "error";
        }
    }
}

// Marrim eventin kur faqja hapet për herë të parë
// ose kur të dhënat e eventit nuk janë marrë ende.
if ($eventId && $eventId > 0 && !$event) {
    $stmt = $pdo->prepare(
        "SELECT id, title, price, available_tickets
         FROM events
         WHERE id = ?"
    );

    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="sq">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rezervo bileta</title>

    <link rel="stylesheet" href="style.css">
</head>

<body class="auth-page">

<header class="pastel-header">

    <div class="header-content">

        <a href="index.php" class="brand-link">
            <span class="gold-flower">✿</span>
            <span>Sistemi për Menaxhimin e Eventeve dhe Biletave</span>
        </a>

        <nav>
            <a href="index.php">Kryefaqja</a>
            <a href="reservations.php">Rezervimet e mia</a>
            <a href="logout.php">Dil nga llogaria</a>
        </nav>

    </div>

</header>

<main class="container reservation-form-container">

    <section class="form-card reservation-form-card">

        <div class="event-flower">✿</div>

        <div class="section-heading">

            <span class="pastel-label">Përjeto eventin</span>

            <h1>Rezervimi i biletave</h1>

            <p class="muted">
                Zgjidh numrin e biletave dhe siguro vendin tënd në event.
            </p>

        </div>

        <?php if ($message !== ""): ?>

            <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, "UTF-8") ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            </div>

        <?php endif; ?>

        <?php if ($event): ?>

            <div class="reservation-event-info">

                <div class="reservation-event-icon">✿</div>

                <h2>
                    <?= htmlspecialchars($event["title"], ENT_QUOTES, "UTF-8") ?>
                </h2>

                <div class="reservation-info-row">

                    <span>Çmimi për biletë</span>

                    <strong>
                        <?= number_format((float) $event["price"], 2) ?> €
                    </strong>

                </div>

                <div class="reservation-info-row">

                    <span>Bileta të lira</span>

                    <strong>
                        <?= (int) $event["available_tickets"] ?>
                    </strong>

                </div>

            </div>

            <?php if ((int) $event["available_tickets"] > 0): ?>

                <form method="POST" class="form-grid pastel-reservation-form">

                    <input
                        type="hidden"
                        name="event_id"
                        value="<?= (int) $event["id"] ?>"
                    >

                    <div class="field">

                        <label for="quantity">
                            Numri i biletave
                        </label>

                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            min="1"
                            max="<?= (int) $event["available_tickets"] ?>"
                            placeholder="Shkruaj numrin e biletave"
                            required
                        >

                        <small class="field-help">
                            Mund të rezervosh deri në
                            <?= (int) $event["available_tickets"] ?>
                            bileta.
                        </small>

                    </div>

                    <div class="actions reservation-actions">

                        <button type="submit" class="button auth-button">
                            ✿ Konfirmo rezervimin
                        </button>

                        <a href="index.php" class="button secondary">
                            Kthehu te eventet
                        </a>

                    </div>

                </form>

            <?php else: ?>

                <div class="sold-out pastel-sold-out">
                    Ky event nuk ka më bileta të lira.
                </div>

                <div class="reservation-back-link">
                    <a href="index.php" class="button secondary">
                        Kthehu te eventet
                    </a>
                </div>

            <?php endif; ?>

        <?php else: ?>

            <div class="pastel-empty">

                <div class="empty-flower">✿</div>

                <h2>Eventi nuk u gjet</h2>

                <p>
                    Eventi që kërkon nuk ekziston ose nuk është më i disponueshëm.
                </p>

                <a href="index.php" class="button auth-button">
                    Kthehu te eventet
                </a>

            </div>

        <?php endif; ?>

    </section>

</main>

<footer class="pastel-footer">

    <p>
        <span class="gold-flower">✿</span>
        &copy; <?= date("Y") ?> Sistemi për Menaxhimin e Eventeve dhe Biletave
        <span class="gold-flower">✿</span>
    </p>

</footer>

</body>
</html>
