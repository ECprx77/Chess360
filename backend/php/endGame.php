<?php
/**
 * Game Completion Endpoint
 * 
 * Handles game completion by updating game status, calculating ELO rating changes,
 * and cleaning up active game records. Supports checkmate, draw, and abandonment.
 */

require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON response header
header('Content-Type: application/json');

// Parse incoming JSON data
$data = json_decode(file_get_contents("php://input"));

// Validate required input parameters
if (!isset($data->gameId) || !isset($data->status)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields: gameId, status'
    ]);
    exit;
}

$gameId = $data->gameId;
$status = $data->status;
$winnerId = $data->winnerId ?? null;

try {
    $conn->begin_transaction();

    // Fetch and lock game record to prevent race conditions
    $sql = "SELECT * FROM games WHERE id = ? FOR UPDATE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $gameId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Game not found");
    }
    
    $game = $result->fetch_assoc();
    
    // Check if game is already completed
    if ($game['status'] !== 'ongoing') {
        echo json_encode(['status' => 'success', 'message' => 'Game already ended']);
        $conn->commit();
        exit;
    }

    // Update game status and set end time
    $sql = "UPDATE games SET status = ?, winner_id = ?, end_time = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $status, $winnerId, $gameId);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update game status");
    }

    // Calculate and update ELO ratings for completed games with a winner
    if ($status === 'completed' && $winnerId) {
        $loserId = ($winnerId == $game['white_player_id']) ? $game['black_player_id'] : $game['white_player_id'];

        // Retrieve current ELO ratings for both players
        $sql = "SELECT id, elo_rating FROM users WHERE id IN (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $winnerId, $loserId);
        $stmt->execute();
        $players = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $winnerElo = 0;
        $loserElo = 0;
        foreach ($players as $player) {
            if ($player['id'] == $winnerId) {
                $winnerElo = $player['elo_rating'];
            } else {
                $loserElo = $player['elo_rating'];
            }
        }

        // Calculate new ELO ratings using standard formula
        $k = 32;  // K-factor determines rating change sensitivity
        $winnerExpected = 1 / (1 + pow(10, ($loserElo - $winnerElo) / 400));
        $loserExpected = 1 / (1 + pow(10, ($winnerElo - $loserElo) / 400));
        
        $newWinnerElo = $winnerElo + $k * (1 - $winnerExpected);
        $newLoserElo = $loserElo + $k * (0 - $loserExpected);

        // Update ELO ratings in database
        $sql = "UPDATE users SET elo_rating = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $newWinnerElo, $winnerId);
        $stmt->execute();
        $stmt->bind_param("di", $newLoserElo, $loserId);
        $stmt->execute();
    }

    // Remove game from active games table
    $sql = "DELETE FROM active_games WHERE game_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $gameId);
    
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