<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $_SESSION['darkMode'] = $data['darkMode'];
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false]);