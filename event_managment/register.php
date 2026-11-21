
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
$name = "";
$email = "";

// Procesi i regjistrimit
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

            // Kontrollojmë nëse email-i ekziston.
            $stmt = $pdo->prepare(
                "SELECT id FROM users WHERE email = ?"
            );

            $stmt->execute([$email]);

            if ($stmt->fetch()) {

                $message = "Ky email është regjistruar tashmë.";

            } else {

                // Hashimi i fjalëkalimit.
                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Regjistrojmë përdoruesin.
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

    <title>Regjistrohu | evente&bileta</title>

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

            <a href="login.php" class="nav-highlight">
                Kyçu
            </a>

        </nav>

    </div>

</header>


<!-- REGISTER SECTION -->
<main class="auth-container register-container">

    <div class="auth-decoration decoration-one">
        <?= flowerIcon("event-flower") ?>
    </div>

    <div class="auth-decoration decoration-two">
        <?= flowerIcon("event-flower") ?>
    </div>


    <section class="auth-card register-card">

        <div class="auth-flower">
            <?= flowerIcon("empty-icon") ?>
        </div>


        <div class="auth-heading">

            <span class="eyebrow">
                BASHKOHU ME NE
            </span>

            <h1>Krijo llogarinë tënde</h1>

            <p>
                Regjistrohu dhe bëhu pjesë e përvojave të veçanta.
            </p>

        </div>


        <!-- ERROR MESSAGE -->
        <?php if ($message !== ""): ?>

            <div class="message <?= h($messageType) ?>">
                <?= h($message) ?>
            </div>

        <?php endif; ?>


        <!-- REGISTER FORM -->
        <form method="POST" class="auth-form">

            <div class="field">

                <label for="name">
                    Emri dhe mbiemri
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Shkruaj emrin tënd"
                    value="<?= h($name) ?>"
                    required
                    autocomplete="name"
                >

            </div>


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
                    placeholder="Të paktën 8 karaktere"
                    minlength="8"
                    required
                    autocomplete="new-password"
                >

            </div>


            <div class="field">

                <label for="confirm_password">
                    Përsërit fjalëkalimin
                </label>

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

                Krijo llogari
                <span>→</span>

            </button>

        </form>


        <div class="auth-divider">
            <span>OSE</span>
        </div>


        <p class="auth-bottom">

            Ke tashmë një llogari?

            <a href="login.php">
                Kyçu këtu
            </a>

        </p>

    </section>


    <p class="auth-note">
        ✿ Momentet më të bukura i përjetojmë së bashku. ✿
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
