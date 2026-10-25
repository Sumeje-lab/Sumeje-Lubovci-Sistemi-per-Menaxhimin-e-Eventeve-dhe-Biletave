
<?php
require_once "db.php";

$reservationId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

$message = "";

if (!$reservationId || $reservationId < 1) {
    $message = "ID e rezervimit nuk është e vlefshme.";
} else {
    try {
        $pdo->beginTransaction();

        // Gjejmë dhe bllokojmë rezervimin gjatë përpunimit
        $stmt = $pdo->prepare(
            "SELECT id, event_id, quantity, status
             FROM reservations
             WHERE id = ?
             FOR UPDATE"
        );
        $stmt->execute([$reservationId]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reservation) {
            throw new Exception("Rezervimi nuk u gjet.");
        }

        if ($reservation["status"] !== "confirmed") {
            throw new Exception(
                "Ky rezervim është anuluar ose nuk është aktiv."
            );
        }

        // Kthejmë biletat në dispozicion
        $stmt = $pdo->prepare(
            "UPDATE events
             SET available_tickets = available_tickets + ?
             WHERE id = ?"
        );
        $stmt->execute([
            (int)$reservation["quantity"],
            (int)$reservation["event_id"]
        ]);

        // Ndryshojmë statusin e rezervimit
        $stmt = $pdo->prepare(
            "UPDATE reservations
             SET status = 'cancelled'
             WHERE id = ? AND status = 'confirmed'"
        );
        $stmt->execute([$reservationId]);

        if ($stmt->rowCount() !== 1) {
            throw new Exception(
                "Rezervimi nuk mund të anulohej."
            );
        }

        $pdo->commit();

        header("Location: reservations.php?cancelled=1");
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $message = $e instanceof PDOException
            ? "Ndodhi një gabim gjatë anulimit."
            : $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anulimi i rezervimit</title>
</head>
<body>
    <h1>Anulimi i rezervimit</h1>

    <p>
        <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
    </p>

    <a href="reservations.php">Kthehu te rezervimet</a>
</body>
</html>
