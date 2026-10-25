
<?php
require_once "db.php";

$message = "";
$reservations = [];

if (isset($_GET["cancelled"]) && $_GET["cancelled"] === "1") {
    $message = "Rezervimi u anulua me sukses dhe biletat u kthyen në dispozicion.";
}

try {
    $sql = "SELECT
                r.id,
                u.name AS user_name,
                u.email,
                e.title AS event_title,
                r.quantity,
                r.total_price,
                r.status,
                r.created_at
            FROM reservations r
            INNER JOIN users u ON r.user_id = u.id
            INNER JOIN events e ON r.event_id = e.id
            ORDER BY r.created_at DESC";

    $stmt = $pdo->query($sql);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Nuk u arrit të ngarkohen rezervimet nga databaza.";
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rezervimet - Menaxhimi i Eventeve</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 25px 15px;
            color: #1e293b;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 12px #00000010;
        }

        h1 {
            color: #1e3a8a;
            text-align: center;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }

        th {
            background: #1e3a8a;
            color: white;
        }

        tr:nth-child(even) {
            background: #f8fafc;
        }

        .status-confirmed {
            color: #15803d;
            font-weight: bold;
        }

        .status-cancelled {
            color: #dc2626;
            font-weight: bold;
        }

        .cancel-link {
            display: inline-block;
            background: #dc2626;
            color: white;
            padding: 8px 10px;
            text-decoration: none;
            border-radius: 5px;
        }

        .cancel-link:hover {
            background: #b91c1c;
        }

        .message {
            padding: 12px;
            background: #dcfce7;
            color: #166534;
            border-radius: 6px;
        }

        .error {
            padding: 12px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 6px;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>

<body>
<div class="container">

    <h1>Rezervimet e biletave</h1>

    <?php if ($message !== ""): ?>
        <p class="message">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <?php if (isset($error)): ?>

        <p class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </p>

    <?php elseif (count($reservations) === 0): ?>

        <p>Nuk ka ende rezervime të regjistruara.</p>

    <?php else: ?>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Përdoruesi</th>
                        <th>Email</th>
                        <th>Eventi</th>
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
                            <td>
                                <?= (int)$reservation["id"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $reservation["user_name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $reservation["email"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $reservation["event_title"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?= (int)$reservation["quantity"] ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (float)$reservation["total_price"],
                                    2
                                ) ?> €
                            </td>

                            <td>
                                <?php if ($reservation["status"] === "confirmed"): ?>
                                    <span class="status-confirmed">
                                        Konfirmuar
                                    </span>
                                <?php elseif ($reservation["status"] === "cancelled"): ?>
                                    <span class="status-cancelled">
                                        Anuluar
                                    </span>
                                <?php else: ?>
                                    <?= htmlspecialchars(
                                        $reservation["status"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $reservation["created_at"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>

                            <td>
                                <?php if ($reservation["status"] === "confirmed"): ?>
                                    <a
                                        class="cancel-link"
                                        href="cancel_reservation.php?id=<?= (int)$reservation["id"] ?>"
                                        onclick="return confirm('A dëshironi ta anuloni këtë rezervim?');"
                                    >
                                        Anulo rezervimin
                                    </a>
                                <?php else: ?>
                                    Nuk ka veprim
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>

    <a class="back-link" href="index.php">
        Kthehu te eventet
    </a>

</div>
</body>
</html>
