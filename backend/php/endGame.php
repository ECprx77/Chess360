<?php
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if(!isset($data->gameId)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required field: gameId'
    ]);
    exit;
}

try {
    $conn->begin_transaction();

    // First check if game exists and get players
    $checkSql = "SELECT status FROM games WHERE id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $data->gameId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Game not found");
    }
    
    $game = $result->fetch_assoc();
    
    // If game is already completed or abandoned, just return success
    if ($game['status'] !== 'ongoing') {
        echo json_encode(['status' => 'success', 'message' => 'Game already ended']);
        exit;
    }

    // Update game status to abandoned and delete active game
    $sql = "UPDATE games 
            SET status = 'abandoned', 
                end_time = CURRENT_TIMESTAMP
            WHERE id = ? AND status = 'ongoing'";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $data->gameId);
    
    if(!$stmt->execute()) {
        throw new Exception("Failed to update game status");
    }

    // Delete from active_games
    $sql = "DELETE FROM active_games WHERE game_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $data->gameId);
    
    if(!$stmt->execute()) {
        throw new Exception("Failed to delete active game");
    }

    $conn->commit();
    echo json_encode(['status' => 'success']);

} catch(Exception $e) {
    if(isset($conn)) $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();