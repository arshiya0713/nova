<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "nova_agency";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $phone === '' || $email === '' || $address === '' || $message === '') {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

if (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
    echo json_encode(['success' => false, 'message' => 'Name should only contain letters']);
    exit();
}

if (!preg_match('/^\d{10}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Phone number should contain exactly 10 digits']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit();
}

$check_sql = "SELECT ticket_id FROM contacts WHERE email = ?";
$check_stmt = $conn->prepare($check_sql);
if (!$check_stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}
$check_stmt->bind_param('s', $email);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $ticket_id = $row['ticket_id'];

    $update_sql = "UPDATE contacts SET name = ?, phone = ?, address = ?, message = ?, updated_at = NOW() WHERE email = ?";
    $update_stmt = $conn->prepare($update_sql);
    if (!$update_stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit();
    }
    $update_stmt->bind_param('sssss', $name, $phone, $address, $message, $email);
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'ticket_id' => $ticket_id, 'message' => 'Message updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating: ' . $update_stmt->error]);
    }
    $update_stmt->close();
} else {
    $ticket_id = 'TICKET-' . strtoupper(substr(md5(uniqid($email . microtime(true)) . random_int(1000, 9999)), 0, 8));
    if ($ticket_id === '') {
        echo json_encode(['success' => false, 'message' => 'Ticket generation failed']);
        exit();
    }

    $insert_sql = "INSERT INTO contacts (ticket_id, name, phone, email, address, message, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $insert_stmt = $conn->prepare($insert_sql);
    if (!$insert_stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit();
    }
    $insert_stmt->bind_param('ssssss', $ticket_id, $name, $phone, $email, $address, $message);
    if ($insert_stmt->execute()) {
        echo json_encode(['success' => true, 'ticket_id' => $ticket_id, 'message' => 'Message recorded successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error inserting: ' . $insert_stmt->error]);
    }
    $insert_stmt->close();
}

$check_stmt->close();
$conn->close();
?>