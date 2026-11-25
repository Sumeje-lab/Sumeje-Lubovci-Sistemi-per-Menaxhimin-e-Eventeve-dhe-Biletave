
<?php
session_start();
require_once "db.php";

// Funksioni për mbrojtjen e tekstit HTML
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// Ikona dekorative e lules
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

// Nëse përdoruesi është kyçur, kthehet në kryefaqe
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = "Ju lutem plotësoni të gjitha fushat.";

    } else {

        try {

            // Marrim të dhënat e përdoruesit nga databaza
            $sql = "SELECT id, name, email, password_hash, role
                    FROM users
                    WHERE email = :email
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                "email" => $email
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Kontrollojmë fjalëkalimin
            if ($user && password_verify($password, $user["password_hash"])) {

                // Rigjenerojmë ID-në e sesionit
                session_regenerate_id(true);

                // Ruajmë të dhënat në sesion
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_role"] = $user["role"];

                // Ridrejtojmë përdoruesin në kryefaqe
                header("Location: index.php");
                exit;

            } else {

                $error = "Email ose fjalëkalim i pasaktë.";
            }

        } catch (PDOException $e) {

            error_log("Gabim gjatë kyçjes: " . $e->getMessage());

            $error = "Ndodhi një gabim gjatë kyçjes. Ju lutem provoni përsëri.";
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

    <link rel="stylesheet" href="style.css?v=4">
</head>

<body class="auth-page login-page">

<div class="auth-container login-container">

    <!-- Pjesa dekorative -->
    <section class="auth-intro login-intro">

        <?= flowerIcon("auth-flower") ?>

        <p class="auth-eyebrow">MIRË SE U KTHEVE</p>

        <h1>Vazhdo përvojën tënde me eventet.</h1>

        <p>
            Kyçu në llogarinë tënde për të zbuluar evente,
            rezervuar bileta dhe menaxhuar rezervimet.
        </p>

        <div class="auth-decoration">
            <span>✧</span>
            <span>✿</span>
            <span>✧</span>
        </div>

    </section>

    <!-- Forma e kyçjes -->
    <section class="auth-card login-card">

        <div class="auth-card-heading">

            <p class="auth-eyebrow">LLOGARIA JOTE</p>

            <h2>Kyçu në llogari</h2>

            <p>Plotëso të dhënat për të vazhduar.</p>

        </div>

        <!-- Mesazhi i gabimit -->
        <?php if ($error !== ""): ?>

            <div class="message error-message">
                <?= h($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="login.php">

            <div class="form-group">

                <label for="email">Email adresa</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="shembull@email.com"
                    value="<?= h($_POST["email"] ?? "") ?>"
                    autocomplete="email"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">Fjalëkalimi</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Shkruaj fjalëkalimin"
                    autocomplete="current-password"
                    required
                >

            </div>

            <button type="submit" class="auth-button">
                Kyçu
            </button>

        </form>

        <div class="auth-switch">

            Nuk ke ende llogari?

            <a href="register.php">Regjistrohu këtu</a>

        </div>

        <a href="index.php" class="auth-back">
            ← Kthehu në kryefaqe
        </a>

    </section>

</div>

</body>
</html>
