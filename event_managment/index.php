
<?php
require_once "db.php";

try {
    $sql = "SELECT id, title, description, category, location,
                   event_date, price, available_tickets
            FROM events
            ORDER BY event_date ASC";

    $stmt = $pdo->query($sql);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $events = [];
    $error = "Nuk u arrit të ngarkohen eventet nga databaza.";
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sistemi për Menaxhimin e Eventeve dhe Biletave</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            margin: 0;
            color: #1e293b;
        }

        header {
            background: #1e3a8a;
            color: white;
            padding: 25px 15px;
            text-align: center;
        }

        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 15px;
        }

        .add-link, .reserve-link {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 12px 18px;
            text-decoration: none;
            border-radius: 6px;
        }

        .add-link {
            margin-bottom: 20px;
        }

        .events {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }

        .event {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 12px #00000010;
        }

        .event h2 {
            color: #1d4ed8;
            margin-top: 0;
        }

        .price {
            font-size: 20px;
            font-weight: bold;
            color: #15803d;
        }

        .empty {
            background: white;
            padding: 20px;
            border-radius: 8px;
        }
    </style>
</head>

<body>
<header>
    <h1>Sistemi për Menaxhimin e Eventeve dhe Biletave</h1>
    <p>Gjej eventin tënd të radhës!</p>
</header>

<div class="container">
    <a class="add-link" href="create_event.php">
        + Regjistro event të ri
    </a>

    <h2>Eventet e regjistruara</h2>

    <?php if (isset($error)): ?>

        <p><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></p>

    <?php elseif (count($events) === 0): ?>

        <p class="empty">Nuk ka ende evente të regjistruara.</p>

    <?php else: ?>

        <div class="events">
            <?php foreach ($events as $event): ?>

                <article class="event">
                    <h2>
                        <?= htmlspecialchars($event["title"], ENT_QUOTES, "UTF-8") ?>
                    </h2>

                    <p>
                        <strong>Kategoria:</strong>
                        <?= htmlspecialchars($event["category"], ENT_QUOTES, "UTF-8") ?>
                    </p>

                    <p>
                        <strong>Përshkrimi:</strong>
                        <?= nl2br(htmlspecialchars($event["description"] ?? "", ENT_QUOTES, "UTF-8")) ?>
                    </p>

                    <p>
                        <strong>Lokacioni:</strong>
                        <?= htmlspecialchars($event["location"], ENT_QUOTES, "UTF-8") ?>
                    </p>

                    <p>
                        <strong>Data:</strong>
                        <?= htmlspecialchars(
                            date("d.m.Y H:i", strtotime($event["event_date"])),
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </p>

                    <p class="price">
                        <?= number_format((float)$event["price"], 2) ?> €
                    </p>

                    <p>
                        <strong>Bileta të lira:</strong>
                        <?= (int)$event["available_tickets"] ?>
                    </p>

                    <?php if ((int)$event["available_tickets"] > 0): ?>
                        <a
                            class="reserve-link"
                            href="reserve.php?event_id=<?= (int)$event["id"] ?>"
                        >
                            Rezervo bileta
                        </a>
                    <?php else: ?>
                        <p><strong>Biletat janë shitur.</strong></p>
                    <?php endif; ?>

                </article>

            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>
</body>
</html>
