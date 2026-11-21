
<?php
session_start();
require_once "db.php";

// Temat e lejuara
$allowedThemes = [
    "pastel-floral",
    "dark",
    "ocean-blue",
    "sage-green"
];

$currentTheme = $_SESSION["theme"] ?? "pastel-floral";

if (!in_array($currentTheme, $allowedThemes, true)) {
    $currentTheme = "pastel-floral";
}

$message = "";
$messageType = "error";
$email = "";

// Kontrollojmë nëse përdoruesi sapo është regjistruar.
if (isset($_GET["registered"]) && $_GET["registered"] === "1") {
    $message = "Regjistrimi u krye me sukses. Tani mund të kyçesh.";
    $messageType = "success";
}

// Procesi i kyçjes
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $message = "Ju lutem plotësoni email-in dhe fjalëkalimin.";

    } else {

        try {
            $stmt = $pdo->prepare(
                "SELECT id, name, email, password_hash, role, theme
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

                // Ngarkojmë temën e ruajtur në databazë.
                $userTheme = $user["theme"] ?? "pastel-floral";

                if (!in_array($userTheme, $allowedThemes, true)) {
                    $userTheme = "pastel-floral";
                }

                $_SESSION["theme"] = $userTheme;

                // Krijojmë tokenin CSRF.
                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

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

// Funksioni për sigurinë e tekstit.
function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

// Funksioni për logon me lulen SVG.
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
?>

<!DOCTYPE html>
<html lang="sq">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kyçu | evente&bileta</title>

    <link rel="stylesheet" href="style.css?v=2">
</head>

<body class="auth-page" data-theme="<?= h($currentTheme) ?>">

<!-- HEADER -->
<header class="site-header">

    <div class="header-content">

        <a href="index.php" class="brand">

            <?= flowerIcon("brand-flower") ?>

            <span>evente<span class="brand-amp">&</span>bileta</span>

        </a>

        <nav>

            <a href="index.php">Kryefaqja</a>

            <a href="register.php" class="nav-highlight">
                Regjistrohu
            </a>

        </nav>

    </div>

</header>


<!-- LOGIN SECTION -->
<main class="auth-container">

    <div class="auth-decoration decoration-one">
        <?= flowerIcon("event-flower") ?>
    </div>

    <div class="auth-decoration decoration-two">
        <?= flowerIcon("event-flower") ?>
    </div>


    <section class="auth-card">

        <div class="auth-flower">
            <?= flowerIcon("empty-icon") ?>
        </div>

        <div class="auth-heading">

            <span class="eyebrow">
                MIRË SE U KTHEVE
            </span>

            <h1>Mirë se vjen përsëri!</h1>

            <p>
                Kyçu në llogarinë tënde dhe zbulo eventet e ardhshme.
            </p>

        </div>


        <!-- MESSAGES -->
        <?php if ($message !== ""): ?>

            <div class="message <?= h($messageType) ?>">
                <?= h($message) ?>
            </div>

        <?php endif; ?>


        <!-- LOGIN FORM -->
        <form method="POST" class="auth-form">

            <div class="field">

                <label for="email">
                    Email-i
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Shkruaj email-in tënd"
                    value="<?= h($email) ?>"
                    required
                    autocomplete="email"
                >

            </div>


            <div class="field">

                <label for="password">
                    Fjalëkalimi
                </label>

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

                Kyçu në llogari
                <span>→</span>

            </button>

        </form>


        <div class="auth-divider">
            <span>OSE</span>
        </div>


        <p class="auth-bottom">

            Nuk ke ende llogari?

            <a href="register.php">
                Regjistrohu këtu
            </a>

        </p>

    </section>


    <p class="auth-note">
        ✿ Çdo event i bukur fillon me një përvojë të bukur. ✿
    </p>

</main>


<!-- FOOTER -->
<footer class="site-footer">

    <div class="footer-brand">

        <?= flowerIcon("footer-flower") ?>

        evente<span>&</span>bileta

    </div>

    <p>
        Zbulo. Përjeto. Krijo kujtime të bukura.
    </p>

    <div class="footer-line"></div>

    <small>
        © <?= date("Y") ?> evente&bileta. Të gjitha të drejtat e rezervuara.
    </small>

</footer>

</body>
</html>
