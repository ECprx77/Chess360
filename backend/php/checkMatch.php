<?php
/**
 * Matchmaking and Game Creation Endpoint
 * 
 * Handles matchmaking by checking for existing games, finding opponents in queue,
 * and creating new games with Chess960 positions. Manages the complete matchmaking flow.
 */

require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Parse incoming user ID
$data = json_decode(file_get_contents("php://input"));

if(!isset($data->userId)) {
    echo json_encode(['status' => 'error', 'message' => 'No user ID provided']);
    exit;
}

try {
    // Check for existing active game for this user
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
        // Return existing game information
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

    // Search for potential opponent in matchmaking queue
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
        
        // Create new game with transaction for data consistency
        $conn->begin_transaction();
        
        try {
            // Generate Chess960 starting position via API
            $response = file_get_contents('http://localhost:8000/chess/game/start');
            $gameData = json_decode($response, true);
            $initialFen = $gameData['fen'];

            // Randomly assign colors to players
            $isWhite = rand(0, 1) == 1;
            $whiteId = $isWhite ? $data->userId : $opponent['user_id'];
            $blackId = $isWhite ? $opponent['user_id'] : $data->userId;

            // Create new game record
            $sql = "INSERT INTO games (white_player_id, black_player_id, status, initial_fen) 
                    VALUES (?, ?, 'ongoing', ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $whiteId, $blackId, $initialFen);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create game: " . $stmt->error);
            }
            $gameId = $conn->insert_id;

            // Log successful game creation
            error_log("Game created with ID: $gameId");

            // Create active game entry for real-time communication
            $sql = "INSERT INTO active_games (game_id, socket_room) 
                    VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $socket_room = "game_" . $gameId;
            $stmt->bind_param("is", $gameId, $socket_room);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create active game: " . $stmt->error);
            }

            // Log active game creation
            error_log("Active game created for game ID: $gameId, room: $socket_room");

            // Remove both players from matchmaking queue
            $sql = "DELETE FROM matchmaking_queue WHERE user_id IN (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $data->userId, $opponent['user_id']);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to remove players from queue: " . $stmt->error);
            }

            $conn->commit();

            // Return match information
            echo json_encode([
                'status' => 'matched',
                'gameId' => $gameId,
                'opponent' => [
                    'id' => $opponent['user_id'],
                    'username' => $opponent['username'],
                    'elo' => $opponent['elo_rating']
                ],
                'isWhite' => $isWhite,
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

    // No match found, continue searching
    echo json_encode(['status' => 'searching']);

} catch(Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();