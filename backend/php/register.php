<?php
/**
 * User Registration Endpoint
 * 
 * Handles new user registration with email/username validation, password hashing,
 * and initialization of player statistics. Uses database transactions for data integrity.
 */

require_once 'config.php';

// Parse incoming JSON registration data
$data = json_decode(file_get_contents("php://input"));

// Validate required registration fields
if(isset($data->email) && isset($data->password) && isset($data->username)) {
    // Use transaction to ensure data consistency
    $conn->begin_transaction();
    
    try {
        // Check for existing email address
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $data->email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if($result->num_rows > 0) {
            throw new Exception('Email already exists');
        }
        
        // Check for existing username
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $data->username);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if($result->num_rows > 0) {
            throw new Exception('Username already exists');
        }
        
        // Create new user with hashed password and default ELO rating
        $hashed_password = password_hash($data->password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password, elo_rating, games_played, games_won) VALUES (?, ?, ?, 1200, 0, 0)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $data->username, $data->email, $hashed_password);
        
        if(!$stmt->execute()) {
            throw new Exception('Failed to create user');
        }
        
        $user_id = $stmt->insert_id;
        
        // Initialize player statistics record
        $stats_sql = "INSERT INTO player_stats (user_id, total_games, wins, losses, draws, current_streak, best_streak) VALUES (?, 0, 0, 0, 0, 0, 0)";
        $stats_stmt = $conn->prepare($stats_sql);
        $stats_stmt->bind_param("i", $user_id);
        
        if(!$stats_stmt->execute()) {
            throw new Exception('Failed to initialize player stats');
        }
        
        // Commit transaction on successful registration
        $conn->commit();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Registration successful',
            'user_id' => $user_id
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on any error
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
}

$conn->close();