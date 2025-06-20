<?php
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if(!isset($data->userId)) {
    echo json_encode(['status' => 'error', 'message' => 'No user ID provided']);
    exit;
}

try {
    // First check for existing match
    $sql = "SELECT g.id, g.white_player_id, g.black_player_id, g.initial_fen,
            u.username, u.elo_rating
            FROM games g 
            JOIN users u ON (u.id = CASE 
                WHEN g.white_player_id = ? THEN g.black_player_id 
                ELSE g.white_player_id 
            END)
            WHERE (g.white_player_id = ? OR g.black_player_id = ?)
            AND g.status = 'ongoing'
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $data->userId, $data->userId, $data->userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $game = $result->fetch_assoc();
        echo json_encode([
            'status' => 'matched',
            'gameId' => $game['id'],
            'opponent' => [
                'id' => $game['white_player_id'] == $data->userId ? 
                    $game['black_player_id'] : $game['white_player_id'],
                'username' => $game['username'],
                'elo' => $game['elo_rating']
            ],
            'isWhite' => $game['white_player_id'] == $data->userId,
            'fen' => $game['initial_fen']
        ]);
        exit;
    }

    // Check for potential match in queue
    $sql = "SELECT q.user_id, q.elo, u.username, u.elo_rating 
            FROM matchmaking_queue q
            JOIN users u ON q.user_id = u.id
            WHERE q.user_id != ? 
            AND ABS(q.elo - (SELECT elo FROM matchmaking_queue WHERE user_id = ?)) <= 100
            ORDER BY q.timestamp ASC
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $data->userId, $data->userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $opponent = $result->fetch_assoc();
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Get Chess960 position
            $response = file_get_contents('http://localhost:8000/chess/game/start');
            $gameData = json_decode($response, true);
            $initialFen = $gameData['fen'];

            // Create new game
            $isWhite = rand(0, 1) == 1;
            $whiteId = $isWhite ? $data->userId : $opponent['user_id'];
            $blackId = $isWhite ? $opponent['user_id'] : $data->userId;

            $sql = "INSERT INTO games (white_player_id, black_player_id, status, initial_fen) 
                    VALUES (?, ?, 'ongoing', ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $whiteId, $blackId, $initialFen);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create game: " . $stmt->error);
            }
            $gameId = $conn->insert_id;

            // Log game creation
            error_log("Game created with ID: $gameId");

            // Create active_game entry - fixed to match schema
            $sql = "INSERT INTO active_games (game_id, socket_room) 
                    VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $socket_room = "game_" . $gameId;  // Create a room name based on game ID
            $stmt->bind_param("is", $gameId, $socket_room);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create active game: " . $stmt->error);
            }

            // Log active game creation
            error_log("Active game created for game ID: $gameId, room: $socket_room");

            // Remove both players from queue
            $sql = "DELETE FROM matchmaking_queue WHERE user_id IN (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $data->userId, $opponent['user_id']);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to remove players from queue: " . $stmt->error);
            }

            $conn->commit();

            echo json_encode([
                'status' => 'matched',
                'gameId' => $gameId,
                'opponent' => [
                    'id' => $opponent['user_id'],
                    'username' => $opponent['username'],
                    'elo' => $opponent['elo_rating']
                ],
                'isWhite' => $isWhite,  // Fix: Use actual isWhite value
                'fen' => $initialFen
            ]);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error in checkMatch: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    echo json_encode(['status' => 'searching']);

} catch(Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();