<?php
/**
 * User Search Endpoint
 * 
 * Searches for users by username and checks friendship status.
 * Excludes the searching user from results and indicates if a
 * friendship request is already pending.
 */

require_once 'config.php';

// Parse incoming search data
$data = json_decode(file_get_contents("php://input"));

// Validate search parameters
if(isset($data->username) && isset($data->userId)) {
    // Search for users with matching username (excluding self)
    $sql = "SELECT id, username FROM users 
            WHERE username LIKE ? AND id != ?";
    
    $searchTerm = $data->username . '%';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $searchTerm, $data->userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check if friendship request already exists between users
        $checkSql = "SELECT status FROM friendships 
                    WHERE (user_id = ? AND friend_id = ?) 
                    OR (user_id = ? AND friend_id = ?)";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("iiii", $data->userId, $user['id'], $user['id'], $data->userId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        // Add friendship status to user data
        $user['isPending'] = $checkResult->num_rows > 0;
        
        echo json_encode(['status' => 'success', 'user' => $user]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
}

$conn->close();