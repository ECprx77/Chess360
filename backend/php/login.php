<?php
/**
 * User Authentication Endpoint
 * 
 * Handles user login by validating email and password against the database.
 * Returns user information on successful authentication.
 */

// Configure CORS and content type headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Methods');

// Include database configuration
require_once 'config.php';

// Parse incoming JSON data
$data = json_decode(file_get_contents("php://input"));

// Validate required input fields
if(isset($data->email) && isset($data->password)) {
    // Prepare SQL query to find user by email
    $sql = "SELECT id, username, email, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $data->email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Verify password hash
        if(password_verify($data->password, $user['password'])) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email']
                ]
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid password']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
}

$conn->close();
?>