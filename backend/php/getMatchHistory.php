<?php
/**
 * Match History Retrieval Endpoint
 * 
 * Retrieves the match history for a specific user.
 * Returns detailed information about past games including opponent details,
 * game results, and timestamps. Limited to 50 most recent matches.
 */

require_once 'config.php';

// Parse incoming user data
$data = json_decode(file_get_contents("php://input"));

// Validate user ID
if(isset($data->userId)) {
    // Get match history with opponent details
    $sql = "SELECT g.*, 
            u1.username as player1_username, u1.elo_rating as player1_elo,
            u2.username as player2_username, u2.elo_rating as player2_elo
            FROM games g
            INNER JOIN users u1 ON g.white_player_id = u1.id
            INNER JOIN users u2 ON g.black_player_id = u2.id
            WHERE g.white_player_id = ? OR g.black_player_id = ?
            ORDER BY g.start_time DESC LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $data->userId, $data->userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Build array of match history
    $matches = [];
    while($row = $result->fetch_assoc()) {
        $matches[] = $row;
    }
    
    echo json_encode(['status' => 'success', 'matches' => $matches]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
}

$conn->close();