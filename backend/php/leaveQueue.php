<?php
require_once 'config.php';

$data = json_decode(file_get_contents("php://input"));

if(isset($data->userId)) {
    $sql = "DELETE FROM matchmaking_queue WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $data->userId);
    
    if($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to leave queue']);
    }
}

$conn->close();