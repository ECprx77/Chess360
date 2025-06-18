<?php
require_once 'config.php';

$data = json_decode(file_get_contents("php://input"));

if(isset($data->senderId) && isset($data->receiverId)) {
    // Check if request already exists
    $checkSql = "SELECT id FROM friendships 
                WHERE (user_id = ? AND friend_id = ?) 
                OR (user_id = ? AND friend_id = ?)";
    
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("iiii", $data->senderId, $data->receiverId, $data->receiverId, $data->senderId);
    $stmt->execute();
    
    if($stmt->get_result()->num_rows === 0) {
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