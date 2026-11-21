
<?php
session_start();

// Fshijmë të gjitha të dhënat e sesionit.
$_SESSION = [];

// Fshijmë cookie-n e sesionit nëse përdoren cookies.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Shkatërrojmë sesionin.
session_destroy();

// Ridrejtojmë përdoruesin te kyçja.
header("Location: login.php");
exit;
