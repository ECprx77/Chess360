<?php
/**
 * Matchmaking Queue Leave Endpoint
 * 
 * Removes a user from the matchmaking queue when they cancel
 * their search for an opponent or start a game.
 */

require_once 'config.php';

// Parse incoming user data
$data = json_decode(file_get_contents("php://input"));

// Validate user ID
if(isset($data->userId)) {
    // Remove user from matchmaking queue
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