
<?php
session_start();
require_once "db.php";

// Lejohet anulimi vetëm për përdorues të kyçur.
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Anulimi duhet të bëhet përmes formularit POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: reservations.php");
    exit;
}

// Kontrolli CSRF për të mbrojtur formularin.
if (
    !isset($_SESSION["csrf_token"], $_POST["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {
    http_response_code(403);
    exit("Kërkesa është e pavlefshme. Kthehu te rezervimet dhe provo përsëri.");
}

$reservationId = filter_input(INPUT_POST, "reservation_id", FILTER_VALIDATE_INT);

if (!$reservationId || $reservationId < 1) {
    header("Location: reservations.php?error=invalid");
    exit;
}

try {
    $pdo->beginTransaction();

    // Kontrollojmë që rezervimi i përket përdoruesit të kyçur.
    $stmt = $pdo->prepare(
        "SELECT id, event_id, quantity, status
         FROM reservations
         WHERE id = ? AND user_id = ?
         FOR UPDATE"
    );

    $stmt->execute([$reservationId, $_SESSION["user_id"]]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        $pdo->rollBack();
        header("Location: reservations.php?error=not_found");
        exit;
    }

    // Parandalojmë kthimin e biletave më shumë se një herë.
    if ($reservation["status"] !== "confirmed") {
        $pdo->rollBack();
        header("Location: reservations.php?error=already_cancelled");
        exit;
    }

    // Ndryshojmë statusin e rezervimit.
    $stmt = $pdo->prepare(
        "UPDATE reservations
         SET status = 'cancelled'
         WHERE id = ? AND user_id = ?"
    );

    $stmt->execute([$reservationId, $_SESSION["user_id"]]);

    // Rikthejmë biletat e anuluara në dispozicion.
    $stmt = $pdo->prepare(
        "UPDATE events
         SET available_tickets =
             LEAST(total_tickets, available_tickets + ?)
         WHERE id = ?"
    );

    $stmt->execute([
        $reservation["quantity"],
        $reservation["event_id"]
    ]);

    $pdo->commit();

    header("Location: reservations.php?cancelled=1");
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Gabim gjatë anulimit të rezervimit: " . $e->getMessage());

    header("Location: reservations.php?error=failed");
    exit;
}
