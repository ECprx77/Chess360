import mysql.connector
from mysql.connector import Error

"""
Database Synchronization Module
Handles real-time updates to game state in the MySQL database.
"""

def update_game_state(game_id: int, new_position: str, move: str):
    """
    Update the game state in the database with new position and move.
    
    Args:
        game_id (int): The ID of the game to update
        new_position (str): New FEN position after the move
        move (str): The move made in UCI format
        
    Raises:
        Error: If database operation fails
    """
    try:
        connection = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='chess360'
        )
        cursor = connection.cursor()
        
        # Update current position and append move to history
        query = """
            UPDATE games 
            SET current_position = %s,
                moves_history = CONCAT(COALESCE(moves_history, ''), %s, ' ')
            WHERE id = %s
        """
        cursor.execute(query, (new_position, move, game_id))
        connection.commit()
        print(f"Game state updated successfully for game {game_id}")
        
    except Error as e:
        print(f"Error updating game state: {e}")
        raise e
    finally:
        if 'connection' in locals() and connection.is_connected():
            cursor.close()
            connection.close()