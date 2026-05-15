<?php
header('Content-Type: application/json; charset=utf-8');
// Simple CORS for local dev; tighten in production
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid method.');

    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['email'], $data['password'], $data['name'])) throw new Exception('Missing fields.');

    $name = trim($data['name']);
    $email = trim($data['email']);
    $password = $data['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Invalid email.');
    if (strlen($password) < 6) throw new Exception('Password too short.');

    require_once __DIR__ . '/../config/Database.php';
    $dbObj = new Database();
    $conn = $dbObj->getConnection();

    // Check existing
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) throw new Exception('Email already registered.');
    $stmt->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $name, $email, $hash);
    if (!$stmt->execute()) throw new Exception('Insert failed: ' . $stmt->error);
    $userId = $stmt->insert_id;
    $stmt->close();

    echo json_encode(['success' => true, 'userId' => $userId]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
