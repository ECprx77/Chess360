<?php
/**
 * Friend Request Send Endpoint
 * 
 * Sends a friendship request from one user to another.
 * Prevents duplicate requests and validates input parameters.
 */

require_once 'config.php';

// Parse incoming request data
$data = json_decode(file_get_contents("php://input"));

// Validate sender and receiver IDs
if(isset($data->senderId) && isset($data->receiverId)) {
    // Check if friendship request already exists in either direction
    $checkSql = "SELECT id FROM friendships 
                WHERE (user_id = ? AND friend_id = ?) 
                OR (user_id = ? AND friend_id = ?)";
    
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("iiii", $data->senderId, $data->receiverId, $data->receiverId, $data->senderId);
    $stmt->execute();
    
    if($stmt->get_result()->num_rows === 0) {
        // Create new friendship request with pending status
        $sql = "INSERT INTO friendships (user_id, friend_id, status) VALUES (?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $data->senderId, $data->receiverId);
        
        if($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to send request']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Request already exists']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
}

$conn->close();