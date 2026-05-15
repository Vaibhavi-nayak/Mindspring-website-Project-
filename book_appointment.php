<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['patientName', 'patientEmail', 'appointmentDate', 'appointmentTime', 'doctorName', 'therapyType'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => 'Missing required field: ' . $field]);
        exit;
    }
}

// Sanitize inputs
$patientName = trim($data['patientName']);
$patientEmail = trim($data['patientEmail']);
$appointmentDate = trim($data['appointmentDate']);
$appointmentTime = trim($data['appointmentTime']);
$doctorName = trim($data['doctorName']);
$therapyType = trim($data['therapyType']);

// Validate email
if (!filter_var($patientEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Validate date format
$date = DateTime::createFromFormat('Y-m-d', $appointmentDate);
if (!$date || $date->format('Y-m-d') !== $appointmentDate) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

// Check if date is in the future
if ($date < new DateTime('today')) {
    echo json_encode(['success' => false, 'message' => 'Appointment date must be in the future']);
    exit;
}

// Validate time format
if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $appointmentTime)) {
    echo json_encode(['success' => false, 'message' => 'Invalid time format']);
    exit;
}

// Check if time is within business hours (9 AM - 6 PM)
$time = DateTime::createFromFormat('H:i', $appointmentTime);
$startTime = DateTime::createFromFormat('H:i', '09:00');
$endTime = DateTime::createFromFormat('H:i', '18:00');

if ($time < $startTime || $time > $endTime) {
    echo json_encode(['success' => false, 'message' => 'Appointments are only available between 9 AM and 6 PM']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check for duplicate appointments (same doctor, date, and time)
    $checkStmt = $conn->prepare("SELECT id FROM appointments WHERE doctor_name = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
    $checkStmt->bind_param("sss", $doctorName, $appointmentDate, $appointmentTime);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This time slot is already booked. Please choose another time.']);
        exit;
    }
    
    // Insert appointment with therapy_type
    $stmt = $conn->prepare("INSERT INTO appointments (patient_name, patient_email, appointment_date, appointment_time, doctor_name, therapy_type, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->bind_param("ssssss", $patientName, $patientEmail, $appointmentDate, $appointmentTime, $doctorName, $therapyType);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Appointment booked successfully!',
            'appointment_id' => $stmt->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to book appointment: ' . $stmt->error]);
    }
    
    $stmt->close();
    $checkStmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>