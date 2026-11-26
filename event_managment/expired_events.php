
<?php
session_start();
require_once "db.php";

date_default_timezone_set("Europe/Belgrade");

$events = [];
$error = "";

try {
    $sql = "SELECT
                id,
                title,
                description,
                category,
                location,
                event_date,
                price,
                available_tickets
            FROM events
            WHERE event_date < ?
            ORDER BY event_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([date("Y-m-d H:i:s")]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Nuk u arrit të ngarkohen eventet e përfunduara nga databaza.";
}

$isLoggedIn = isset($_SESSION["user_id"]);
$isAdmin = $isLoggedIn && (($_SESSION["user_role"] ?? "") === "admin");

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

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

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
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Eventet e përfunduara | evente&bileta</title>

    <link rel="stylesheet" href="style.css?v=2">
</head>

<body data-theme="<?= h($currentTheme) ?>">

<header class="site-header">
    <div class="header-content">

        <a href="index.php" class="brand">
            <?= flowerIcon("brand-flower") ?>
            <span>evente<span class="brand-amp">&</span>bileta</span>
        </a>

        <nav>
            <a href="index.php">Kryefaqja</a>

            <a href="expired_events.php" class="active">
                Eventet e skaduara
            </a>

            <?php if ($isLoggedIn): ?>

                <a href="reservations.php">Rezervimet e mia</a>

                <?php if ($isAdmin): ?>
                    <a href="create_event.php" class="nav-highlight">
                        Krijo event
                    </a>
                <?php endif; ?>

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

            <?php else: ?>

                <a href="login.php">Kyçu</a>

                <a href="register.php" class="nav-highlight">
                    Regjistrohu
                </a>

            <?php endif; ?>
        </nav>

    </div>
</header>

<section class="hero">

    <div class="hero-decoration hero-decoration-one"></div>
    <div class="hero-decoration hero-decoration-two"></div>

    <div class="hero-content">

        <span class="hero-label">
            ✦ KUJTIME TË BUKURA ✦
        </span>

        <h1>
            Eventet e
            <span>përfunduara</span>
        </h1>

        <p>
            Këtu mund të shikosh eventet që tashmë kanë kaluar.
            Ato mbeten të ruajtura në sistem, por nuk mund të rezervohen më.
        </p>

        <a href="index.php" class="button button-primary">
            Shiko eventet e ardhshme
        </a>

        <?= flowerIcon("hero-flower") ?>

    </div>

</section>

<main class="container">

    <div class="section-heading">

        <div>
            <span class="section-eyebrow">ARKIVA E EVENTEVE</span>

            <h2>Eventet e skaduara</h2>

            <p class="muted">
                Eventet që kanë kaluar sipas datës dhe orës lokale të Kosovës.
            </p>
        </div>

        <div class="event-count">
            <?= flowerIcon("event-count-flower") ?>
            Evente të përfunduara
        </div>

    </div>

    <?php if ($error !== ""): ?>

        <div class="message error">
            <?= h($error) ?>
        </div>

    <?php elseif (empty($events)): ?>

        <div class="empty">

            <?= flowerIcon("empty-icon") ?>

            <h3>Nuk ka evente të përfunduara</h3>

            <p>
                Eventet e përfunduara do të shfaqen këtu.
            </p>

        </div>

    <?php else: ?>

        <div class="events">

            <?php foreach ($events as $event): ?>

                <article class="event">

                    <div class="event-top">

                        <span class="category">
                            <?= h($event["category"] ?? "Tjetër") ?>
                        </span>

                        <?= flowerIcon("event-flower") ?>

                    </div>

                    <h3><?= h($event["title"]) ?></h3>

                    <p class="description">
                        <?= nl2br(h($event["description"] ?? "")) ?>
                    </p>

                    <div class="event-details">

                        <p>
                            <span class="detail-icon">⌖</span>
                            <?= h($event["location"]) ?>
                        </p>

                        <p>
                            <span class="detail-icon">◷</span>

                            <?= h(date(
                                "d.m.Y H:i",
                                strtotime($event["event_date"])
                            )) ?>
                        </p>

                    </div>

                    <div class="event-bottom">

                        <div>
                            <span class="price-label">Çmimi i biletës</span>

                            <p class="price">
                                <?= number_format((float)$event["price"], 2) ?> €
                            </p>
                        </div>

                        <div class="tickets">
                            <span class="ticket-dot"></span>

                            <?= (int)$event["available_tickets"] ?>
                            bileta të mbetura
                        </div>

                    </div>

                    <div class="event-action">
                        <div class="sold-out">
                            Eventi ka përfunduar
                        </div>
                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

<footer class="site-footer">

    <div class="footer-brand">

        <?= flowerIcon("footer-flower") ?>

        evente<span>&</span>bileta

    </div>

    <p>
        Zbulo. Përjeto. Krijo kujtime të bukura.
    </p>

    <div class="footer-line"></div>

    <small>
        © <?= date("Y") ?> evente&bileta. Të gjitha të drejtat e rezervuara.
    </small>

</footer>

</body>
</html>