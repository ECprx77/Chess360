<?php
require_once 'config.php';

// Get JSON data from request
$data = json_decode(file_get_contents("php://input"));

if(isset($data->email) && isset($data->password) && isset($data->username)) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if email already exists
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $data->email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if($result->num_rows > 0) {
            throw new Exception('Email already exists');
        }
        
        // Check if username already exists
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $data->username);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if($result->num_rows > 0) {
            throw new Exception('Username already exists');
        }
        
        // Hash password and insert new user
        $hashed_password = password_hash($data->password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password, elo_rating, games_played, games_won) VALUES (?, ?, ?, 1200, 0, 0)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $data->username, $data->email, $hashed_password);
        
        if(!$stmt->execute()) {
            throw new Exception('Failed to create user');
        }
        
        $user_id = $stmt->insert_id;
        
        // Initialize player stats
        $stats_sql = "INSERT INTO player_stats (user_id, total_games, wins, losses, draws, current_streak, best_streak) VALUES (?, 0, 0, 0, 0, 0, 0)";
        $stats_stmt = $conn->prepare($stats_sql);
        $stats_stmt->bind_param("i", $user_id);
        
        if(!$stats_stmt->execute()) {
            throw new Exception('Failed to initialize player stats');
        }
        
        // If everything OK, commit the transaction
        $conn->commit();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Registration successful',
            'user_id' => $user_id
        ]);
        
    } catch (Exception $e) {
        // If error occurs, rollback changes
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
}

$conn->close();