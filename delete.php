<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

$id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("DELETE FROM requests WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header('Location: dashboard.php');
exit;
