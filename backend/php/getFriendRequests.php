<?php
/**
 * Friend Requests Retrieval Endpoint
 * 
 * Retrieves all pending friendship requests sent to a specific user.
 * Returns the list of users who have sent friend requests.
 */

require_once 'config.php';

// Parse incoming user data
$data = json_decode(file_get_contents("php://input"));

// Validate user ID
if(isset($data->userId)) {
    // Get all pending friend requests for this user
    $sql = "SELECT f.id, u.username 
            FROM friendships f
            INNER JOIN users u ON f.user_id = u.id
            WHERE f.friend_id = ? AND f.status = 'pending'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $data->userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Build array of friend requests
    $requests = [];
    while($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    
    echo json_encode(['status' => 'success', 'requests' => $requests]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
}

$conn->close();