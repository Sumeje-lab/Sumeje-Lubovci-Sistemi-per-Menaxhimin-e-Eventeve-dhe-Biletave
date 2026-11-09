
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
                 FROM users WHERE email = ?"
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
    <title>Kyçu | Evente & Bileta</title>
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
        <a href="register.php" class="nav-highlight">Regjistrohu</a>
    </nav>
</header>

<main class="auth-container">
    <div class="auth-decoration decoration-one">✿</div>
    <div class="auth-decoration decoration-two">✿</div>

    <section class="auth-card">
        <div class="auth-flower">✿</div>

        <div class="auth-heading">
            <span class="eyebrow">MIRË SE U KTHEVE</span>
            <h1>Mirë se vjen përsëri!</h1>
            <p>Kyçu në llogarinë tënde dhe zbulo eventet e ardhshme.</p>
        </div>

        <?php if ($message !== ""): ?>
            <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, "UTF-8") ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
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
                    placeholder="Shkruaj fjalëkalimin"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="auth-button">
                Kyçu në llogari <span>→</span>
            </button>
        </form>

        <div class="auth-divider">
            <span>OSE</span>
        </div>

        <p class="auth-bottom">
            Nuk ke ende llogari?
            <a href="register.php">Regjistrohu këtu</a>
        </p>
    </section>

    <p class="auth-note">✿ Çdo event i bukur fillon me një përvojë të bukur. ✿</p>
</main>

<footer class="pastel-footer">
    <p>✿ Evente & Bileta · Përjeto momente të veçanta ✿</p>
</footer>

</body>
</html>

