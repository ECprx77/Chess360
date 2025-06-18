<?php
require_once 'config.php';

$data = json_decode(file_get_contents("php://input"));

if(isset($data->username) && isset($data->userId)) {
    $sql = "SELECT id, username FROM users 
            WHERE username LIKE ? AND id != ?";
    
    $searchTerm = $data->username . '%';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $searchTerm, $data->userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check if friend request already exists
        $checkSql = "SELECT status FROM friendships 
                    WHERE (user_id = ? AND friend_id = ?) 
                    OR (user_id = ? AND friend_id = ?)";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("iiii", $data->userId, $user['id'], $user['id'], $data->userId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        $user['isPending'] = $checkResult->num_rows > 0;
        
        echo json_encode(['status' => 'success', 'user' => $user]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
}

$conn->close();