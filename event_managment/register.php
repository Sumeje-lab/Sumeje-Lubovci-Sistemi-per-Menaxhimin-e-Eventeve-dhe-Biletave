
<?php
session_start();
require_once "db.php";

$message = "";
$messageType = "error";

$name = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if ($name === "" || $email === "" || $password === "") {
        $message = "Ju lutem plotësoni të gjitha fushat.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Ju lutem shkruani një email të vlefshëm.";
    } elseif (strlen($password) < 8) {
        $message = "Fjalëkalimi duhet të ketë të paktën 8 karaktere.";
    } elseif ($password !== $confirmPassword) {
        $message = "Fjalëkalimet nuk përputhen.";
    } else {
        try {
            $stmt = $pdo->prepare(
                "SELECT id FROM users WHERE email = ?"
            );
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $message = "Ky email është regjistruar tashmë.";
            } else {
                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $pdo->prepare(
                    "INSERT INTO users (name, email, password_hash, role)
                     VALUES (?, ?, ?, 'user')"
                );

                $stmt->execute([
                    $name,
                    $email,
                    $passwordHash
                ]);

                header("Location: login.php?registered=1");
                exit;
            }
        } catch (PDOException $e) {
            $message = "Regjistrimi dështoi për shkak të një gabimi në databazë.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regjistrohu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <div class="header-content">
        <h1>Sistemi për Menaxhimin e Eventeve dhe Biletave</h1>
        <nav>
            <a href="index.php">Kryefaqja</a>
            <a href="login.php">Kyçu</a>
        </nav>
    </div>
</header>

<main class="container">
    <section class="form-card">
        <div class="section-heading">
            <h1>Krijo llogari</h1>
            <p class="muted">Regjistrohu për të përdorur sistemin.</p>
        </div>

        <?php if ($message !== ""): ?>
            <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, "UTF-8") ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="form-grid">
            <div class="field">
                <label for="name">Emri dhe mbiemri</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>"
                    required
                    autocomplete="name"
                >
            </div>

            <div class="field">
                <label for="email">Email-i</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="field">
                <label for="password">Fjalëkalimi</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    required
                    autocomplete="new-password"
                >
            </div>

            <div class="field">
                <label for="confirm_password">Përsërit fjalëkalimin</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="8"
                    required
                    autocomplete="new-password"
                >
            </div>

            <div class="actions">
                <button type="submit" class="button">
                    Regjistrohu
                </button>
            </div>
        </form>

        <p class="intro">
            Ke tashmë një llogari?
            <a href="login.php">Kyçu këtu</a>.
        </p>
    </section>
</main>

<footer>
    <p>Sistemi për Menaxhimin e Eventeve dhe Biletave</p>
</footer>

</body>
</html>
