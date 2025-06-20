<?php
require_once 'config.php';

$data = json_decode(file_get_contents("php://input"));

if(isset($data->userId) && isset($data->elo)) {
    error_log("Joining queue - User ID: " . $data->userId . ", ELO: " . $data->elo);
    
    // Remove any existing queue entry for this user
    $sql = "DELETE FROM matchmaking_queue WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $data->userId);
    $stmt->execute();
    
    // Add user to queue
    $sql = "INSERT INTO matchmaking_queue (user_id, elo) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $data->userId, $data->elo);
    
    if($stmt->execute()) {
        error_log("Successfully added to queue - User ID: " . $data->userId);
        echo json_encode(['status' => 'success']);
    } else {
        error_log("Failed to add to queue - User ID: " . $data->userId);
        echo json_encode(['status' => 'error', 'message' => 'Failed to join queue']);
    }
}

$conn->close();