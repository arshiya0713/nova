<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "nova_agency";
$port = 3307;

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit();
}

// Get form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    $message = $_POST['message'] ?? '';

    // Validate data
    if (empty($name) || empty($phone) || empty($email) || empty($address) || empty($message)) {
        echo json_encode([
            'success' => false,
            'message' => 'All fields are required'
        ]);
        exit();
    }

    // Validate name - only letters
    if (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        echo json_encode([
            'success' => false,
            'message' => 'Name should only contain letters'
        ]);
        exit();
    }

    // Validate phone - only 10 digits
    if (!preg_match("/^\d{10}$/", $phone)) {
        echo json_encode([
            'success' => false,
            'message' => 'Phone number should contain exactly 10 digits'
        ]);
        exit();
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email address'
        ]);
        exit();
    }

    $create_messages_table = "CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contact_id INT NOT NULL,
        ticket_id VARCHAR(50) NOT NULL,
        message LONGTEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($create_messages_table);

    $check_sql = "SELECT id, ticket_id FROM contacts WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    
    if (!$check_stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Prepare failed: ' . $conn->error
        ]);
        exit();
    }

    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $contact_id = $row['id'];
        $ticket_id = $row['ticket_id'];

        if (empty($ticket_id)) {
            $ticket_id = 'TICKET-' . strtoupper(substr(md5(uniqid($email . microtime(true))), 0, 8));
            $update_ticket_sql = "UPDATE contacts SET ticket_id = ? WHERE id = ?";
            $update_ticket_stmt = $conn->prepare($update_ticket_sql);
            if ($update_ticket_stmt) {
                $update_ticket_stmt->bind_param('si', $ticket_id, $contact_id);
                $update_ticket_stmt->execute();
                $update_ticket_stmt->close();
            }
        }

        $update_sql = "UPDATE contacts SET name = ?, phone = ?, address = ?, message = ?, updated_at = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);

        if (!$update_stmt) {
            echo json_encode([
                'success' => false,
                'message' => 'Prepare failed: ' . $conn->error
            ]);
            exit();
        }

        $update_stmt->bind_param('ssssi', $name, $phone, $address, $message, $contact_id);

        if (!$update_stmt->execute()) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $update_stmt->error
            ]);
            exit();
        }

        $update_stmt->close();

        $insert_message_sql = "INSERT INTO contact_messages (contact_id, ticket_id, message, created_at) VALUES (?, ?, ?, NOW())";
        $insert_message_stmt = $conn->prepare($insert_message_sql);
        if (!$insert_message_stmt) {
            echo json_encode([
                'success' => false,
                'message' => 'Prepare failed: ' . $conn->error
            ]);
            exit();
        }
        $insert_message_stmt->bind_param('iss', $contact_id, $ticket_id, $message);
        $insert_message_stmt->execute();
        $insert_message_stmt->close();

        echo json_encode([
            'success' => true,
            'ticket_id' => $ticket_id,
            'message' => 'Message updated successfully! Ticket ID: ' . $ticket_id
        ]);
    } else {
        $ticket_id = 'TICKET-' . strtoupper(substr(md5(uniqid($email . microtime(true)) . random_int(1000, 9999)), 0, 8));
        if ($ticket_id === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Ticket generation failed'
            ]);
            exit();
        }

        $insert_sql = "INSERT INTO contacts (ticket_id, name, phone, email, address, message, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $insert_stmt = $conn->prepare($insert_sql);

        if (!$insert_stmt) {
            echo json_encode([
                'success' => false,
                'message' => 'Prepare failed: ' . $conn->error
            ]);
            exit();
        }

        $insert_stmt->bind_param("ssssss", $ticket_id, $name, $phone, $email, $address, $message);

        if (!$insert_stmt->execute()) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $insert_stmt->error
            ]);
            exit();
        }

        $contact_id = $insert_stmt->insert_id;
        $insert_stmt->close();

        $insert_message_sql = "INSERT INTO contact_messages (contact_id, ticket_id, message, created_at) VALUES (?, ?, ?, NOW())";
        $insert_message_stmt = $conn->prepare($insert_message_sql);
        if (!$insert_message_stmt) {
            echo json_encode([
                'success' => false,
                'message' => 'Prepare failed: ' . $conn->error
            ]);
            exit();
        }
        $insert_message_stmt->bind_param('iss', $contact_id, $ticket_id, $message);
        $insert_message_stmt->execute();
        $insert_message_stmt->close();

        echo json_encode([
            'success' => true,
            'ticket_id' => $ticket_id,
            'message' => 'Success'
        ]);
    }

    $check_stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

$conn->close();
?>
