<?php

$host = "localhost";
$user = "root";
$password = "";
$dbname = "academic_tutoring_web_business";
$dsn = "mysql:host={$host};dbname={$dbname}";

$pdo = new PDO($dsn, $user, $password);
$pdo->exec("SET time_zone = '+08:00';");

// Start session safely with XSS & Session Hijacking mitigations (HttpOnly + SameSite)
if (session_status() === PHP_SESSION_NONE) {
	session_set_cookie_params([
		'httponly' => true,      // Blocks JavaScript from reading session cookies via document.cookie
		'samesite' => 'Lax'      // Mitigates CSRF and cross-site context attacks
	]);
	session_start();
}

?>