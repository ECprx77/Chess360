<?php
/**
 * Friends List Retrieval Endpoint
 * 
 * Retrieves the list of accepted friends for a specific user.
 * Returns user IDs and usernames of all accepted friends.
 */

require_once 'config.php';

// Parse incoming user data
$data = json_decode(file_get_contents("php://input"));

// Validate user ID
if(isset($data->userId)) {
    // Get all accepted friends for this user
    $sql = "SELECT u.id, u.username FROM users u 
            INNER JOIN friendships f ON (u.id = f.friend_id) 
            WHERE f.user_id = ? AND f.status = 'accepted'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $data->userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Build array of friends
    $friends = [];
    while($row = $result->fetch_assoc()) {
        $friends[] = $row;
    }
    
    echo json_encode(['status' => 'success', 'friends' => $friends]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
}

$conn->close();