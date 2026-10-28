
<?php
session_start();
require_once "db.php";

$message = "";
$messageType = "error";

$email = "";

if (isset($_GET["registered"]) && $_GET["registered"] === "1") {
    $message = "Regjistrimi u krye me sukses. Tani mund të kyçesh.";
    $messageType = "success";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $message = "Ju lutem plotësoni email-in dhe fjalëkalimin.";
    } else {
        try {
            $stmt = $pdo->prepare(
                "SELECT id, name, email, password_hash, role
                 FROM users
                 WHERE email = ?"
            );

            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user["password_hash"])) {
                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_email"] = $user["email"];
                $_SESSION["user_role"] = $user["role"];

                header("Location: index.php");
                exit;
            } else {
                $message = "Email-i ose fjalëkalimi nuk është i saktë.";
            }
        } catch (PDOException $e) {
            $message = "Nuk u arrit të kryhet kyçja. Provo përsëri.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kyçu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <div class="header-content">
        <h1>Sistemi për Menaxhimin e Eventeve dhe Biletave</h1>
        <nav>
            <a href="index.php">Kryefaqja</a>
            <a href="register.php">Regjistrohu</a>
        </nav>
    </div>
</header>

<main class="container">
    <section class="form-card">
        <div class="section-heading">
            <h1>Kyçu në llogari</h1>
            <p class="muted">Shkruaj të dhënat e llogarisë tënde.</p>
        </div>

        <?php if ($message !== ""): ?>
            <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, "UTF-8") ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="form-grid">
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
                    required
                    autocomplete="current-password"
                >
            </div>

            <div class="actions">
                <button type="submit" class="button">
                    Kyçu
                </button>
            </div>
        </form>

        <p class="intro">
            Nuk ke llogari?
            <a href="register.php">Regjistrohu këtu</a>.
        </p>
    </section>
</main>

<footer>
    <p>Sistemi për Menaxhimin e Eventeve dhe Biletave</p>
</footer>

</body>
</html>
