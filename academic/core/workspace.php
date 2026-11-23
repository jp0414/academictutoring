<?php
require_once __DIR__ . '/dbConfig.php';
require_once __DIR__ . '/models.php';
require_once __DIR__ . '/ui.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
