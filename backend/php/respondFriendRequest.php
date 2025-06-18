<?php
require_once 'config.php';

$data = json_decode(file_get_contents("php://input"));

if(isset($data->requestId) && isset($data->userId) && isset($data->action)) {
    if($data->action === 'accept') {
        $sql = "UPDATE friendships SET status = 'accepted' WHERE id = ? AND friend_id = ?";
    } else {
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