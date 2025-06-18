<?php
require_once 'config.php';

$data = json_decode(file_get_contents("php://input"));

if(isset($data->userId)) {
    // Get user's ELO
    $sql = "SELECT elo FROM matchmaking_queue WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $data->userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Find opponent within ELO range
        $sql = "SELECT q.user_id, u.username, u.elo_rating 
                FROM matchmaking_queue q
                JOIN users u ON q.user_id = u.id
                WHERE q.user_id != ? 
                AND ABS(q.elo - ?) <= 100
                ORDER BY q.timestamp ASC
                LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $data->userId, $user['elo']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $opponent = $result->fetch_assoc();
            
            // Remove both players from queue
            $sql = "DELETE FROM matchmaking_queue 
                    WHERE user_id IN (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $data->userId, $opponent['user_id']);
            $stmt->execute();
            
            echo json_encode([
                'status' => 'matched',
                'opponent' => [
                    'id' => $opponent['user_id'],
                    'username' => $opponent['username'],
                    'elo' => $opponent['elo_rating']
                ]
            ]);
        } else {
            echo json_encode(['status' => 'searching']);
        }
    }
}

$conn->close();