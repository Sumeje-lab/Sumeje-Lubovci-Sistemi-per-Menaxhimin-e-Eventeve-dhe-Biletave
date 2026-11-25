
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

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    // Kontrollojmë nëse fushat janë plotësuar
    if (
        $name === "" ||
        $email === "" ||
        $password === "" ||
        $confirmPassword === ""
    ) {

        $error = "Ju lutem plotësoni të gjitha fushat.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Ju lutem shkruani një email adresë valide.";

    } elseif (strlen($password) < 6) {

        $error = "Fjalëkalimi duhet të ketë së paku 6 karaktere.";

    } elseif ($password !== $confirmPassword) {

        $error = "Fjalëkalimet nuk përputhen.";

    } else {

        try {

            // Kontrollojmë nëse email ekziston
            $checkSql = "SELECT id
                         FROM users
                         WHERE email = :email
                         LIMIT 1";

            $checkStmt = $pdo->prepare($checkSql);

            $checkStmt->execute([
                "email" => $email
            ]);

            if ($checkStmt->fetch()) {

                $error = "Ky email është i regjistruar tashmë.";

            } else {

                // Hash-ojmë fjalëkalimin
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Regjistrojmë përdoruesin
                // Përdorim kolonën password_hash
                $sql = "INSERT INTO users
                        (name, email, password_hash, role)
                        VALUES
                        (:name, :email, :password_hash, 'user')";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    "name" => $name,
                    "email" => $email,
                    "password_hash" => $hashedPassword
                ]);

                // Ridrejtojmë përdoruesin te kyçja
                header("Location: login.php?registered=1");
                exit;
            }

        } catch (PDOException $e) {

            error_log("Gabim gjatë regjistrimit: " . $e->getMessage());

            $error = "Ndodhi një gabim gjatë regjistrimit. Ju lutem provoni përsëri.";
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

    <link rel="stylesheet" href="style.css?v=4">
</head>

<body class="auth-page register-page">

<div class="auth-container register-container">

    <!-- Pjesa dekorative -->
    <section class="auth-intro register-intro">

        <?= flowerIcon("auth-flower") ?>

        <p class="auth-eyebrow">BËHU PJESË E KOMUNITETIT</p>

        <h1>Çdo event fillon me një mundësi të re.</h1>

        <p>
            Krijo llogarinë tënde dhe fillo të zbulosh
            përvoja të reja, evente interesante dhe momente të veçanta.
        </p>

        <div class="auth-decoration">
            <span>✧</span>
            <span>✿</span>
            <span>✧</span>
        </div>

    </section>

    <!-- Forma e regjistrimit -->
    <section class="auth-card register-card">

        <div class="auth-card-heading">

            <p class="auth-eyebrow">KRIJO LLOGARINË</p>

            <h2>Regjistrohu</h2>

            <p>Plotëso të dhënat për të krijuar llogarinë.</p>

        </div>

        <!-- Mesazhi i gabimit -->
        <?php if ($error !== ""): ?>

            <div class="message error-message">
                <?= h($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="register.php">

            <div class="form-group">

                <label for="name">Emri dhe mbiemri</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Shkruaj emrin tënd"
                    value="<?= h($_POST["name"] ?? "") ?>"
                    autocomplete="name"
                    required
                >

            </div>

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
                    placeholder="Minimum 6 karaktere"
                    autocomplete="new-password"
                    required
                >

            </div>

            <div class="form-group">

                <label for="confirm_password">Konfirmo fjalëkalimin</label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Shkruaj përsëri fjalëkalimin"
                    autocomplete="new-password"
                    required
                >

            </div>

            <button type="submit" class="auth-button register-button">
                Krijo llogarinë
            </button>

        </form>

        <div class="auth-switch">

            Ke tashmë llogari?

            <a href="login.php">Kyçu këtu</a>

        </div>

        <a href="index.php" class="auth-back">
            ← Kthehu në kryefaqe
        </a>

    </section>

</div>

</body>
</html>
