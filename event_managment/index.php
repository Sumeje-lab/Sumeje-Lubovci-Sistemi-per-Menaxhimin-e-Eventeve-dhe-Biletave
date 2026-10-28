
<?php
session_start();
require_once "db.php";

$events = [];
$error = "";
$successMessage = "";

if (isset($_GET["event_created"]) && $_GET["event_created"] === "1") {
    $successMessage = "Eventi u regjistrua me sukses!";
}

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
            ORDER BY event_date ASC";

    $stmt = $pdo->query($sql);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Nuk u arrit të ngarkohen eventet nga databaza.";
}

$isLoggedIn = isset($_SESSION["user_id"]);
$isAdmin = $isLoggedIn && (($_SESSION["user_role"] ?? "") === "admin");
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistemi për Menaxhimin e Eventeve dhe Biletave</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <div class="header-content">
        <h1>Sistemi për Menaxhimin e Eventeve dhe Biletave</h1>

        <nav>
            <a href="index.php">Kryefaqja</a>

            <?php if ($isLoggedIn): ?>
                <a href="reservations.php">Rezervimet e mia</a>

                <?php if ($isAdmin): ?>
                    <a href="create_event.php">Krijo event</a>
                <?php endif; ?>

                <a href="logout.php">Dil nga llogaria</a>
            <?php else: ?>
                <a href="login.php">Kyçu</a>
                <a href="register.php">Regjistrohu</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<section class="hero">
    <h2>Mirë se vjen në botën e eventeve!</h2>
    <p>Gjej eventin tënd të radhës dhe rezervo biletat në mënyrë të lehtë.</p>

    <?php if ($isLoggedIn): ?>
        <p>
            Përshëndetje,
            <?= htmlspecialchars(
                $_SESSION["user_name"] ?? "Përdorues",
                ENT_QUOTES,
                "UTF-8"
            ) ?>!
        </p>
    <?php else: ?>
        <div class="actions">
            <a href="login.php" class="button">Kyçu</a>
            <a href="register.php" class="button secondary">Krijo llogari</a>
        </div>
    <?php endif; ?>
</section>

<main class="container">
    <div class="section-heading">
        <h2>Eventet e ardhshme</h2>
        <p class="muted">Shiko eventet e regjistruara në sistem.</p>
    </div>

    <?php if ($successMessage !== ""): ?>
        <div class="message success">
            <?= htmlspecialchars($successMessage, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="message error">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>

    <?php elseif (empty($events)): ?>
        <div class="empty">
            Nuk ka ende evente të regjistruara.
        </div>

    <?php else: ?>
        <div class="events">
            <?php foreach ($events as $event): ?>
                <article class="event">
                    <span class="category">
                        <?= htmlspecialchars(
                            $event["category"] ?? "Tjetër",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </span>

                    <h2>
                        <?= htmlspecialchars(
                            $event["title"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </h2>

                    <p class="description">
                        <?= nl2br(htmlspecialchars(
                            $event["description"] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        )) ?>
                    </p>

                    <p>
                        <strong>Lokacioni:</strong>
                        <?= htmlspecialchars(
                            $event["location"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </p>

                    <p>
                        <strong>Data:</strong>
                        <?= htmlspecialchars(
                            date(
                                "d.m.Y H:i",
                                strtotime($event["event_date"])
                            ),
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </p>

                    <p class="price">
                        <?= number_format((float)$event["price"], 2) ?> €
                    </p>

                    <p class="tickets">
                        Bileta të lira:
                        <?= (int)$event["available_tickets"] ?>
                    </p>

                    <?php if ((int)$event["available_tickets"] > 0): ?>
                        <?php if ($isLoggedIn): ?>
                            <a
                                href="reserve.php?event_id=<?= (int)$event["id"] ?>"
                                class="button reserve-link"
                            >
                                Rezervo bileta
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="button reserve-link">
                                Kyçu për të rezervuar
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="sold-out">Biletat janë shitur.</p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<footer>
    <p>Sistemi për Menaxhimin e Eventeve dhe Biletave</p>
</footer>

</body>
</html>
