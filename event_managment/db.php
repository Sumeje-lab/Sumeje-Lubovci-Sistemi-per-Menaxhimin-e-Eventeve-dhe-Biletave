
<?php
$host = "localhost";
$dbname = "event_managment";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {
    error_log("Gabim ne lidhjen me databazen: " . $e->getMessage());
    die("Nuk mund te lidhemi me databazen. Kontrollo konfigurimin e XAMPP.");
}
?>
