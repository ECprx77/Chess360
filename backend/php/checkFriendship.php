<?php
/**
 * Friendship Status Check Endpoint
 * 
 * Checks the friendship status between two users.
 * Returns the current status: 'pending', 'accepted', or 'none'.
 */

require_once 'config.php';

// Parse incoming user data
$data = json_decode(file_get_contents("php://input"));

// Validate user IDs
if(isset($data->userId) && isset($data->targetId)) {
    // Check friendship status in both directions (sender/receiver)
    $sql = "SELECT status FROM friendships 
            WHERE (user_id = ? AND friend_id = ?) 
            OR (user_id = ? AND friend_id = ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", 
        $data->userId, $data->targetId,
        $data->targetId, $data->userId
    );
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        // Return existing friendship status
        $friendship = $result->fetch_assoc();
        echo json_encode([
            'status' => $friendship['status']
        ]);
    } else {
        // No friendship exists
        echo json_encode([
            'status' => 'none'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid input'
    ]);
}

$conn->close();