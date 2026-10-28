
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Krijojmë tokenin e sigurisë për formularin e anulimit.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$userId = (int) $_SESSION["user_id"];

$stmt = $pdo->prepare(
    "SELECT
        r.id,
        r.quantity,
        r.total_price,
        r.status,
        r.created_at,
        e.title AS event_title,
        e.event_date
     FROM reservations r
     INNER JOIN events e ON r.event_id = e.id
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC"
);

$stmt->execute([$userId]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$messages = [
    "cancelled" => "Rezervimi u anulua me sukses dhe biletat u kthyen në dispozicion.",
    "invalid" => "Numri i rezervimit nuk është i vlefshëm.",
    "not_found" => "Rezervimi nuk u gjet ose nuk të përket ty.",
    "already_cancelled" => "Ky rezervim është anuluar më parë ose nuk mund të anulohet.",
    "failed" => "Ndodhi një gabim gjatë anulimit. Provo përsëri."
];

$errorCode = $_GET["error"] ?? "";
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervimet e mia</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="site-header">
    <div class="header-content">
        <h1>Menaxhimi i Eventeve</h1>

        <nav>
            <a href="index.php">Ballina</a>
            <a href="reservations.php">Rezervimet e mia</a>
            <?php if (($_SESSION["user_role"] ?? "") === "admin"): ?>
                <a href="create_event.php">Krijo event</a>
            <?php endif; ?>
            <a href="logout.php">Dil</a>
        </nav>
    </div>
</header>

<main class="container">
    <div class="section-heading">
        <h2>Rezervimet e mia</h2>
        <p>Këtu mund t'i shikosh rezervimet dhe statusin e tyre.</p>
    </div>

    <?php if (isset($_GET["cancelled"]) && $_GET["cancelled"] === "1"): ?>
        <div class="message success">
            <?= h($messages["cancelled"]) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($messages[$errorCode])): ?>
        <div class="message error">
            <?= h($messages[$errorCode]) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($reservations)): ?>
        <div class="content-card empty">
            <p>Nuk ke ende rezervime.</p>
            <a href="index.php" class="button">Shiko eventet</a>
        </div>
    <?php else: ?>
        <div class="content-card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Eventi</th>
                            <th>Data e eventit</th>
                            <th>Biletat</th>
                            <th>Çmimi total</th>
                            <th>Statusi</th>
                            <th>Data e rezervimit</th>
                            <th>Veprimi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reservations as $reservation): ?>
                        <tr>
                            <td><?= h($reservation["event_title"]) ?></td>
                            <td>
                                <?= h(date("d.m.Y H:i", strtotime($reservation["event_date"]))) ?>
                            </td>
                            <td><?= (int) $reservation["quantity"] ?></td>
                            <td><?= number_format((float) $reservation["total_price"], 2) ?> €</td>
                            <td>
                                <?php if ($reservation["status"] === "confirmed"): ?>
                                    Konfirmuar
                                <?php elseif ($reservation["status"] === "cancelled"): ?>
                                    Anuluar
                                <?php else: ?>
                                    <?= h($reservation["status"]) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= h(date("d.m.Y H:i", strtotime($reservation["created_at"]))) ?>
                            </td>
                            <td>
                                <?php if ($reservation["status"] === "confirmed"): ?>
                                    <form
                                        action="cancel_reservation.php"
                                        method="POST"
                                        onsubmit="return confirm('A je i sigurt që dëshiron ta anulosh këtë rezervim?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="reservation_id"
                                            value="<?= (int) $reservation["id"] ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= h($_SESSION["csrf_token"]) ?>"
                                        >
                                        <button type="submit" class="button secondary">
                                            Anulo rezervimin
                                        </button>
                                    </form>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; <?= date("Y") ?> Sistemi për Menaxhimin e Eventeve dhe Biletave</p>
</footer>

</body>
</html>

