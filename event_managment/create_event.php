
<?php
require_once "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $event_date = $_POST["event_date"] ?? "";
    $price = $_POST["price"] ?? "";
    $total_tickets = $_POST["total_tickets"] ?? "";

    if (
        $title === "" ||
        $category === "" ||
        $location === "" ||
        $event_date === "" ||
        $price === "" ||
        $total_tickets === ""
    ) {
        $message = "Ju lutem plotësoni të gjitha fushat e detyrueshme.";
    } elseif (
        !is_numeric($price) ||
        (float)$price < 0 ||
        filter_var($total_tickets, FILTER_VALIDATE_INT) === false ||
        (int)$total_tickets < 1
    ) {
        $message = "Çmimi duhet të jetë pozitiv ose zero dhe biletat duhet të jenë të paktën 1.";
    } else {
        try {
            // Gjejmë administratorin
            $userQuery = $pdo->prepare(
                "SELECT id FROM users WHERE email = ? AND role = 'admin' LIMIT 1"
            );
            $userQuery->execute(["admin@eventmanager.com"]);
            $admin = $userQuery->fetch(PDO::FETCH_ASSOC);

            if (!$admin) {
                $message = "Administratori nuk u gjet në databazë.";
            } else {
                // Ruajmë eventin në databazë
                $sql = "INSERT INTO events
                    (title, description, category, location, event_date,
                     price, total_tickets, available_tickets, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $title,
                    $description,
                    $category,
                    $location,
                    $event_date,
                    $price,
                    (int)$total_tickets,
                    (int)$total_tickets,
                    $admin["id"]
                ]);

                $message = "Eventi u regjistrua me sukses!";
            }
        } catch (PDOException $e) {
            $message = "Ndodhi një gabim gjatë ruajtjes së eventit.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shto event</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 30px 15px;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 12px #00000015;
        }

        h1 {
            text-align: center;
            color: #1e3a8a;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
        }

        button {
            margin-top: 20px;
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .message {
            padding: 10px;
            background: #e0f2fe;
            border-radius: 5px;
        }

        a {
            display: inline-block;
            margin-top: 15px;
        }
    </style>
</head>

<body>
<div class="container">
    <h1>Regjistro event të ri</h1>

    <?php if ($message !== ""): ?>
        <p class="message">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="title">Emri i eventit *</label>
        <input type="text" id="title" name="title" required>

        <label for="description">Përshkrimi</label>
        <textarea id="description" name="description" rows="4"></textarea>

        <label for="category">Kategoria *</label>
        <select id="category" name="category" required>
            <option value="">Zgjidh kategorinë</option>
            <option value="Koncert">Koncert</option>
            <option value="Konferencë">Konferencë</option>
            <option value="Sport">Sport</option>
            <option value="Kulturë">Kulturë</option>
            <option value="Tjetër">Tjetër</option>
        </select>

        <label for="location">Lokacioni *</label>
        <input type="text" id="location" name="location" required>

        <label for="event_date">Data dhe ora *</label>
        <input type="datetime-local" id="event_date" name="event_date" required>

        <label for="price">Çmimi i biletës (€) *</label>
        <input type="number" id="price" name="price"
               min="0" step="0.01" required>

        <label for="total_tickets">Numri i biletave *</label>
        <input type="number" id="total_tickets" name="total_tickets"
               min="1" step="1" required>

        <button type="submit">Ruaj eventin</button>
    </form>

    <a href="index.php">Kthehu te faqja kryesore</a>
</div>
</body>
</html>
