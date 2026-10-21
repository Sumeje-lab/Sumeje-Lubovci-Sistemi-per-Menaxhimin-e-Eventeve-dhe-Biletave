
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

    echo "Lidhja me databazen u realizua me sukses!";
} catch (PDOException $e) {
    die("Gabim ne lidhjen me databazen: " . $e->getMessage());
}
?>


