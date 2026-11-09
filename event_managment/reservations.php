
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

<body class="auth-page">

<header class="pastel-header">
    <div class="header-content">

        <a href="index.php" class="brand-link">
            <span class="gold-flower">✿</span>
            <span>Sistemi për Menaxhimin e Eventeve dhe Biletave</span>
        </a>

        <nav>
            <a href="index.php">Kryefaqja</a>

            <a href="reservations.php" class="active">
                Rezervimet e mia
            </a>

            <?php if (($_SESSION["user_role"] ?? "") === "admin"): ?>
                <a href="create_event.php">Krijo event</a>
            <?php endif; ?>

            <a href="logout.php">Dil nga llogaria</a>
        </nav>

    </div>
</header>

<main class="container reservations-container">

    <div class="reservations-heading">
        <span class="gold-flower heading-flower">✿</span>

        <span class="pastel-label">Hapësira ime</span>

        <h1>Rezervimet e mia</h1>

        <p>
            Këtu mund t'i shikosh rezervimet, detajet dhe statusin e tyre.
        </p>
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

        <section class="content-card empty pastel-empty">

            <div class="empty-flower">✿</div>

            <h2>Nuk ke ende rezervime</h2>

            <p>
                Zbulo eventet e disponueshme dhe bëj rezervimin tënd të parë.
            </p>

            <a href="index.php" class="button auth-button">
                Shiko eventet
            </a>

        </section>

    <?php else: ?>

        <section class="content-card reservations-card">

            <div class="reservations-card-heading">
                <h2>Lista e rezervimeve</h2>

                <span class="reservation-count">
                    <?= count($reservations) ?>
                    <?= count($reservations) === 1 ? "rezervim" : "rezervime" ?>
                </span>
            </div>

            <div class="table-container pastel-table-container">

                <table class="pastel-table">

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

                            <td class="event-name">
                                <?= h($reservation["event_title"]) ?>
                            </td>

                            <td>
                                <?= h(date("d.m.Y H:i", strtotime($reservation["event_date"]))) ?>
                            </td>

                            <td>
                                <?= (int) $reservation["quantity"] ?>
                            </td>

                            <td class="price-cell">
                                <?= number_format((float) $reservation["total_price"], 2) ?> €
                            </td>

                            <td>
                                <?php if ($reservation["status"] === "confirmed"): ?>

                                    <span class="status-badge confirmed">
                                        Konfirmuar
                                    </span>

                                <?php elseif ($reservation["status"] === "cancelled"): ?>

                                    <span class="status-badge cancelled">
                                        Anuluar
                                    </span>

                                <?php else: ?>

                                    <span class="status-badge other-status">
                                        <?= h($reservation["status"]) ?>
                                    </span>

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

                                        <button
                                            type="submit"
                                            class="cancel-button"
                                        >
                                            Anulo rezervimin
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <span class="no-action">—</span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </section>

    <?php endif; ?>

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
