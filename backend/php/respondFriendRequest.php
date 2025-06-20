<?php
/**
 * Friend Request Response Endpoint
 * 
 * Handles user responses to friendship requests.
 * Accepts or rejects pending friend requests based on user action.
 */

require_once 'config.php';

// Parse incoming response data
$data = json_decode(file_get_contents("php://input"));

// Validate request parameters
if(isset($data->requestId) && isset($data->userId) && isset($data->action)) {
    if($data->action === 'accept') {
        // Accept the friendship request
        $sql = "UPDATE friendships SET status = 'accepted' WHERE id = ? AND friend_id = ?";
    } else {
        // Reject/delete the friendship request
        $sql = "DELETE FROM friendships WHERE id = ? AND friend_id = ?";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $data->requestId, $data->userId);
    
    if($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to process request']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
}

$conn->close();