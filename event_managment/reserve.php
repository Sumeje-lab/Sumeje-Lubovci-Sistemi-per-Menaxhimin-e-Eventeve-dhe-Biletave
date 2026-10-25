
<?php
require_once "db.php";

$message = "";
$event = null;

$eventId = filter_input(INPUT_GET, "event_id", FILTER_VALIDATE_INT);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $eventId = filter_input(INPUT_POST, "event_id", FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_INT);
    $userId = filter_input(INPUT_POST, "user_id", FILTER_VALIDATE_INT);

    if (!$eventId || !$quantity || $quantity < 1 || !$userId) {
        $message = "Ju lutem kontrolloni të dhënat e rezervimit.";
    } else {
        try {
            $pdo->beginTransaction();

            // Kontrollojmë eventin dhe biletat e lira
            $stmt = $pdo->prepare(
                "SELECT id, title, price, available_tickets
                 FROM events WHERE id = ? FOR UPDATE"
            );
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$event) {
                throw new Exception("Eventi nuk u gjet.");
            }

            // Kontrollojmë nëse përdoruesi ekziston
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$userId]);

            if (!$stmt->fetch()) {
                throw new Exception("Përdoruesi nuk u gjet.");
            }

            if ((int)$event["available_tickets"] < $quantity) {
                throw new Exception("Nuk ka mjaftueshëm bileta të lira.");
            }

            $totalPrice = (float)$event["price"] * $quantity;

            // Krijojmë rezervimin
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

            // Zvogëlojmë numrin e biletave të lira
            $stmt = $pdo->prepare(
                "UPDATE events
                 SET available_tickets = available_tickets - ?
                 WHERE id = ?"
            );
            $stmt->execute([$quantity, $eventId]);

            $pdo->commit();

            $message = "Rezervimi u realizua me sukses! Çmimi total: "
                . number_format($totalPrice, 2) . " €";

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $message = $e instanceof PDOException
                ? "Ndodhi një gabim në databazë gjatë rezervimit."
                : $e->getMessage();
        }
    }
}

// Marrim eventin për ta shfaqur në formular
if ($eventId && !$event) {
    $stmt = $pdo->prepare(
        "SELECT id, title, price, available_tickets
         FROM events WHERE id = ?"
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
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            padding: 25px 15px;
        }
        .container {
            max-width: 500px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        input {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            margin-top: 6px;
        }
        button {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            background: #2563eb;
            color: white;
            border: 0;
            cursor: pointer;
        }
        .message {
            padding: 12px;
            background: #e0f2fe;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Rezervimi i biletave</h1>

    <?php if ($message !== ""): ?>
        <p class="message">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <?php if ($event): ?>
        <h2><?= htmlspecialchars($event["title"], ENT_QUOTES, "UTF-8") ?></h2>
        <p>Çmimi për biletë: <?= number_format((float)$event["price"], 2) ?> €</p>
        <p>Bileta të lira: <?= (int)$event["available_tickets"] ?></p>

        <?php if ((int)$event["available_tickets"] > 0): ?>
            <form method="POST">
                <input type="hidden" name="event_id"
                       value="<?= (int)$event["id"] ?>">

                <label for="user_id">ID e përdoruesit</label>
                <input type="number" id="user_id" name="user_id"
                       min="1" required>

                <label for="quantity">Numri i biletave</label>
                <input type="number" id="quantity" name="quantity"
                       min="1" max="<?= (int)$event["available_tickets"] ?>"
                       required>

                <button type="submit">Konfirmo rezervimin</button>
            </form>
        <?php else: ?>
            <p>Ky event nuk ka më bileta të lira.</p>
        <?php endif; ?>
    <?php else: ?>
        <p>Eventi nuk u gjet. Kthehu te faqja kryesore dhe zgjidh një event.</p>
    <?php endif; ?>

    <p><a href="index.php">Kthehu te eventet</a></p>
</div>
</body>
</html>
