
<?php
require_once "db.php";

$stmt = $pdo->query(
    "SELECT id, title, description, category, location,
            event_date, price, available_tickets
     FROM events
     ORDER BY event_date ASC"
);

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menaxhimi i Eventeve dhe Biletave</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f3f4f6;
            color: #222;
        }

        header {
            background: #243b64;
            color: white;
            padding: 25px;
            text-align: center;
        }

        main {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
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
            box-shadow: 0 2px 8px #00000012;
        }

        .price {
            color: #243b64;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header>
        <h1>Sistemi për Menaxhimin e Eventeve dhe Biletave</h1>
        <p>Gjej eventin tënd të radhës</p>
    </header>

    <main>
        <h2>Eventet e ardhshme</h2>

        <?php if (empty($events)): ?>
            <p>Nuk ka ende evente të regjistruara.</p>
        <?php else: ?>
            <div class="events">
                <?php foreach ($events as $event): ?>
                    <article class="event">
                        <h3><?= htmlspecialchars($event["title"]) ?></h3>

                        <p>
                            <?= nl2br(htmlspecialchars(
                                $event["description"] ?? ""
                            )) ?>
                        </p>

                        <p>
                            <strong>Kategoria:</strong>
                            <?= htmlspecialchars($event["category"] ?? "Pa kategori") ?>
                        </p>

                        <p>
                            <strong>Lokacioni:</strong>
                            <?= htmlspecialchars($event["location"]) ?>
                        </p>

                        <p>
                            <strong>Data:</strong>
                            <?= htmlspecialchars($event["event_date"]) ?>
                        </p>

                        <p class="price">
                            Çmimi: <?= number_format((float)$event["price"], 2) ?> €
                        </p>

                        <p>
                            Bileta të lira:
                            <?= (int)$event["available_tickets"] ?>
                        </p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
