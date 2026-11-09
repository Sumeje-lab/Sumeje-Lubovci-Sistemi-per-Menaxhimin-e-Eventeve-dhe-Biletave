
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

                $stmt->execute([$name, $email, $passwordHash]);

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
    <title>Regjistrohu | Evente & Bileta</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

<header class="pastel-header">
    <a href="index.php" class="brand-link">
        <span class="gold-flower">✿</span>
        <span>Evente <span class="brand-amp">&</span> Bileta</span>
    </a>

    <nav>
        <a href="index.php">Kryefaqja</a>
        <a href="login.php" class="nav-highlight">Kyçu</a>
    </nav>
</header>

<main class="auth-container register-container">
    <div class="auth-decoration decoration-one">✿</div>
    <div class="auth-decoration decoration-two">✿</div>

    <section class="auth-card register-card">
        <div class="auth-flower">✿</div>

        <div class="auth-heading">
            <span class="eyebrow">BASHKOHU ME NE</span>
            <h1>Krijo llogarinë tënde</h1>
            <p>Regjistrohu dhe bëhu pjesë e përvojave të veçanta.</p>
        </div>

        <?php if ($message !== ""): ?>
            <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, "UTF-8") ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="field">
                <label for="name">Emri dhe mbiemri</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Shkruaj emrin tënd"
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
                    placeholder="Shkruaj email-in tënd"
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
                    placeholder="Të paktën 8 karaktere"
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
                    placeholder="Shkruaj përsëri fjalëkalimin"
                    minlength="8"
                    required
                    autocomplete="new-password"
                >
            </div>

            <button type="submit" class="auth-button">
                Krijo llogari <span>→</span>
            </button>
        </form>

        <div class="auth-divider">
            <span>OSE</span>
        </div>

        <p class="auth-bottom">
            Ke tashmë një llogari?
            <a href="login.php">Kyçu këtu</a>
        </p>
    </section>

    <p class="auth-note">✿ Momentet më të bukura i përjetojmë së bashku. ✿</p>
</main>

<footer class="pastel-footer">
    <p>✿ Evente & Bileta · Përjeto momente të veçanta ✿</p>
</footer>

</body>
</html>
