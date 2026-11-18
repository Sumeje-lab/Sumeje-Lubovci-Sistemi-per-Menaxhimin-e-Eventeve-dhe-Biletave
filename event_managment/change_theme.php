
<?php
session_start();
require_once "db.php";

// Vetëm përdoruesit e kyçur mund ta ndryshojnë temën.
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Lejohet vetëm metoda POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

// Kontrollimi i CSRF token.
if (
    !isset($_POST["csrf_token"], $_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {
    http_response_code(403);
    exit("Kërkesa nuk është e vlefshme. Rifresko faqen dhe provo përsëri.");
}

// Temat e lejuara.
$allowedThemes = [
    "pastel-floral",
    "dark",
    "ocean-blue",
    "sage-green"
];

$theme = $_POST["theme"] ?? "";

// Kontrollimi i temës së zgjedhur.
if (!in_array($theme, $allowedThemes, true)) {
    header("Location: index.php");
    exit;
}

try {
    // Ruajtja e temës për përdoruesin aktual.
    $stmt = $pdo->prepare(
        "UPDATE users SET theme = ? WHERE id = ?"
    );

    $stmt->execute([
        $theme,
        $_SESSION["user_id"]
    ]);

    // Përditësimi i sesionit.
    $_SESSION["theme"] = $theme;

    header("Location: index.php");
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    exit("Tema nuk u ruajt. Provo përsëri.");
}
