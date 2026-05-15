<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid method.');

    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['email'], $data['password'])) throw new Exception('Missing fields.');

    $email = trim($data['email']);
    $password = $data['password'];

    require_once __DIR__ . '/../config/Database.php';
    $dbObj = new Database();
    $conn = $dbObj->getConnection();

    $stmt = $conn->prepare('SELECT id, name, password FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($id, $name, $hash);
    if (!$stmt->fetch()) throw new Exception('Account not found.');
    $stmt->close();

    if (!password_verify($password, $hash)) throw new Exception('Invalid password.');

    // Start session and set basic session vars
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['user_id'] = $id;
    $_SESSION['user_name'] = $name;

    echo json_encode(['success' => true, 'user' => ['id' => $id, 'name' => $name, 'email' => $email]]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
